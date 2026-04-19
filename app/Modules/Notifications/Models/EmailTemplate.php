<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable email body/subject.
 *
 * Resolution cascade when we need a template for (key, type, locale):
 *   1. exact match  (key, type, locale)
 *   2. generic type (key, null, locale)
 *   3. exact-type fallback locale      — always English
 *   4. generic + English               — final fallback
 *   5. null                            — the caller uses its hardcoded default
 */
final class EmailTemplate extends Model
{
    protected $fillable = ['key', 'campaign_type', 'locale', 'subject', 'body', 'is_active'];
    protected $casts    = ['is_active' => 'bool'];

    public static function resolve(string $key, ?string $campaignType, string $locale): ?self
    {
        $active = fn (Builder $q) => $q->where('is_active', true);

        $candidates = [
            [$key, $campaignType, $locale],
            [$key, null,          $locale],
            [$key, $campaignType, 'en'],
            [$key, null,          'en'],
        ];

        foreach ($candidates as [$k, $type, $loc]) {
            $q = self::query()->tap($active)
                ->where('key', $k)
                ->where('locale', $loc);
            $type === null ? $q->whereNull('campaign_type') : $q->where('campaign_type', $type);
            if ($row = $q->first()) return $row;
        }
        return null;
    }
}
