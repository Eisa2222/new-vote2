<?php

declare(strict_types=1);

use App\Modules\Shared\Services\SettingsService;
use App\Modules\Shared\Support\MailConfig;
use Illuminate\Support\Facades\Mail;

beforeEach(function () { seedRolesAndPermissions(); });

it('renders the Mail (SMTP) tab on the settings page', function () {
    $admin = makeSuperAdmin();
    test()->actingAs($admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Mail (SMTP)')
        ->assertSee('name="mail_host"', false);
});

it('saves SMTP settings and encrypts the password', function () {
    $admin = makeSuperAdmin();

    test()->actingAs($admin)->post(route('admin.settings.mail.update'), [
        'mail_host'         => 'smtp.example.com',
        'mail_port'         => 587,
        'mail_username'     => 'bot@example.com',
        'mail_password'     => 'super-secret-123',
        'mail_encryption'   => 'tls',
        'mail_from_address' => 'no-reply@example.com',
        'mail_from_name'    => 'SFPA',
    ])->assertRedirect(route('admin.settings.index'));

    $settings = app(SettingsService::class);
    expect($settings->get('mail_host'))->toBe('smtp.example.com');
    expect($settings->get('mail_from_address'))->toBe('no-reply@example.com');

    // Password is stored encrypted, not plaintext.
    $stored = (string) $settings->get('mail_password');
    expect($stored)->not->toBe('super-secret-123');
    expect(MailConfig::decryptSafe($stored))->toBe('super-secret-123');
});

it('keeps the old password when the field is left blank', function () {
    $admin = makeSuperAdmin();
    $settings = app(SettingsService::class);
    $settings->set('mail_password', MailConfig::encryptSafe('original-pwd'), 'mail');

    test()->actingAs($admin)->post(route('admin.settings.mail.update'), [
        'mail_host'         => 'smtp.example.com',
        'mail_port'         => 587,
        'mail_username'     => 'bot@example.com',
        'mail_password'     => '',                  // blank = keep
        'mail_encryption'   => 'tls',
        'mail_from_address' => 'no-reply@example.com',
        'mail_from_name'    => 'SFPA',
    ])->assertRedirect(route('admin.settings.index'));

    $stored = (string) $settings->get('mail_password');
    expect(MailConfig::decryptSafe($stored))->toBe('original-pwd');
});

it('applies DB settings to runtime mail config', function () {
    $settings = app(SettingsService::class);
    $settings->setMany([
        'mail_host'         => 'mail.sfpa.sa',
        'mail_port'         => 587,
        'mail_username'     => 'bot@sfpa.sa',
        'mail_encryption'   => 'tls',
        'mail_from_address' => 'no-reply@sfpa.sa',
        'mail_from_name'    => 'SFPA',
    ], 'mail');
    $settings->set('mail_password', MailConfig::encryptSafe('pwd'), 'mail');

    MailConfig::apply($settings);

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('mail.sfpa.sa');
    expect(config('mail.mailers.smtp.port'))->toBe(587);
    expect(config('mail.mailers.smtp.password'))->toBe('pwd');
    expect(config('mail.from.address'))->toBe('no-reply@sfpa.sa');
});

it('accepts a test_to address and flashes a result (success or warning)', function () {
    // Against a test SMTP host the send WILL fail — that's fine. The
    // controller's job is to flash the outcome either way, so we
    // accept `success` *or* `warning`; both prove the code path ran
    // without throwing a 500.
    $admin = makeSuperAdmin();

    $resp = test()->actingAs($admin)->post(route('admin.settings.mail.update'), [
        'mail_host'         => 'smtp.example.com',
        'mail_port'         => 587,
        'mail_username'     => 'bot@example.com',
        'mail_password'     => 'pwd',
        'mail_encryption'   => 'tls',
        'mail_from_address' => 'no-reply@example.com',
        'mail_from_name'    => 'SFPA',
        'test_to'           => 'qa@example.com',
    ])->assertRedirect();

    $session = session();
    expect($session->has('success') || $session->has('warning'))->toBeTrue();
});

it('validates required SMTP fields', function () {
    $admin = makeSuperAdmin();

    test()->actingAs($admin)->post(route('admin.settings.mail.update'), [
        'mail_host'         => '',
        'mail_port'         => 99999,    // out of range
        'mail_encryption'   => 'invalid',
        'mail_from_address' => 'not-an-email',
        'mail_from_name'    => '',
    ])->assertSessionHasErrors(['mail_host', 'mail_port', 'mail_encryption', 'mail_from_address', 'mail_from_name']);
});
