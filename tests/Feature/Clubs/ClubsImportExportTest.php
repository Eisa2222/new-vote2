<?php

declare(strict_types=1);

use App\Modules\Clubs\Actions\ExportClubsAction;
use App\Modules\Clubs\Actions\ImportClubsAction;
use App\Modules\Clubs\Models\Club;
use App\Modules\Leagues\Models\League;
use App\Modules\Sports\Models\Sport;

beforeEach(function () {
    seedRolesAndPermissions();
});

it('imports clubs and syncs their sports and leagues by name_en', function () {
    $football = Sport::create(['slug' => 'football', 'name_ar' => 'كرة القدم', 'name_en' => 'Football', 'status' => 'active']);
    $league   = League::create(['sport_id' => $football->id, 'slug' => 'roshn', 'name_ar' => 'دوري روشن', 'name_en' => 'Roshn League', 'status' => 'active']);

    $csv = "\xEF\xBB\xBFsep=,\r\n"
        ."name_ar,name_en,short_name,status,sports_en,leagues_en\n"
        ."الهلال,Al-Hilal,HIL,active,Football,Roshn League\n"
        ."النصر,Al-Nassr,NAS,active,Football,\n";

    $result = app(ImportClubsAction::class)->execute(uploadCsv($csv));

    expect($result['created'])->toBe(2);
    expect($result['updated'])->toBe(0);
    expect($result['skipped'])->toBe([]);

    $hilal = Club::where('name_en', 'Al-Hilal')->first();
    expect($hilal?->sports->pluck('name_en')->all())->toBe(['Football']);
    expect($hilal?->leagues->pluck('name_en')->all())->toBe(['Roshn League']);

    expect(Club::where('name_en', 'Al-Nassr')->first()?->leagues)->toHaveCount(0);
});

it('reports unknown sports and leagues without crashing the import', function () {
    Sport::create(['slug' => 'football', 'name_ar' => 'كرة القدم', 'name_en' => 'Football', 'status' => 'active']);

    $csv = "name_ar,name_en,short_name,status,sports_en,leagues_en\n"
        ."Club X,Club X,,active,Football|MadeUpSport,Ghost League\n";

    $result = app(ImportClubsAction::class)->execute(uploadCsv($csv));

    expect($result['created'])->toBe(1);
    $errors = array_column($result['skipped'], 'error');
    expect($errors)->toContain("Sport 'MadeUpSport' not found (club saved without it)");
    expect($errors)->toContain("League 'Ghost League' not found (club saved without it)");
});

it('export CSV starts with BOM + sep hint + expected columns', function () {
    Club::factory()->create(['name_en' => 'ExportMe', 'name_ar' => 'للتصدير']);

    $body = captureStream(app(ExportClubsAction::class)->execute());

    expect(substr($body, 0, 3))->toBe("\xEF\xBB\xBF");
    expect($body)->toContain("sep=,");
    expect($body)->toContain('name_ar,name_en,short_name,status,sports_en,leagues_en');
    expect($body)->toContain('ExportMe');
});
