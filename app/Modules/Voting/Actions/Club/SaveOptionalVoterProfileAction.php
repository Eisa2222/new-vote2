<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions\Club;

use App\Modules\Players\Models\Player;
use App\Modules\Voting\Support\IdentityNormalizer;

/**
 * Post-vote step — the spec asks for an optional profile capture
 * (mobile / email / national_id) that updates the player's record
 * so the SFPA team can reach voters when results are announced.
 *
 * Rules:
 *   • Skipping is fine — all fields optional.
 *   • We never *overwrite* an existing value with a blank. An admin
 *     may have already populated the row; a voter leaving the field
 *     empty shouldn't wipe it.
 *   • national_id + mobile are normalised (Arabic digits → Latin,
 *     international prefix stripping) so the DB stores one canonical
 *     form regardless of how the voter typed it.
 */
final class SaveOptionalVoterProfileAction
{
    /**
     * @param  array{mobile_number?:?string, email?:?string, national_id?:?string}  $data
     */
    public function execute(Player $player, array $data): void
    {
        $updates = [];

        if (! empty($data['mobile_number'])) {
            $updates['mobile_number'] = IdentityNormalizer::normalizeMobile($data['mobile_number']);
        }
        if (! empty($data['national_id'])) {
            $updates['national_id'] = IdentityNormalizer::normalizeNationalId($data['national_id']);
        }
        if (! empty($data['email'])) {
            $updates['email'] = mb_strtolower(trim((string) $data['email']));
        }

        if ($updates) {
            $player->update($updates);
        }
    }
}
