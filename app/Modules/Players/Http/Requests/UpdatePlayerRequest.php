<?php

declare(strict_types=1);

namespace App\Modules\Players\Http\Requests;

use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Shared\Enums\ActiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        $player = $this->route('player');
        $clubId = $this->integer('club_id', $player?->club_id);

        // Same per-club name uniqueness as the store rule, excluding
        // the current record so an update of other fields doesn't
        // trip over its own row.
        $sameClubRule = fn (string $field) => Rule::unique('players', $field)
            ->ignore($player?->id)
            ->where('club_id', $clubId)
            ->whereNull('deleted_at');

        return [
            'club_id'       => ['sometimes', 'required', 'integer', Rule::exists('clubs', 'id')],
            'sport_id'      => ['sometimes', 'required', 'integer', Rule::exists('sports', 'id')],
            'name_ar'       => ['sometimes', 'required', 'string', 'max:120', $sameClubRule('name_ar')],
            'name_en'       => ['sometimes', 'required', 'string', 'max:120', $sameClubRule('name_en')],
            'photo'         => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'position'      => ['sometimes', new Enum(PlayerPosition::class)],
            'is_captain'    => ['boolean'],
            'jersey_number' => [
                'nullable', 'integer', 'min:1', 'max:999',
                Rule::unique('players')
                    ->ignore($player?->id)
                    ->where('club_id', $this->integer('club_id', $player?->club_id))
                    ->where('sport_id', $this->integer('sport_id', $player?->sport_id))
                    ->whereNull('deleted_at'),
            ],
            'status'        => ['nullable', new Enum(ActiveStatus::class)],
            'national_id'   => ['nullable', 'string', 'max:20', Rule::unique('players', 'national_id')->ignore($player?->id)->whereNull('deleted_at')],
            'mobile_number' => ['nullable', 'string', 'max:20', Rule::unique('players', 'mobile_number')->ignore($player?->id)->whereNull('deleted_at')],
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
