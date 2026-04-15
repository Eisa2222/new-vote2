<?php

declare(strict_types=1);

namespace App\Modules\Voting\Actions;

use App\Modules\Players\Models\Player;
use App\Modules\Shared\Enums\ActiveStatus;
use App\Modules\Voting\Enums\VerificationMethod;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Support\IdentityNormalizer;

/**
 * Looks up a player by national_id or mobile_number.
 *
 * Returns a [player, method, normalized_value] tuple. Throws a generic
 * VotingException on failure to avoid leaking which field was wrong.
 */
final class VerifyVoterIdentityAction
{
    /**
     * @return array{player: Player, method: VerificationMethod, value: string}
     */
    public function execute(?string $nationalId, ?string $mobile): array
    {
        if ($nationalId) {
            $value  = IdentityNormalizer::normalizeNationalId($nationalId);
            $player = Player::active()->where('national_id', $value)->first();
            if ($player) {
                return ['player' => $player, 'method' => VerificationMethod::NationalId, 'value' => $value];
            }
        }
        if ($mobile) {
            $value  = IdentityNormalizer::normalizeMobile($mobile);
            $player = Player::active()->where('mobile_number', $value)->first();
            if ($player) {
                return ['player' => $player, 'method' => VerificationMethod::Mobile, 'value' => $value];
            }
        }

        // Generic message — never reveal which field was wrong.
        throw new VotingException(__('We could not verify your identity. Please check your input and try again.'));
    }
}
