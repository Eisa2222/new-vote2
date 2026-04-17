<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTeamOfSeasonCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('campaigns.create');
    }

    public function rules(): array
    {
        return [
            'title_ar'       => ['required', 'string', 'max:180'],
            'title_en'       => ['required', 'string', 'max:180'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'league_id'      => ['nullable', 'integer', 'exists:leagues,id'],
            'auto_populate'  => ['nullable', 'boolean'],
            'start_at'       => ['required', 'date'],
            'end_at'         => ['required', 'date', 'after:start_at'],
            'max_voters'     => ['nullable', 'integer', 'min:1'],
            'attack'         => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
            'midfield'       => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
            'defense'        => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
        ];
    }

    public function wantsAutoPopulate(): bool
    {
        return $this->boolean('auto_populate') && !empty($this->input('league_id'));
    }
}
