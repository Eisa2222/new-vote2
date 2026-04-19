<?php

declare(strict_types=1);

namespace App\Modules\Sms\Services;

use App\Modules\Shared\Services\SettingsService;
use App\Modules\Shared\Support\MailConfig;
use App\Modules\Sms\Contracts\SmsDriverContract;
use App\Modules\Sms\Drivers\LogDriver;
use App\Modules\Sms\Drivers\TwilioDriver;
use App\Modules\Sms\Drivers\UnifonicDriver;
use App\Modules\Voting\Support\IdentityNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Thin orchestration layer over the SMS drivers.
 *
 *   • Reads the active provider + its secrets from the `settings`
 *     table (group = 'sms'), decrypting anything that was stored
 *     with Crypt via MailConfig::encryptSafe / decryptSafe.
 *   • Normalises the phone number to E.164 (Saudi-aware) so every
 *     driver gets the same shape.
 *   • Writes a one-line audit trail to the default log channel for
 *     every attempt (success + failure), without the message body —
 *     helpful for ops, safe for PII.
 */
final class SmsService
{
    public const KEYS = [
        'sms_driver',          // 'log' | 'twilio' | 'unifonic'
        'sms_twilio_sid',
        'sms_twilio_token',    // encrypted
        'sms_twilio_from',
        'sms_unifonic_appsid', // encrypted
        'sms_unifonic_sender',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public function isConfigured(): bool
    {
        return in_array((string) $this->settings->get('sms_driver', ''), ['log', 'twilio', 'unifonic'], true);
    }

    public function driverName(): string
    {
        return (string) $this->settings->get('sms_driver', 'log');
    }

    public function send(string $to, string $message): array
    {
        $toE164 = IdentityNormalizer::normalizeMobile($to);
        // Ensure international format: Saudi local 05XXXXXXXX → +9665XXXXXXXX.
        if (str_starts_with($toE164, '05')) {
            $toE164 = '+966'.substr($toE164, 1);
        } elseif (! str_starts_with($toE164, '+')) {
            $toE164 = '+'.$toE164;
        }

        $driver = $this->resolveDriver();
        $result = $driver->send($toE164, $message);

        Log::info('sms.send', [
            'driver'      => $driver->name(),
            'to'          => self::maskPhone($toE164),
            'ok'          => $result['ok'] ?? false,
            'provider_id' => $result['provider_id'] ?? null,
            'error'       => $result['error'] ?? null,
            // Explicitly NOT logging the message body — keeps OTP codes
            // out of the log stream.
        ]);

        return $result;
    }

    public function resolveDriver(): SmsDriverContract
    {
        $name = $this->driverName();

        if ($name === 'twilio') {
            $sid   = (string) $this->settings->get('sms_twilio_sid', '');
            $token = MailConfig::decryptSafe((string) $this->settings->get('sms_twilio_token', ''));
            $from  = (string) $this->settings->get('sms_twilio_from', '');
            if ($sid !== '' && $token !== '' && $from !== '') {
                return new TwilioDriver($sid, $token, $from);
            }
        }

        if ($name === 'unifonic') {
            $appSid = MailConfig::decryptSafe((string) $this->settings->get('sms_unifonic_appsid', ''));
            $sender = (string) $this->settings->get('sms_unifonic_sender', '');
            if ($appSid !== '' && $sender !== '') {
                return new UnifonicDriver($appSid, $sender);
            }
        }

        // Missing credentials or unknown driver → log driver. Keeps
        // dev and fresh installs from throwing at send time.
        return new LogDriver();
    }

    /** `+966501234567` → `+966 ✱✱✱✱ 567` for safe logging. */
    private static function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 6) return str_repeat('*', $len);
        return substr($phone, 0, 4).str_repeat('*', $len - 7).substr($phone, -3);
    }
}
