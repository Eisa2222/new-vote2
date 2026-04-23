<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Services\SettingsService;


final class Branding
{
    public static function logoUrl(): ?string
    {
        $path = app(SettingsService::class)->get('platform_logo_path');

        if (! $path || ! is_string($path)) {
            return null;
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, 'data:')) {
            return $path;
        }
        return asset('storage/' . $path);
    }

    public static function name(): string
    {
        return (string) app(SettingsService::class)->get('app_name', 'SFPA Voting');
    }

    public static function initials(): string
    {
        $name  = trim(self::name());
        $parts = preg_split('/\s+/', $name) ?: [];
        if (! $parts) return 'SFPA';

        $first = $parts[0] ?? '';
        if (
            $first !== '' && mb_strtoupper($first, 'UTF-8') === $first
            && preg_match('/^[A-Z]{2,6}$/u', $first)
        ) {
            return mb_substr($first, 0, 4);
        }

        // Otherwise take the first letter of up to 3 words.
        $initials = '';
        foreach ($parts as $p) {
            if ($p === '') continue;
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
            if (mb_strlen($initials) >= 3) break;
        }
        return $initials ?: 'SFPA';
    }
}
