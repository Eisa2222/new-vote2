<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;

final class AttachCategoryToCampaignAction
{
    public function execute(Campaign $campaign, array $data): VotingCategory
    {
        return $campaign->categories()->create([
            'title_ar'       => $data['title_ar'],
            'title_en'       => $data['title_en'],
            'category_type'  => $data['category_type']  ?? 'single_choice',
            'position_slot'  => $data['position_slot']  ?? 'any',
            'required_picks' => $data['required_picks'] ?? 1,
            'selection_min'  => $data['selection_min']  ?? $data['required_picks'] ?? 1,
            'selection_max'  => $data['selection_max']  ?? $data['required_picks'] ?? 1,
            'is_active'      => $data['is_active']      ?? true,
            'display_order'  => $data['display_order']  ?? $campaign->categories()->max('display_order') + 1,
        ]);
    }
}
