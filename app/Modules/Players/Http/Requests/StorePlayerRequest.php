<?php

declare(strict_types=1);

namespace App\Modules\Players\Http\Requests;

use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Shared\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Player::class) ?? false;
    }

    public function rules(): array
    {
        // Per-club name uniqueness — stops the "same player twice" case
        // that previously slipped past validation and triggered a
        // 500 on insert (TC022).
        $clubId = $this->integer('club_id');
        $sameClubRule = fn (string $field) => Rule::unique('players', $field)
            ->where('club_id', $clubId)
            ->whereNull('deleted_at');

        return [
            'club_id'       => ['required', 'integer', Rule::exists('clubs', 'id')->whereNull('deleted_at')],
            'sport_id'      => ['required', 'integer', Rule::exists('sports', 'id')],
            'name_ar'       => ['required', 'string', 'max:120', $sameClubRule('name_ar')],
            'name_en'       => ['required', 'string', 'max:120', $sameClubRule('name_en')],
            'photo'         => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'position'      => ['required', new Enum(PlayerPosition::class)],
            'is_captain'    => ['boolean'],
            'jersey_number' => [
                'nullable', 'integer', 'min:1', 'max:999',
                Rule::unique('players')
                    ->where('club_id', $this->integer('club_id'))
                    ->where('sport_id', $this->integer('sport_id'))
                    ->whereNull('deleted_at'),
            ],
            'status'        => ['nullable', new Enum(ActiveStatus::class)],
            // No `whereNull('deleted_at')` here — the DB unique index on
            // national_id / mobile_number is global (covers soft-deleted
            // rows too). Ignoring deleted_at in validation would let a
            // request slip past and then 500 on the INSERT. Validating
            // against every row gives the user a friendly error instead.
            'national_id'   => ['nullable', 'string', 'max:20', Rule::unique('players', 'national_id')],
            'mobile_number' => ['nullable', 'string', 'max:20', Rule::unique('players', 'mobile_number')],
        ];
    }

    public function prepareForValidation(): void
    {
        $merge = [];
        if ($this->filled('national_id')) {
            $merge['national_id'] = \App\Modules\Voting\Support\IdentityNormalizer::normalizeNationalId($this->input('national_id'));
        }
        if ($this->filled('mobile_number')) {
            $merge['mobile_number'] = \App\Modules\Voting\Support\IdentityNormalizer::normalizeMobile($this->input('mobile_number'));
        }
        if ($merge) $this->merge($merge);
    }
}
