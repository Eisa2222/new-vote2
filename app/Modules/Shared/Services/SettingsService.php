<?php

declare(strict_types=1);

namespace App\Modules\Shared\Services;

use App\Modules\Shared\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value settings store with a cheap 60-second cache.
 * Lightweight on purpose — no Redis required.
 */
final class SettingsService
{
    private const CACHE_KEY = 'settings:all';
    private const TTL = 60;

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL,
            fn () => Setting::pluck('value', 'key')->toArray(),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'group' => $group],
        );
        Cache::forget(self::CACHE_KEY);
    }

    public function setMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
        Cache::forget(self::CACHE_KEY);
    }
}
