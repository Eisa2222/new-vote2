<?php

declare(strict_types=1);

namespace App\Modules\Voting\Support;

/**
 * Locked-in 1-4-3-3 formation for the new Team of the Season award.
 *
 * The old TOS flow allowed the admin to pick the formation per
 * campaign; the new spec makes it a single fixed shape so the pitch
 * UI can be identical on every campaign. If that ever changes again,
 * move these constants to config/voting.php and swap the call sites.
 */
final class Formation
{
    public const GOALKEEPER = 1;
    public const DEFENSE    = 4;
    public const MIDFIELD   = 3;
    public const ATTACK     = 3;
    public const TOTAL      = 11;

    /** @return array<string,int> */
    public static function slots(): array
    {
        return [
            'goalkeeper' => self::GOALKEEPER,
            'defense'    => self::DEFENSE,
            'midfield'   => self::MIDFIELD,
            'attack'     => self::ATTACK,
        ];
    }

    /** Ordered from back (GK) to front (Attack) for UI rendering. */
    public static function slotOrder(): array
    {
        return ['goalkeeper', 'defense', 'midfield', 'attack'];
    }
}
