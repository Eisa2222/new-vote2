<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

beforeEach(function () { seedRolesAndPermissions(); });

it('forgot-password form renders', function () {
    test()->get(route('password.request'))->assertOk();
});

it('sends a reset link for an existing user', function () {
    Notification::fake();
    $user = User::factory()->create();

    test()->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('returns neutral message for unknown email (no user enumeration)', function () {
    // The broker fires no notification but still responds with the same
    // flash, so an attacker can't tell which addresses are registered.
    Notification::fake();
    test()->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect()
        ->assertSessionHas('success');
    Notification::assertNothingSent();
});

it('rate-limits repeated forgot requests per email+ip', function () {
    $user = User::factory()->create();

    test()->post(route('password.email'), ['email' => $user->email])->assertRedirect();
    test()->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors(['email']);
});
