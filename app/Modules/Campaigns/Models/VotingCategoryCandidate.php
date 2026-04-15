<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Models;

use App\Modules\Campaigns\Enums\CandidateType;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VotingCategoryCandidate extends Model
{
    protected $fillable = [
        'voting_category_id', 'candidate_type', 'player_id', 'club_id',
        'display_order', 'is_active',
    ];

    protected $casts = [
        'candidate_type' => CandidateType::class,
        'is_active'      => 'boolean',
    ];

    public function category(): BelongsTo { return $this->belongsTo(VotingCategory::class, 'voting_category_id'); }
    public function player(): BelongsTo   { return $this->belongsTo(Player::class); }
    public function club(): BelongsTo     { return $this->belongsTo(Club::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }

    /** Unified display name regardless of candidate type. */
    public function displayName(): ?string
    {
        return match ($this->candidate_type) {
            CandidateType::Player => $this->player?->localized('name'),
            CandidateType::Club   => $this->club?->localized('name'),
            default               => $this->player?->localized('name') ?? $this->club?->localized('name'),
        };
    }
}
