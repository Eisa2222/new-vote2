<?php

declare(strict_types=1);

namespace App\Modules\Players\Actions;

use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\NationalityType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Shared\Enums\ActiveStatus;
use App\Modules\Sports\Models\Sport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Parses a UTF-8 CSV of players and upserts them by (name_en + club).
 *
 * Returns a summary: ['created' => N, 'updated' => N, 'skipped' => [...row errors...]].
 */
final class ImportPlayersAction
{
    public function execute(UploadedFile $file): array
    {
        $created = 0;
        $updated = 0;
        $skipped = [];

        $fh = fopen($file->getRealPath(), 'r');
        if (! $fh) {
            return ['created' => 0, 'updated' => 0, 'skipped' => [['row' => 0, 'error' => 'Cannot open file']]];
        }

        // Skip BOM if present.
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }

        // Detect the column delimiter. Arabic Excel defaults to ';' when
        // saving CSVs, so we sniff the first line instead of assuming ','.
        $delimiter = ',';
        $peek      = ftell($fh);
        $first     = fgets($fh);
        if ($first !== false && preg_match('/^\s*sep=(.)/i', ltrim($first), $m)) {
            $delimiter = $m[1];
        } else {
            fseek($fh, $peek);
            $sample = $first ?: '';
            $counts = [',' => substr_count($sample, ','), ';' => substr_count($sample, ';'), "\t" => substr_count($sample, "\t")];
            arsort($counts);
            $delimiter = array_key_first($counts) ?: ',';
            if ($counts[$delimiter] === 0) $delimiter = ',';
        }

        $header = fgetcsv($fh, 0, $delimiter);
        if (! $header) {
            fclose($fh);
            return ['created' => 0, 'updated' => 0, 'skipped' => [['row' => 1, 'error' => 'Empty file']]];
        }
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        $rowNum = 1;
        DB::transaction(function () use ($fh, $header, $delimiter, &$created, &$updated, &$skipped, &$rowNum) {
            while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
                $rowNum++;
                if (count($row) === 1 && trim((string) $row[0]) === '') continue; // blank line

                $data = array_combine($header, array_pad($row, count($header), ''));
                $name_ar = trim((string)($data['name_ar'] ?? ''));
                $name_en = trim((string)($data['name_en'] ?? ''));
                $clubName = trim((string)($data['club_name_en'] ?? ''));
                $sportName = trim((string)($data['sport_name_en'] ?? ''));
                $position = strtolower(trim((string)($data['position'] ?? '')));
                $status   = strtolower(trim((string)($data['status'] ?? 'active')));

                // Nationality column. Accepts the canonical enum values
                // (saudi / foreign) as well as common variants seen in
                // exported sheets:
                //   ar: سعودي / غير سعودي / أجنبي
                //   en: saudi / non-saudi / non saudi / foreign
                // Defaults to 'saudi' when blank to keep existing
                // imports compatible — the admin can fix individual
                // rows in the UI if needed.
                $nationalityRaw = strtolower(trim((string)($data['nationality'] ?? '')));
                $nationality = match (true) {
                    $nationalityRaw === '' => NationalityType::Saudi->value,
                    in_array($nationalityRaw, ['saudi', 'سعودي'], true) => NationalityType::Saudi->value,
                    in_array($nationalityRaw, ['foreign', 'non-saudi', 'non saudi', 'أجنبي', 'غير سعودي'], true) => NationalityType::Foreign->value,
                    default => null, // explicit invalid value
                };
                if ($nationality === null) {
                    $skipped[] = ['row' => $rowNum, 'error' => "Invalid nationality '{$nationalityRaw}' (use 'saudi' or 'foreign')"];
                    continue;
                }

                if (! $name_en || ! $clubName) {
                    $skipped[] = ['row' => $rowNum, 'error' => 'Missing name_en or club_name_en'];
                    continue;
                }

                $club  = Club::where('name_en', $clubName)->first();
                if (! $club) {
                    $skipped[] = ['row' => $rowNum, 'error' => "Club '{$clubName}' not found"];
                    continue;
                }

                $sport = $sportName
                    ? Sport::where('name_en', $sportName)->first()
                    : $club->sports()->first();
                if (! $sport) {
                    $skipped[] = ['row' => $rowNum, 'error' => "Sport '{$sportName}' not found"];
                    continue;
                }

                if ($position && ! PlayerPosition::tryFrom($position)) {
                    $skipped[] = ['row' => $rowNum, 'error' => "Invalid position '{$position}'"];
                    continue;
                }

                // national_id / mobile_number deliberately NOT imported
                // any more — they're voter-facing PII captured on the
                // optional profile page after a vote, not roster data.
                $payload = [
                    'name_ar'       => $name_ar ?: $name_en,
                    'name_en'       => $name_en,
                    'club_id'       => $club->id,
                    'sport_id'      => $sport->id,
                    'position'      => $position ?: null,
                    'jersey_number' => $this->intOrNull($data['jersey_number'] ?? null),
                    'is_captain'    => $this->bool($data['is_captain'] ?? false),
                    'nationality'   => $nationality,
                    'status'        => in_array($status, ['active','inactive'], true) ? $status : ActiveStatus::Active->value,
                ];

                $existing = Player::where('name_en', $name_en)->where('club_id', $club->id)->first();
                try {
                    if ($existing) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        Player::create($payload);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $skipped[] = ['row' => $rowNum, 'error' => $e->getMessage()];
                }
            }
        });

        fclose($fh);

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
    }

    private function intOrNull($v): ?int
    {
        $v = trim((string) $v);
        return $v === '' ? null : (int) $v;
    }

    private function bool($v): bool
    {
        $v = strtolower(trim((string) $v));
        return in_array($v, ['1', 'true', 'yes', 'y'], true);
    }
}
