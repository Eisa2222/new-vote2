<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Services\SettingsService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

/**
 * Applies DB-stored SMTP settings to Laravel's runtime mail config.
 *
 * Called once per request from a service provider, after the container
 * is booted. If no SMTP settings are configured in the DB we leave the
 * .env-driven defaults alone — so the app still works on a fresh install
 * before an admin has opened Settings → Mail.
 *
 * Password is stored encrypted (Crypt) because the raw value would be
 * recoverable from the settings table otherwise; decrypt defensively
 * so a key rotation or an already-plaintext value doesn't crash boot.
 */
final class MailConfig
{
    public const KEYS = [
        'mail_host'        => 'MAIL_HOST',
        'mail_port'        => 'MAIL_PORT',
        'mail_username'    => 'MAIL_USERNAME',
        'mail_password'    => 'MAIL_PASSWORD',
        'mail_encryption'  => 'MAIL_ENCRYPTION',
        'mail_from_address'=> 'MAIL_FROM_ADDRESS',
        'mail_from_name'   => 'MAIL_FROM_NAME',
    ];

    public static function apply(SettingsService $settings): void
    {
        $all = $settings->all();
        // Skip entirely if no mail key was set yet — keeps fresh installs
        // working on MAIL_MAILER=log via .env with no ceremony.
        $anySet = false;
        foreach (self::KEYS as $k => $_) {
            if (! empty($all[$k])) { $anySet = true; break; }
        }
        if (! $anySet) return;

        $host       = (string) ($all['mail_host']        ?? '');
        $port       = (int)    ($all['mail_port']        ?? 587);
        $username   = (string) ($all['mail_username']    ?? '');
        $password   = self::decryptSafe((string) ($all['mail_password'] ?? ''));
        $encryption = (string) ($all['mail_encryption']  ?? 'tls');
        $from       = (string) ($all['mail_from_address'] ?? config('mail.from.address'));
        $fromName   = (string) ($all['mail_from_name']    ?? config('mail.from.name'));

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username ?: null);
        Config::set('mail.mailers.smtp.password', $password ?: null);
        Config::set('mail.mailers.smtp.encryption', in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null);
        Config::set('mail.from.address', $from);
        Config::set('mail.from.name', $fromName);
    }

    /**
     * Encrypted passwords are stored via Crypt. If decrypt throws (e.g.
     * APP_KEY rotated after the value was saved, or the value was never
     * encrypted in the first place), fall back to returning it raw so
     * the admin can re-save instead of the entire app 500'ing.
     */
    public static function decryptSafe(string $value): string
    {
        if ($value === '') return '';
        try {
            return (string) Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public static function encryptSafe(string $value): string
    {
        return $value === '' ? '' : Crypt::encryptString($value);
    }

    /**
     * Mask a password for display in the settings form.
     * Shows "•••••••• (saved)" if a value exists, empty string otherwise.
     * Admins never see the plaintext back — must retype to change.
     */
    public static function isPasswordSet(SettingsService $settings): bool
    {
        return ! empty($settings->get('mail_password'));
    }
}
