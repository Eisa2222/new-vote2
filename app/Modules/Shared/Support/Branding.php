<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Services\SettingsService;
use Illuminate\Support\Facades\Storage;

/**
 * Small read-only helper that resolves the platform's current brand:
 *   • the admin-configurable logo (public storage disk), if uploaded
 *   • the display name ("SFPA Voting" by default)
 *
 * Used by every layout/partial so a single source drives the wordmark
 * everywhere — login, admin header, voting pages, emails.
 *
 * Cheap: SettingsService caches the whole `settings` table for 60s.
 */
final class Branding
{
    public static function logoUrl(): ?string
    {
        $path = app(SettingsService::class)->get('platform_logo_path');
        if (! $path || ! is_string($path)) {
            return null;
        }
        // If the stored value is already a full URL (http:// or data:),
        // return as-is; otherwise resolve via the public disk.
        if (str_starts_with($path, 'http') || str_starts_with($path, 'data:')) {
            return $path;
        }
        return Storage::disk('public')->url($path);
    }

    public static function name(): string
    {
        return (string) app(SettingsService::class)->get('app_name', 'SFPA Voting');
    }

    /**
     * Short wordmark rendered when no logo is uploaded — e.g. "FPA" or
     * the first letters of the configured name. Keeps the old look
     * when the admin hasn't customised the brand yet.
     */
    public static function initials(): string
    {
        $name = self::name();
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (! $parts) return 'SFPA';
        $initials = '';
        foreach ($parts as $p) {
            if ($p === '') continue;
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
            if (mb_strlen($initials) >= 3) break;
        }
        return $initials ?: 'SFPA';
    }
}
