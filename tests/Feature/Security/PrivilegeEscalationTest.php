<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Users\Actions\UpdateUserAction;
use App\Modules\Users\Actions\LogActivityAction;

/**
 * Regression tests for security finding C-1 (privilege escalation).
 *
 * Without the AssignableRoles guard, any account with `users.manage`
 * could grant itself or any peer the `super_admin` role and take over
 * the platform. These tests pin the four invariants:
 *   1. Lower-tier admins cannot grant super_admin to anyone.
 *   2. An admin cannot edit their own roles.
 *   3. Lower-tier admins cannot revoke super_admin from a super_admin.
 *   4. A super_admin can grant super_admin (and other roles) freely.
 */

beforeEach(function () {
    seedRolesAndPermissions();
    // The shared helper only seeds super_admin + committee; ensure
    // campaign_manager exists for the privilege-escalation matrix.
    \Spatie\Permission\Models\Role::findOrCreate('campaign_manager', 'web');
});

/**
 * Make an admin user with the given role names. Bypasses the guard.
 */
function makeUserWithRoles(array $roles, array $overrides = []): User
{
    $u = User::factory()->create(array_merge([
        'name'  => 'admin-'.uniqid(),
        'email' => 'admin-'.uniqid().'@example.com',
    ], $overrides));
    $u->syncRoles($roles);
    return $u;
}

it('blocks a non-super admin from granting super_admin to a peer', function () {
    $manager = makeUserWithRoles(['campaign_manager']);
    $peer    = makeUserWithRoles(['campaign_manager']);

    $this->actingAs($manager);

    app(UpdateUserAction::class)->execute($peer, [
        'name'   => $peer->name,
        'email'  => $peer->email,
        'status' => 'active',
        'roles'  => ['super_admin'],   // attempt the escalation
    ]);

    expect($peer->fresh()->getRoleNames()->all())
        ->not->toContain('super_admin');
});

it('blocks a non-super admin from granting super_admin to themselves', function () {
    $manager = makeUserWithRoles(['campaign_manager']);

    $this->actingAs($manager);

    app(UpdateUserAction::class)->execute($manager, [
        'name'   => $manager->name,
        'email'  => $manager->email,
        'status' => 'active',
        'roles'  => ['super_admin', 'campaign_manager'],
    ]);

    // Self-edit lockout — roles must be unchanged.
    expect($manager->fresh()->getRoleNames()->all())
        ->toBe(['campaign_manager']);
});

it('forbids a non-super admin from stripping super_admin from a peer', function () {
    $manager = makeUserWithRoles(['campaign_manager']);
    $victim  = makeUserWithRoles(['super_admin']);

    $this->actingAs($manager);

    app(UpdateUserAction::class)->execute($victim, [
        'name'   => $victim->name,
        'email'  => $victim->email,
        'status' => 'active',
        'roles'  => ['campaign_manager'],   // try to demote the super_admin
    ]);

    expect($victim->fresh()->getRoleNames()->all())
        ->toContain('super_admin');
});

it('lets a super_admin grant any role including super_admin', function () {
    $boss = makeUserWithRoles(['super_admin']);
    $peer = makeUserWithRoles(['campaign_manager']);

    $this->actingAs($boss);

    app(UpdateUserAction::class)->execute($peer, [
        'name'   => $peer->name,
        'email'  => $peer->email,
        'status' => 'active',
        'roles'  => ['super_admin', 'campaign_manager'],
    ]);

    expect($peer->fresh()->getRoleNames()->sort()->values()->all())
        ->toBe(['campaign_manager', 'super_admin']);
});
