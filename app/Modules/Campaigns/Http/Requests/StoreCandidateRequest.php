<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Enums\CandidateType;
use App\Modules\Campaigns\Models\VotingCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var VotingCategory|null $category */
        $category = $this->route('category');
        return $category !== null
            && $this->user()?->can('update', $category->campaign);
    }

    public function rules(): array
    {
        return [
            'candidate_type' => ['required', new Enum(CandidateType::class)],
            'candidate_id'   => ['required', 'integer'],
        ];
    }
}
