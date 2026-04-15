<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Enums\CandidateType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Players\Models\Player;
use Illuminate\Support\Facades\DB;

/**
 * Attach one or more players to the TOS category that matches their position.
 * Rejects any player whose position doesn't fit the category's position_slot.
 */
final class AttachTeamOfSeasonCandidatesAction
{
    /**
     * @param  int[]  $playerIds
     * @return int  count inserted (skips duplicates)
     */
    public function execute(Campaign $campaign, VotingCategory $category, array $playerIds): int
    {
        if ($category->campaign_id !== $campaign->id) {
            throw new \DomainException('Category does not belong to this campaign.');
        }
        $slot = $category->position_slot;

        $players = Player::active()->whereIn('id', $playerIds)->get();
        foreach ($players as $p) {
            if ($p->position?->value !== $slot) {
                throw new \DomainException(
                    "Player {$p->name_en} has position {$p->position?->value}, cannot attach to {$slot} line.",
                );
            }
        }

        return DB::transaction(function () use ($category, $players) {
            $existing = $category->candidates()->pluck('player_id')->all();
            $toInsert = $players->reject(fn ($p) => in_array($p->id, $existing, true));

            $baseOrder = (int) $category->candidates()->max('display_order') + 1;
            foreach ($toInsert->values() as $i => $p) {
                $category->candidates()->create([
                    'candidate_type' => CandidateType::Player->value,
                    'player_id'      => $p->id,
                    'display_order'  => $baseOrder + $i,
                    'is_active'      => true,
                ]);
            }
            return $toInsert->count();
        });
    }
}
