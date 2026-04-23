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
     * Rules match the UI form shape. Categories are OPTIONAL — the
     * new club-scoped create form no longer ships a Questions section;
     * admins pick between:
     *   • leave categories empty → voter ballot renders the 3 fixed
     *     awards (Best Saudi / Best Foreign / TOS) with candidates
     *     selected by nationality/position.
     *   • attach voting_categories with `award_type` → those act as
     *     curated shortlists per award on the ballot.
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
            // Club-scoped voting rules (unchecked checkbox posts the
            // paired hidden "0" → boolean cast on the Campaign model).
            'allow_self_vote'                   => ['nullable', 'boolean'],
            'allow_teammate_vote'               => ['nullable', 'boolean'],

            // Categories OPTIONAL. When absent the CreateVotingCampaign
            // action just creates the campaign without any questions.
            'categories'                        => ['nullable', 'array'],
            'categories.*.title_ar'             => ['required_with:categories', 'string', 'max:180'],
            'categories.*.title_en'             => ['required_with:categories', 'string', 'max:180'],
            'categories.*.position_slot'        => ['required_with:categories', 'in:attack,midfield,defense,goalkeeper,any'],
            'categories.*.award_type'           => ['nullable', 'in:best_saudi,best_foreign,team_of_the_season'],
            'categories.*.required_picks'       => ['required_with:categories', 'integer', 'min:1', 'max:11'],
            'categories.*.player_ids'           => ['array'],
            'categories.*.player_ids.*'         => ['integer', 'exists:players,id'],
            'categories.*.club_ids'             => ['array'],
            'categories.*.club_ids.*'           => ['integer', 'exists:clubs,id'],
        ];
    }

    /**
     * Reshape the validated data into the `{category, candidates[]}`
     * structure the CreateVotingCampaignAction expects. Categories
     * default to [] so downstream code never has to null-check.
     *
     * @return array<string,mixed>
     */
    public function toActionPayload(): array
    {
        $data = $this->validated();
        $data['categories'] = $data['categories'] ?? [];

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
