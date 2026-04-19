<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    public function rules(): array
    {
        return [
            'app_name'              => ['required', 'string', 'max:120'],
            'contact_email'         => ['required', 'email'],
            'default_max_voters'    => ['nullable', 'integer', 'min:1'],
            'default_campaign_days' => ['required', 'integer', 'min:1', 'max:365'],
            'committee_name_ar'     => ['required', 'string', 'max:120'],
            'committee_name_en'     => ['required', 'string', 'max:120'],
            // Platform logo — appears on login, admin header, voting pages
            // in place of the default "FPA" wordmark. Raster-only (SVG
            // excluded for XSS reasons, same as club logos).
            'platform_logo'         => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'platform_logo_clear'   => ['nullable', 'boolean'],
        ];
    }
}
