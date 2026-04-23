<?php

declare(strict_types=1);

namespace App\Modules\Voting\Models;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Vote extends Model
{
    protected $fillable = [
        'campaign_id', 'voter_identifier', 'ip_address', 'user_agent', 'submitted_at',
        // New club-scoped flow — the voter is a known player.
        'player_id', 'club_id', 'campaign_club_id',
        // Kept for backward compat with the legacy /vote/{token} path.
        'verified_player_id', 'verification_method', 'verification_value', 'is_verified',
    ];

    protected $casts = [
        'submitted_at'        => 'datetime',
        'is_verified'         => 'boolean',
        'verification_method' => VerificationMethod::class,
    ];

    public function campaign(): BelongsTo     { return $this->belongsTo(Campaign::class); }
    public function player(): BelongsTo       { return $this->belongsTo(Player::class); }
    public function club(): BelongsTo         { return $this->belongsTo(Club::class); }
    public function campaignClub(): BelongsTo { return $this->belongsTo(CampaignClub::class); }
    public function verifiedPlayer(): BelongsTo { return $this->belongsTo(Player::class, 'verified_player_id'); }
    public function items(): HasMany          { return $this->hasMany(VoteItem::class); }
}
