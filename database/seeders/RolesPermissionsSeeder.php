<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'clubs.viewAny', 'clubs.create', 'clubs.update', 'clubs.delete',
            'players.viewAny', 'players.create', 'players.update', 'players.delete',
            'campaigns.viewAny', 'campaigns.create', 'campaigns.update',
            'campaigns.publish', 'campaigns.close', 'campaigns.archive',
            'results.view', 'results.calculate', 'results.approve',
            'results.hide', 'results.announce',
            'users.manage',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        Role::findOrCreate('super_admin', 'web')->syncPermissions(Permission::all());

        Role::findOrCreate('auditor', 'web')->syncPermissions([
            'clubs.viewAny', 'players.viewAny', 'campaigns.viewAny', 'results.view',
        ]);

        // The Voting Committee — the only role (besides super_admin) that can
        // APPROVE, HIDE, or ANNOUNCE results. They can also recalculate, but
        // cannot create campaigns, clubs, players or manage users.
        Role::findOrCreate('committee', 'web')->syncPermissions([
            'campaigns.viewAny',
            'clubs.viewAny',
            'players.viewAny',
            'results.view',
            'results.calculate',
            'results.approve',
            'results.hide',
            'results.announce',
        ]);
    }
}
