<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Campaign::class) ?? false;
    }

    /**
     * Rules match the UI form shape: categories carry `player_ids[]` and
     * `club_ids[]`. The controller calls toActionPayload() to get the
     * shape the domain Action expects.
     */
    public function rules(): array
    {
        return [
            'title_ar'                          => ['required', 'string', 'max:180'],
            'title_en'                          => ['required', 'string', 'max:180'],
            'description_ar'                    => ['nullable', 'string'],
            'description_en'                    => ['nullable', 'string'],
            'type'                              => ['required', new Enum(CampaignType::class)],
            'league_id'                         => ['nullable', 'integer', 'exists:leagues,id'],
            'start_at'                          => ['required', 'date'],
            'end_at'                            => ['required', 'date', 'after:start_at'],
            'max_voters'                        => ['nullable', 'integer', 'min:1'],

            'categories'                        => ['required', 'array', 'min:1'],
            'categories.*.title_ar'             => ['required', 'string', 'max:180'],
            'categories.*.title_en'             => ['required', 'string', 'max:180'],
            'categories.*.position_slot'        => ['required', 'in:attack,midfield,defense,goalkeeper,any'],
            'categories.*.required_picks'       => ['required', 'integer', 'min:1', 'max:11'],
            'categories.*.player_ids'           => ['array'],
            'categories.*.player_ids.*'         => ['integer', 'exists:players,id'],
            'categories.*.club_ids'             => ['array'],
            'categories.*.club_ids.*'           => ['integer', 'exists:clubs,id'],
        ];
    }

    /**
     * Reshape the validated data into the `{category, candidates[]}`
     * structure the CreateVotingCampaignAction expects. Keeps all
     * "massage the request body" logic inside this FormRequest so
     * the controller stays one-liner.
     *
     * @return array<string,mixed>
     */
    public function toActionPayload(): array
    {
        $data = $this->validated();

        foreach ($data['categories'] as &$category) {
            $candidates = [];
            foreach ($category['player_ids'] ?? [] as $playerId) {
                $candidates[] = ['player_id' => $playerId];
            }
            foreach ($category['club_ids'] ?? [] as $clubId) {
                $candidates[] = ['club_id' => $clubId];
            }
            $category['candidates'] = $candidates;
            unset($category['player_ids'], $category['club_ids']);
        }
        unset($category);

        return $data;
    }
}
