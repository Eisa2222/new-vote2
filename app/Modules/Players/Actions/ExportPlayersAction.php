<?php

declare(strict_types=1);

namespace App\Modules\Players\Actions;

use App\Modules\Players\Models\Player;
use App\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams every active/inactive player to a UTF-8 CSV (with BOM so
 * Excel opens Arabic cleanly). Columns match the ImportPlayersAction
 * header so export → edit → re-import round-trips without surprises.
 */
final class ExportPlayersAction
{
    public const COLUMNS = [
        'name_ar', 'name_en', 'club_name_en', 'sport_name_en',
        'position', 'jersey_number', 'is_captain',
        // Nationality: 'saudi' or 'foreign' — drives Best Saudi /
        // Best Foreign award eligibility on the voter ballot.
        'nationality',
        'status',
    ];

    public function execute(): StreamedResponse
    {
        $filename = 'players-'.now()->format('Y-m-d-His').'.csv';

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM → Excel treats the file as UTF-8 instead of Windows-1252.
            fwrite($out, "\xEF\xBB\xBF");
            // Excel-specific hint: force comma as separator even on locales
            // (e.g. ar-SA, fr-FR) where the system default is semicolon.
            fwrite($out, "sep=,\r\n");
            fputcsv($out, self::COLUMNS);

            $maskPii = (bool) config('voting.export.mask_pii_by_default', true);

            Player::with(['club', 'sport'])->orderBy('id')->chunk(500, function ($players) use ($out) {
                foreach ($players as $p) {
                    // Every cell goes through Csv::safe() to neutralise
                    // formula injection (a name starting with `=` would
                    // otherwise execute when Excel opens the file).
                    fputcsv($out, [
                        Csv::safe($p->name_ar),
                        Csv::safe($p->name_en),
                        Csv::safe($p->club?->name_en),
                        Csv::safe($p->sport?->name_en),
                        Csv::safe($p->position?->value),
                        Csv::safe($p->jersey_number),
                        Csv::safe($p->is_captain),
                        Csv::safe($p->nationality?->value ?? 'saudi'),
                        Csv::safe($p->status?->value ?? 'active'),
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Empty template with just the header row — used as a starter CSV. */
    public function template(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=,\r\n");
            fputcsv($out, self::COLUMNS);
            // Two sample rows so importers see both nationality values
            // and the expected column shape at a glance.
            fputcsv($out, [
                'أحمد علي', 'Ahmed Ali', 'Al-Hilal', 'Football',
                'attack', '9', '0', 'saudi', 'active',
            ]);
            fputcsv($out, [
                'كريستيانو رونالدو', 'Cristiano Ronaldo', 'Al-Nassr', 'Football',
                'attack', '7', '1', 'foreign', 'active',
            ]);
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="players-template.csv"',
        ]);
    }
}
