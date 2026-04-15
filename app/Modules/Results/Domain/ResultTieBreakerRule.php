<?php

declare(strict_types=1);

namespace App\Modules\Results\Domain;

use Illuminate\Support\Collection;

/**
 * Deterministic tie-breaker for equal vote counts.
 *
 * Rule (documented and stable):
 *   1. Higher votes_count wins.
 *   2. On equal votes, LOWER candidate.display_order wins (admin-controlled order).
 *   3. On equal display_order, LOWER candidate_id wins (earliest attached).
 *
 * This yields a total order with no randomness, which is essential for
 * reproducible results after recalculation.
 */
final class ResultTieBreakerRule
{
    /**
     * @param  Collection<int, object{candidate_id:int, votes_count:int, display_order:int}>  $rows
     */
    public function sort(Collection $rows): Collection
    {
        return $rows->sort(function ($a, $b) {
            return [-$a->votes_count, $a->display_order ?? 0, $a->candidate_id]
                <=> [-$b->votes_count, $b->display_order ?? 0, $b->candidate_id];
        })->values();
    }
}
