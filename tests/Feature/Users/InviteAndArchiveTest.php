<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Users\Actions\CreateUserAction;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () { seedRolesAndPermissions(); });

it('creating a user without a password sends an invite email', function () {
    Notification::fake();

    $result = app(CreateUserAction::class)->execute([
        'name'   => 'Newbie',
        'email'  => 'newbie@sfpa.sa',
        'roles'  => ['super_admin'],
    ]);

    expect($result['invited'])->toBeTrue();
    Notification::assertSentTo($result['user'], ResetPassword::class);
});

it('creating a user with a password does NOT send an invite', function () {
    Notification::fake();

    $result = app(CreateUserAction::class)->execute([
        'name'     => 'Direct',
        'email'    => 'direct@sfpa.sa',
        'password' => 'StrongPass!1',
        'roles'    => ['super_admin'],
    ]);

    expect($result['invited'])->toBeFalse();
    Notification::assertNothingSent();
});

it('StoreUserRequest requires at least one role', function () {
    $admin = makeSuperAdmin();
    test()->actingAs($admin)->post(route('admin.users.store'), [
        'name'  => 'NoRoles',
        'email' => 'noroles@sfpa.sa',
        'roles' => [],
    ])->assertSessionHasErrors(['roles']);
});

it('archives a user via destroy and can restore from archive', function () {
    $admin = makeSuperAdmin();
    $other = User::factory()->create();

    test()->actingAs($admin)->delete(route('admin.users.destroy', $other))
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($other->id))->toBeNull();              // soft-deleted, hidden from default scope
    expect(User::withTrashed()->find($other->id))->not->toBeNull();

    test()->actingAs($admin)->post(route('admin.users.restore', $other->id))
        ->assertRedirect(route('admin.users.archive'));

    expect(User::find($other->id))->not->toBeNull();
});

it('force-delete requires users.forceDelete permission', function () {
    $admin = makeSuperAdmin();
    $other = User::factory()->create();
    $other->delete();

    // super_admin has forceDelete — succeeds.
    test()->actingAs($admin)->delete(route('admin.users.forceDelete', $other->id))
        ->assertRedirect();
    expect(User::withTrashed()->find($other->id))->toBeNull();
});
