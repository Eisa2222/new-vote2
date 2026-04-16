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

        // ──────────────────────────────────────────────
        // super_admin — full access
        // ──────────────────────────────────────────────
        Role::findOrCreate('super_admin', 'web')->syncPermissions(Permission::all());

        // ──────────────────────────────────────────────
        // auditor — read-only observer
        // ──────────────────────────────────────────────
        Role::findOrCreate('auditor', 'web')->syncPermissions([
            'clubs.viewAny', 'players.viewAny', 'campaigns.viewAny', 'results.view',
        ]);

        // ──────────────────────────────────────────────
        // committee — voting committee
        // Sees ONLY campaigns + results. Can approve / hide / announce.
        // Cannot manage clubs, players, users or settings.
        // ──────────────────────────────────────────────
        Role::findOrCreate('committee', 'web')->syncPermissions([
            'campaigns.viewAny',
            'results.view',
            'results.calculate',
            'results.approve',
            'results.hide',
            'results.announce',
        ]);

        // ──────────────────────────────────────────────
        // campaign_manager — content & campaign operator
        // Can manage clubs, players and create/edit/publish campaigns.
        // Cannot approve results, manage users, or touch system settings.
        // ──────────────────────────────────────────────
        Role::findOrCreate('campaign_manager', 'web')->syncPermissions([
            'clubs.viewAny', 'clubs.create', 'clubs.update', 'clubs.delete',
            'players.viewAny', 'players.create', 'players.update', 'players.delete',
            'campaigns.viewAny', 'campaigns.create', 'campaigns.update',
            'campaigns.publish', 'campaigns.close', 'campaigns.archive',
            'results.view',
        ]);
    }
}
