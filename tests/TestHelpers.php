<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Sports\Models\Sport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function seedRolesAndPermissions(): void
{
    foreach ([
        'clubs.viewAny', 'clubs.create', 'clubs.update', 'clubs.delete',
        'players.viewAny', 'players.create', 'players.update', 'players.delete',
        'campaigns.viewAny', 'campaigns.create', 'campaigns.update',
        'campaigns.publish', 'campaigns.close', 'campaigns.archive',
        'campaigns.delete', 'campaigns.approve',
        'results.view', 'results.calculate', 'results.approve',
        'results.hide', 'results.announce',
        'users.manage',
    ] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $super = Role::findOrCreate('super_admin', 'web');
    $super->syncPermissions(Permission::all());

    Role::findOrCreate('committee', 'web')->syncPermissions([
        'campaigns.viewAny', 'clubs.viewAny', 'players.viewAny',
        'results.view', 'results.calculate', 'results.approve',
        'results.hide', 'results.announce',
    ]);
}

function makeSuperAdmin(): User
{
    seedRolesAndPermissions();
    $u = User::factory()->create();
    $u->assignRole('super_admin');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    return $u;
}

function makeFootball(): Sport
{
    return Sport::firstOrCreate(
        ['slug' => 'football'],
        ['name_ar' => 'كرة القدم', 'name_en' => 'Football', 'status' => 'active'],
    );
}

function makeClub(array $attrs = []): Club
{
    return Club::factory()->create($attrs);
}

function makePlayer(array $attrs = []): Player
{
    $attrs['club_id']  ??= makeClub()->id;
    $attrs['sport_id'] ??= makeFootball()->id;
    $attrs['name_ar']  ??= 'لاعب';
    $attrs['name_en']  ??= 'Player';
    $attrs['position'] ??= PlayerPosition::Attack;
    $attrs['status']   ??= 'active';
    return Player::create($attrs);
}
