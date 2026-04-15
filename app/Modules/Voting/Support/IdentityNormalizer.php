<?php

declare(strict_types=1);

namespace App\Modules\Voting\Support;

/**
 * Normalizes Saudi mobile numbers and national IDs into a canonical form
 * so that "+966501234567", "00966501234567", "0501234567" all match.
 */
final class IdentityNormalizer
{
    public static function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw); // strip non-digits

        // Strip Saudi country code prefix: 00966 or 966
        if (str_starts_with($digits, '00966')) $digits = substr($digits, 5);
        elseif (str_starts_with($digits, '966')) $digits = substr($digits, 3);

        // Normalize to "05xxxxxxxx" — accept "5xxxxxxxx" and prepend 0
        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function normalizeNationalId(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw);
    }

    /** Mask a value showing only the last 4 digits: ******1234 */
    public static function mask(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value);
        $len   = strlen($value);
        return $len <= 4
            ? str_repeat('*', $len)
            : str_repeat('*', $len - 4).substr($value, -4);
    }
}
