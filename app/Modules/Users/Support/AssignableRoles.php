<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Models\User;

/**
 * Privilege-escalation guard for role assignment.
 *
 * Background (security audit C-1, 2026-04):
 *   `User::syncRoles($data['roles'] ?? [])` was called with whatever
 *   role names the request carried. The validator only checked the
 *   role names existed — so any account with `users.manage` could
 *   grant itself or any peer the `super_admin` role and take over
 *   the whole platform (settings, results, mail/SMS creds, force
 *   delete, etc.).
 *
 * Rule — read top-down, first match wins:
 *
 *   • An admin can NEVER grant a role they don't already hold.
 *     A "campaign manager" cannot create a "super_admin".
 *   • An admin can NEVER edit their own roles. Self-promotion is
 *     blocked at this layer; demotion via the UI is also blocked
 *     (use a peer or DB to change your own role).
 *   • The `super_admin` role can only be granted by another
 *     `super_admin`. It can also only be revoked by another
 *     `super_admin` — preventing a downgrade attack from someone
 *     who happens to have `users.manage`.
 *
 * Returns the FILTERED role list (an array of role-name strings).
 * Roles silently dropped should be reported back to the UI via
 * a flash; the controller is responsible for that surface.
 */
final class AssignableRoles
{
    /** Roles the actor may grant to a target. */
    public static function allowed(?User $actor, ?User $target = null): array
    {
        if (! $actor) return [];

        $actorRoles = $actor->getRoleNames()->all();

        // Super admin can grant anything (including super_admin).
        if (in_array('super_admin', $actorRoles, true)) {
            return self::allRoleNames();
        }

        // Lower-tier admins can grant any role they themselves hold —
        // but never super_admin, regardless of permissions.
        return array_values(array_diff($actorRoles, ['super_admin']));
    }

    /**
     * Filter a requested role list to only those the actor may grant
     * to the target. If the request asks for a role the actor cannot
     * grant, it is silently dropped.
     *
     * Self-edit guard: when $actor === $target, returns the target's
     * EXISTING roles so the UI can never alter the actor's own access.
     *
     * @param  string[]  $requested
     * @return string[]
     */
    public static function filter(?User $actor, ?User $target, array $requested): array
    {
        if (! $actor) return [];

        // Self-edit lockout.
        if ($target && $actor->id === $target->id) {
            return $target->getRoleNames()->all();
        }

        $allowed = self::allowed($actor, $target);

        // Demotion guard: only super_admin may revoke super_admin.
        $targetRoles = $target?->getRoleNames()->all() ?? [];
        $isActorSuper = in_array('super_admin', $actor->getRoleNames()->all(), true);
        if (in_array('super_admin', $targetRoles, true) && ! $isActorSuper) {
            // Force-keep super_admin on the target if a non-super tries
            // to silently strip it via update.
            return array_values(array_unique(array_merge(
                array_intersect($requested, $allowed),
                ['super_admin'],
            )));
        }

        return array_values(array_intersect($requested, $allowed));
    }

    /** All role names known to Spatie Permission. */
    private static function allRoleNames(): array
    {
        return \Spatie\Permission\Models\Role::query()->pluck('name')->all();
    }
}
