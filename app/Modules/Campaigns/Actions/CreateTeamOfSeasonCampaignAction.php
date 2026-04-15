<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Creates a Team of the Season campaign + seeds the 4 line categories with
 * the correct selection_min = selection_max = 3|3|4|1 enforced in the schema.
 * No manual category setup is needed after this.
 */
final class CreateTeamOfSeasonCampaignAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            $campaign = Campaign::create([
                'title_ar'       => $data['title_ar'],
                'title_en'       => $data['title_en'],
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'type'           => CampaignType::TeamOfTheSeason->value,
                'start_at'       => $data['start_at'],
                'end_at'         => $data['end_at'],
                'max_voters'     => $data['max_voters'] ?? null,
                'status'         => CampaignStatus::Draft->value,
                'created_by'     => Auth::id(),
            ]);

            $order = 0;
            foreach (TeamOfSeasonFormation::LINE_ORDER as $slot) {
                $count = TeamOfSeasonFormation::MAP[$slot];
                $campaign->categories()->create([
                    'title_ar'       => TeamOfSeasonFormation::lineTitles('ar')[$slot],
                    'title_en'       => TeamOfSeasonFormation::lineTitles('en')[$slot],
                    'category_type'  => CategoryType::Lineup->value,
                    'position_slot'  => $slot,
                    'required_picks' => $count,
                    'selection_min'  => $count,
                    'selection_max'  => $count,
                    'is_active'      => true,
                    'display_order'  => $order++,
                ]);
            }

            $this->log->execute('tos.campaigns.created', $campaign);
            return $campaign->load('categories');
        });
    }
}
