<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Enums\CampaignType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('campaign')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title_ar'       => ['sometimes', 'required', 'string', 'max:180'],
            'title_en'       => ['sometimes', 'required', 'string', 'max:180'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'type'           => ['sometimes', new Enum(CampaignType::class)],
            'start_at'       => ['sometimes', 'date'],
            'end_at'         => ['sometimes', 'date', 'after:start_at'],
            'max_voters'     => ['nullable', 'integer', 'min:1'],
            'allow_self_vote'     => ['nullable', 'boolean'],
            'allow_teammate_vote' => ['nullable', 'boolean'],
        ];
    }
}
