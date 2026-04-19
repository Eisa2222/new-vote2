<?php

declare(strict_types=1);

use App\Modules\Shared\Services\SettingsService;
use App\Modules\Shared\Support\MailConfig;
use App\Modules\Sms\Drivers\LogDriver;
use App\Modules\Sms\Drivers\TwilioDriver;
use App\Modules\Sms\Drivers\UnifonicDriver;
use App\Modules\Sms\Services\SmsService;

beforeEach(function () { seedRolesAndPermissions(); });

it('renders the SMS tab on the settings page', function () {
    $admin = makeSuperAdmin();
    test()->actingAs($admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('data-tab="sms"', false)
        ->assertSee('name="sms_driver"', false);
});

it('saves Twilio credentials with the token encrypted', function () {
    $admin = makeSuperAdmin();

    test()->actingAs($admin)->post(route('admin.settings.sms.update'), [
        'sms_driver'       => 'twilio',
        'sms_twilio_sid'   => 'ACabc123',
        'sms_twilio_token' => 'super-secret-token',
        'sms_twilio_from'  => '+19715551234',
    ])->assertRedirect(route('admin.settings.index'));

    $s = app(SettingsService::class);
    expect($s->get('sms_driver'))->toBe('twilio');
    expect($s->get('sms_twilio_sid'))->toBe('ACabc123');

    $stored = (string) $s->get('sms_twilio_token');
    expect($stored)->not->toBe('super-secret-token');
    expect(MailConfig::decryptSafe($stored))->toBe('super-secret-token');
});

it('keeps the old Twilio token when field is left blank', function () {
    $admin = makeSuperAdmin();
    $s = app(SettingsService::class);
    $s->set('sms_twilio_token', MailConfig::encryptSafe('original-token'), 'sms');

    test()->actingAs($admin)->post(route('admin.settings.sms.update'), [
        'sms_driver'       => 'twilio',
        'sms_twilio_sid'   => 'ACabc123',
        'sms_twilio_token' => '',   // blank = keep
        'sms_twilio_from'  => '+19715551234',
    ])->assertRedirect();

    expect(MailConfig::decryptSafe((string) $s->get('sms_twilio_token')))->toBe('original-token');
});

it('normalises Saudi 05XXXXXXXX to +966 before handing to the driver', function () {
    $s = app(SettingsService::class);
    $s->set('sms_driver', 'log', 'sms');

    // Spy on Log to capture the normalised phone. The log driver and
    // the service both write via Log::info — we grep the spied calls.
    \Illuminate\Support\Facades\Log::spy();
    app(SmsService::class)->send('0501234567', 'hi');

    \Illuminate\Support\Facades\Log::shouldHaveReceived('info')
        ->withArgs(function ($event, $ctx = null) {
            if ($event !== 'SMS (log driver)' && $event !== 'sms.send') return false;
            $to = (string) ($ctx['to'] ?? '');
            return str_starts_with($to, '+966');
        });
});

it('falls back to the Log driver when twilio credentials are missing', function () {
    $s = app(SettingsService::class);
    $s->set('sms_driver', 'twilio', 'sms');
    // No SID/token/from → driver resolver should return the log driver.

    $d = app(SmsService::class)->resolveDriver();
    expect($d)->toBeInstanceOf(LogDriver::class);
});

it('resolves TwilioDriver when all credentials are present', function () {
    $s = app(SettingsService::class);
    $s->setMany([
        'sms_driver'      => 'twilio',
        'sms_twilio_sid'  => 'AC123',
        'sms_twilio_from' => '+1555',
    ], 'sms');
    $s->set('sms_twilio_token', MailConfig::encryptSafe('tok'), 'sms');

    $d = app(SmsService::class)->resolveDriver();
    expect($d)->toBeInstanceOf(TwilioDriver::class);
});

it('resolves UnifonicDriver when Unifonic credentials are present', function () {
    $s = app(SettingsService::class);
    $s->setMany([
        'sms_driver'          => 'unifonic',
        'sms_unifonic_sender' => 'SFPA',
    ], 'sms');
    $s->set('sms_unifonic_appsid', MailConfig::encryptSafe('app-sid'), 'sms');

    $d = app(SmsService::class)->resolveDriver();
    expect($d)->toBeInstanceOf(UnifonicDriver::class);
});

it('validates required fields when twilio is chosen', function () {
    $admin = makeSuperAdmin();

    test()->actingAs($admin)->post(route('admin.settings.sms.update'), [
        'sms_driver' => 'twilio',
        // sid + from missing
    ])->assertSessionHasErrors(['sms_twilio_sid', 'sms_twilio_from']);
});
