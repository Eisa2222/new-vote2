<?php

declare(strict_types=1);

namespace App\Modules\Results\Models;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CampaignResult extends Model
{
    protected $fillable = [
        'campaign_id', 'status', 'total_votes', 'notes',
        'calculated_at', 'approved_at', 'announced_at',
        'calculated_by', 'approved_by', 'announced_by',
    ];

    protected $casts = [
        'status'        => ResultStatus::class,
        'calculated_at' => 'datetime',
        'approved_at'   => 'datetime',
        'announced_at'  => 'datetime',
    ];

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function items(): HasMany { return $this->hasMany(ResultItem::class); }
}
