<?php

declare(strict_types=1);

namespace App\Modules\Results\Domain;

use App\Modules\Results\Enums\ResultStatus;

/**
 * Enforces the result status lifecycle.
 *
 *  pending_calculation ─► calculated
 *  calculated          ─► approved
 *  approved            ─► announced
 *  approved            ─► hidden
 *  hidden              ─► announced
 *  announced           ─► hidden       (emergency takedown)
 *  calculated          ─► pending_calculation  (recalc reset)
 */
final class ResultStatusTransitionRule
{
    private const ALLOWED = [
        'pending_calculation' => ['calculated'],
        'calculated'          => ['approved', 'pending_calculation'],
        'approved'            => ['announced', 'hidden', 'pending_calculation'],
        'hidden'              => ['announced', 'pending_calculation'],
        'announced'           => ['hidden'],
    ];

    public function assert(ResultStatus $from, ResultStatus $to): void
    {
        $allowed = self::ALLOWED[$from->value] ?? [];
        if (! in_array($to->value, $allowed, true)) {
            throw new \DomainException(
                "Invalid result status transition: {$from->value} → {$to->value}",
            );
        }
    }

    public function can(ResultStatus $from, ResultStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED[$from->value] ?? [], true);
    }
}
