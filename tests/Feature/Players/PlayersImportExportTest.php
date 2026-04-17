<?php

declare(strict_types=1);

use App\Modules\Players\Actions\ExportPlayersAction;
use App\Modules\Players\Actions\ImportPlayersAction;
use App\Modules\Players\Models\Player;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('export CSV contains the UTF-8 BOM + Excel sep hint + header row', function () {
    makeClub(['name_en' => 'Al-Hilal', 'name_ar' => 'الهلال']);
    $body = captureStream(app(ExportPlayersAction::class)->template());

    expect(substr($body, 0, 3))->toBe("\xEF\xBB\xBF");
    expect($body)->toContain("sep=,");
    expect($body)->toContain('name_ar,name_en,club_name_en');
});

it('imports a CSV and creates missing players, updating existing ones', function () {
    $club = makeClub(['name_en' => 'Al-Hilal', 'name_ar' => 'الهلال']);
    makeFootball();

    $csv = "\xEF\xBB\xBFsep=,\r\n"
        ."name_ar,name_en,club_name_en,sport_name_en,position,jersey_number,is_captain,national_id,mobile_number,status\n"
        ."محمد,Mohamed,Al-Hilal,Football,attack,9,1,1012345678,0501234567,active\n"
        ."علي,Ali,Al-Hilal,Football,defense,4,0,,,active\n";

    $file = uploadCsv($csv);
    $result = app(ImportPlayersAction::class)->execute($file);

    expect($result['created'])->toBe(2);
    expect($result['updated'])->toBe(0);
    expect($result['skipped'])->toBe([]);
    expect(Player::where('name_en', 'Mohamed')->first()?->jersey_number)->toBe(9);

    // Re-import same CSV with a change — should UPDATE, not create.
    $csv2 = "\xEF\xBB\xBFsep=,\r\n"
        ."name_ar,name_en,club_name_en,sport_name_en,position,jersey_number,is_captain,national_id,mobile_number,status\n"
        ."محمد,Mohamed,Al-Hilal,Football,attack,10,1,1012345678,0501234567,active\n";
    $result2 = app(ImportPlayersAction::class)->execute(uploadCsv($csv2));
    expect($result2['created'])->toBe(0);
    expect($result2['updated'])->toBe(1);
    expect(Player::where('name_en', 'Mohamed')->first()?->jersey_number)->toBe(10);
});

it('skips rows with unknown clubs or missing required fields and keeps counts accurate', function () {
    makeClub(['name_en' => 'Al-Hilal', 'name_ar' => 'الهلال']);
    makeFootball();

    $csv = "name_ar,name_en,club_name_en,sport_name_en,position\n"
        .",Player1,,Football,attack\n"                       // missing club
        ."علي,Ali,Al-Nassr,Football,defense\n"               // unknown club
        ."خالد,Khaled,Al-Hilal,Football,attack\n";           // valid

    $result = app(ImportPlayersAction::class)->execute(uploadCsv($csv));

    expect($result['created'])->toBe(1);
    expect(count($result['skipped']))->toBe(2);
    expect($result['skipped'][0]['error'])->toContain('Missing name_en or club_name_en');
    expect($result['skipped'][1]['error'])->toContain("Club 'Al-Nassr' not found");
});

it('round-trips: export every player, re-import the same bytes → no duplicates created', function () {
    $club = makeClub(['name_en' => 'Al-Hilal', 'name_ar' => 'الهلال']);
    makeFootball();
    makePlayer(['club_id' => $club->id, 'name_en' => 'Roundtrip', 'name_ar' => 'عودة']);

    $csv = captureStream(app(ExportPlayersAction::class)->execute());
    $result = app(ImportPlayersAction::class)->execute(uploadCsv($csv));

    expect($result['created'])->toBe(0);
    expect($result['updated'])->toBe(1);
});

// ── helpers ────────────────────────────────────────────

function captureStream($response): string {
    ob_start();
    $response->sendContent();
    return ob_get_clean();
}

function uploadCsv(string $contents): UploadedFile {
    $tmp = tempnam(sys_get_temp_dir(), 'pest-csv');
    file_put_contents($tmp, $contents);
    return new UploadedFile($tmp, 'pest.csv', 'text/csv', null, true);
}
