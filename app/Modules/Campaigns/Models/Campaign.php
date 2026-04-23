<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Models;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Enums\ResultsVisibility;
use App\Modules\Shared\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Campaign extends Model
{
    use SoftDeletes, HasTranslations;

    protected $fillable = [
        'title_ar', 'title_en', 'description_ar', 'description_en',
        'type', 'league_id', 'start_at', 'end_at', 'max_voters', 'public_token',
        'status', 'results_visibility', 'created_by',
        'committee_approved_at', 'committee_approved_by',
        'committee_rejected_at', 'committee_rejected_by', 'committee_rejection_note',
        // New club-scoped voting flags.
        'allow_self_vote', 'allow_teammate_vote',
    ];

    protected $casts = [
        'type'                  => CampaignType::class,
        'status'                => CampaignStatus::class,
        'results_visibility'    => ResultsVisibility::class,
        'start_at'              => 'datetime',
        'end_at'                => 'datetime',
        'committee_approved_at' => 'datetime',
        'committee_rejected_at' => 'datetime',
        'allow_self_vote'       => 'boolean',
        'allow_teammate_vote'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            $c->public_token ??= Str::random(32);
        });
    }

    public function league(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Leagues\Models\League::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(VotingCategory::class)->orderBy('display_order');
    }

    /**
     * Per-club participation rows for the new club-scoped voting flow.
     * Each row owns its own token and max_voters cap.
     */
    public function campaignClubs(): HasMany
    {
        return $this->hasMany(\App\Modules\Voting\Models\CampaignClub::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(\App\Modules\Voting\Models\Vote::class);
    }

    public function result(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\Results\Models\CampaignResult::class);
    }

    public function isAcceptingVotes(): bool
    {
        $now = now();
        return $this->status === CampaignStatus::Active
            && $now->between($this->start_at, $this->end_at);
    }

    public function reachedMaxVoters(): bool
    {
        return $this->max_voters !== null && $this->votes()->count() >= $this->max_voters;
    }

    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'committee_approved_by');
    }

    public function rejectedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'committee_rejected_by');
    }
}
