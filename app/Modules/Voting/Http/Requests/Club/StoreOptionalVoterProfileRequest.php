<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Requests\Club;

use App\Modules\Voting\Support\IdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOptionalVoterProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Resolve the player whose profile is being saved. The controller
        // reads this from `session("club_voter_done:$token")` — pull the
        // same key here so the unique rules below can ignore the row.
        $token    = $this->route('token');
        $playerId = $token ? (int) session("club_voter_done:$token") : null;

        // Every field is optional — the whole form is skippable. But when
        // present the value must be globally unique (DB has a hard
        // unique index on both columns; the DB would otherwise raise a
        // 1062 QueryException that surfaces as a 500 to the voter, and
        // worse: without this rule a voter can hijack a teammate's
        // still-empty mobile number so SFPA winner-notification SMSes
        // are intercepted).
        return [
            'mobile_number' => [
                'nullable', 'string', 'max:20',
                Rule::unique('players', 'mobile_number')->ignore($playerId),
            ],
            'email'         => ['nullable', 'email', 'max:180'],
            'national_id'   => [
                'nullable', 'string', 'max:20',
                Rule::unique('players', 'national_id')->ignore($playerId),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        // Normalise BEFORE the unique check so "05xx xxxx", "+9665xx..."
        // and "٠٥..." all collide with the same canonical form stored
        // in the DB and cannot be used to bypass uniqueness.
        $merge = [];
        if ($this->filled('mobile_number')) {
            $merge['mobile_number'] = IdentityNormalizer::normalizeMobile($this->input('mobile_number'));
        }
        if ($this->filled('national_id')) {
            $merge['national_id'] = IdentityNormalizer::normalizeNationalId($this->input('national_id'));
        }
        if ($this->filled('email')) {
            $merge['email'] = mb_strtolower(trim((string) $this->input('email')));
        }
        if ($merge) $this->merge($merge);
    }
}
