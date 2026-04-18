<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->route('campaign');
        return $campaign !== null
            && $this->user()?->can('update', $campaign);
    }

    public function rules(): array
    {
        return [
            'title_ar'      => ['required', 'string', 'max:180'],
            'title_en'      => ['required', 'string', 'max:180'],
            'category_type' => ['required', new Enum(CategoryType::class)],
            'position_slot' => ['required', 'in:attack,midfield,defense,goalkeeper,any'],
            'selection_min' => ['required', 'integer', 'min:1', 'max:11'],
            'selection_max' => ['required', 'integer', 'min:1', 'max:11', 'gte:selection_min'],
            'is_active'     => ['boolean'],
        ];
    }

    /** Returns the data shape the AttachCategoryToCampaignAction expects. */
    public function toActionPayload(): array
    {
        $data = $this->validated();
        $data['required_picks'] = $data['selection_max'];
        return $data;
    }
}
