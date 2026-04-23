<?php

declare(strict_types=1);

namespace App\Modules\Voting\Models;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pivot-plus — represents one club's participation in one campaign.
 * Owns a unique voting_link_token (the URL voters actually receive)
 * and a max_voters cap enforced per-club.
 *
 * current_voters_count is denormalised for fast "is this full?"
 * checks; kept in sync by IncrementCampaignClubVoterCountAction
 * inside the same DB transaction as the vote write.
 */
final class CampaignClub extends Model
{
    protected $fillable = [
        'campaign_id', 'club_id', 'max_voters', 'current_voters_count',
        'voting_link_token', 'is_active',
    ];

    protected $casts = [
        'max_voters'           => 'integer',
        'current_voters_count' => 'integer',
        'is_active'            => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'current_voters_count' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $row) {
            $row->voting_link_token ??= self::generateUniqueToken();
        });
    }

    public static function generateUniqueToken(int $length = 40): string
    {
        // Rare collision, but guard against it so the unique index
        // never fires a duplicate-key error at insert time.
        do {
            $token = Str::random($length);
        } while (self::where('voting_link_token', $token)->exists());
        return $token;
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /** Has the per-club quota been reached? (null = unlimited). */
    public function isFull(): bool
    {
        return $this->max_voters !== null && $this->current_voters_count >= $this->max_voters;
    }

    public function publicUrl(): string
    {
        return url('/vote/club/'.$this->voting_link_token);
    }
}
