<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Models;

use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Shared\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VotingCategory extends Model
{
    use HasTranslations;

    protected $fillable = [
        'campaign_id', 'title_ar', 'title_en',
        'category_type', 'position_slot',
        'required_picks', 'selection_min', 'selection_max',
        'is_active', 'display_order',
    ];

    protected $casts = [
        'category_type' => CategoryType::class,
        'is_active'     => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(VotingCategoryCandidate::class)->orderBy('display_order');
    }

    public function activeCandidates(): HasMany
    {
        return $this->candidates()->where('is_active', true);
    }

    public function positionSlot(): ?PlayerPosition
    {
        return $this->position_slot === 'any'
            ? null
            : PlayerPosition::tryFrom($this->position_slot);
    }

    /** How many picks must a voter submit for this category. */
    public function effectiveMin(): int
    {
        return (int) ($this->selection_min ?: $this->required_picks);
    }

    public function effectiveMax(): int
    {
        return (int) ($this->selection_max ?: $this->required_picks);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
