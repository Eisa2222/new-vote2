<?php

declare(strict_types=1);

namespace App\Modules\Voting\Models;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Vote extends Model
{
    protected $fillable = [
        'campaign_id', 'voter_identifier', 'ip_address', 'user_agent', 'submitted_at',
        'verified_player_id', 'verification_method', 'verification_value', 'is_verified',
    ];

    protected $casts = [
        'submitted_at'        => 'datetime',
        'is_verified'         => 'boolean',
        'verification_method' => VerificationMethod::class,
    ];

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function verifiedPlayer(): BelongsTo { return $this->belongsTo(Player::class, 'verified_player_id'); }
    public function items(): HasMany { return $this->hasMany(VoteItem::class); }
}
