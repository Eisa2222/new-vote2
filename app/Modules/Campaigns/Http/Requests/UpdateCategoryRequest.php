<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Requests;

use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Campaigns\Models\VotingCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateCategoryRequest extends FormRequest
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
            'title_ar'      => ['sometimes', 'string', 'max:180'],
            'title_en'      => ['sometimes', 'string', 'max:180'],
            'category_type' => ['sometimes', new Enum(CategoryType::class)],
            'selection_min' => ['sometimes', 'integer', 'min:1', 'max:11'],
            'selection_max' => ['sometimes', 'integer', 'min:1', 'max:11'],
            'is_active'     => ['boolean'],
        ];
    }
}
