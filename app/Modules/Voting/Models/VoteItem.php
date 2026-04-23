<?php

declare(strict_types=1);

namespace App\Modules\Voting\Models;

use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Campaigns\Models\VotingCategoryCandidate;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Enums\AwardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single pick inside a Vote.
 *
 * New club-scoped schema:
 *   • award_type           — `best_saudi` / `best_foreign` / `team_of_the_season`
 *   • category_key         — free-form sub-key (e.g. "best_saudi", or a TOTS slot)
 *   • candidate_player_id  — who the voter picked (individual awards + TOTS slots)
 *   • candidate_club_id    — used if a campaign ever votes for a club (future)
 *   • position_key         — goalkeeper / defense / midfield / attack (TOTS only)
 *
 * Legacy columns (voting_category_id, candidate_id) are kept nullable
 * so the old `/vote/{token}` flow keeps working during the transition.
 */
final class VoteItem extends Model
{
    protected $fillable = [
        'vote_id',
        // Legacy columns
        'voting_category_id', 'candidate_id',
        // New columns
        'award_type', 'category_key', 'candidate_player_id',
        'candidate_club_id', 'position_key',
    ];

    protected $casts = [
        'award_type' => AwardType::class,
    ];

    public function vote(): BelongsTo { return $this->belongsTo(Vote::class); }
    public function category(): BelongsTo { return $this->belongsTo(VotingCategory::class, 'voting_category_id'); }
    public function candidate(): BelongsTo { return $this->belongsTo(VotingCategoryCandidate::class, 'candidate_id'); }
    public function candidatePlayer(): BelongsTo { return $this->belongsTo(Player::class, 'candidate_player_id'); }
    public function candidateClub(): BelongsTo   { return $this->belongsTo(Club::class, 'candidate_club_id'); }
}
