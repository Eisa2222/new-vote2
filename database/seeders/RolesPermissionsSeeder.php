<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions model — 4 core verbs × N modules + a handful of lifecycle
 * verbs specific to our domain (publish/close/approve/…) and the
 * archive verbs (restore / forceDelete) for the new archive UI.
 *
 * Convention: `<module>.<verb>`.
 *   viewAny     — list page
 *   view        — show page (falls back to viewAny if unset in policy)
 *   create      — new form + POST
 *   update      — edit form + PUT/PATCH + toggle
 *   delete      — soft-delete / archive
 *   restore     — un-archive
 *   forceDelete — permanent destruction (super_admin only)
 */
final class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $crud = fn (string $module, array $extra = []) => array_merge([
            "$module.viewAny",
            "$module.view",
            "$module.create",
            "$module.update",
            "$module.delete",
            "$module.restore",
            "$module.forceDelete",
        ], array_map(fn ($v) => "$module.$v", $extra));

        $permissions = array_merge(
            $crud('clubs'),
            $crud('players'),
            $crud('campaigns', ['publish', 'close', 'archive', 'approve']),
            $crud('results',   ['calculate', 'approve', 'hide', 'announce']),
            $crud('sports'),
            $crud('leagues'),
            // Users have the legacy `users.manage` umbrella + the new
            // granular archive verbs. Both exist on purpose: existing
            // controllers still check `users.manage`, new routes gate
            // forceDelete separately so a campaign_manager promoted to
            // user management still cannot hard-delete.
            ['users.manage', 'users.restore', 'users.forceDelete'],
            ['settings.update'],
        );

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // super_admin — every permission including the two dangerous
        // `*.forceDelete` ones.
        Role::findOrCreate('super_admin', 'web')->syncPermissions(Permission::all());

        // auditor — read-only across everything.
        Role::findOrCreate('auditor', 'web')->syncPermissions([
            'clubs.viewAny', 'clubs.view',
            'players.viewAny', 'players.view',
            'campaigns.viewAny', 'campaigns.view',
            'results.viewAny', 'results.view',
            'sports.viewAny', 'leagues.viewAny',
        ]);

        // committee — sees campaigns + results; approves / hides / announces.
        Role::findOrCreate('committee', 'web')->syncPermissions([
            'campaigns.viewAny', 'campaigns.view',
            'campaigns.approve', 'campaigns.publish', 'campaigns.close',
            'results.viewAny', 'results.view',
            'results.calculate', 'results.approve', 'results.hide', 'results.announce',
        ]);

        // campaign_manager — content + campaign operator.
        // Can soft-delete (archive) but NOT forceDelete — that's reserved
        // for super_admin so a compromised manager cannot wipe history.
        Role::findOrCreate('campaign_manager', 'web')->syncPermissions([
            'clubs.viewAny', 'clubs.view', 'clubs.create', 'clubs.update', 'clubs.delete', 'clubs.restore',
            'players.viewAny', 'players.view', 'players.create', 'players.update', 'players.delete', 'players.restore',
            'campaigns.viewAny', 'campaigns.view', 'campaigns.create', 'campaigns.update',
            'campaigns.publish', 'campaigns.close', 'campaigns.archive', 'campaigns.delete', 'campaigns.restore',
            'results.viewAny', 'results.view',
            'sports.viewAny', 'leagues.viewAny',
        ]);
    }
}
