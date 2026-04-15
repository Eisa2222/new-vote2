<?php

declare(strict_types=1);

namespace App\Modules\Players\Models;

use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Shared\Concerns\HasTranslations;
use App\Modules\Shared\Enums\ActiveStatus;
use App\Modules\Sports\Models\Sport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Player extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'club_id', 'sport_id', 'name_ar', 'name_en',
        'photo_path', 'position', 'is_captain', 'jersey_number', 'status',
        'national_id', 'mobile_number',
    ];

    protected $casts = [
        'position'   => PlayerPosition::class,
        'status'     => ActiveStatus::class,
        'is_captain' => 'boolean',
    ];

    public function club(): BelongsTo { return $this->belongsTo(Club::class); }
    public function sport(): BelongsTo { return $this->belongsTo(Sport::class); }

    public function scopeActive($q) { return $q->where('status', ActiveStatus::Active->value); }

    public function scopeOfPosition($q, PlayerPosition $p)
    {
        return $q->where('position', $p->value);
    }

    protected static function newFactory()
    {
        return \Database\Factories\PlayerFactory::new();
    }
}
