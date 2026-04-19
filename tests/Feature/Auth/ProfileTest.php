<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () { seedRolesAndPermissions(); });

it('authenticated user can view profile page', function () {
    $user = User::factory()->create();
    test()->actingAs($user)->get(route('profile.show'))->assertOk();
});

it('guest is redirected from profile', function () {
    test()->get(route('profile.show'))->assertRedirect(route('login'));
});

it('updates name and email', function () {
    $user = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com']);
    test()->actingAs($user)->post(route('profile.update'), [
        'name' => 'New Name', 'email' => 'new@example.com',
    ])->assertRedirect(route('profile.show'));
    expect($user->fresh()->name)->toBe('New Name');
    expect($user->fresh()->email)->toBe('new@example.com');
});

it('rejects a profile update with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    test()->actingAs($user)->post(route('profile.update'), [
        'name' => $user->name, 'email' => 'taken@example.com',
    ])->assertSessionHasErrors(['email']);
});

it('changes password when current password matches', function () {
    $user = User::factory()->create(['password' => Hash::make('OldStrong!Pwd1')]);
    test()->actingAs($user)->post(route('profile.password'), [
        'current_password'      => 'OldStrong!Pwd1',
        'password'              => 'NewStrong!Pwd2',
        'password_confirmation' => 'NewStrong!Pwd2',
    ])->assertRedirect(route('profile.show'));
    expect(Hash::check('NewStrong!Pwd2', $user->fresh()->password))->toBeTrue();
});

it('rejects change with wrong current password', function () {
    $user = User::factory()->create(['password' => Hash::make('RightStrong!Pwd1')]);
    test()->actingAs($user)->post(route('profile.password'), [
        'current_password'      => 'wrong',
        'password'              => 'NewStrong!Pwd2',
        'password_confirmation' => 'NewStrong!Pwd2',
    ])->assertSessionHasErrors(['current_password']);
});
