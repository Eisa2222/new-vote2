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
 * Creates a Team of the Season campaign + seeds the line categories.
 * The admin supplies attack/midfield/defense; goalkeeper is fixed.
 * Formation is validated against TeamOfSeasonFormation rules.
 */
final class CreateTeamOfSeasonCampaignAction
{
    /**
     * Category titles per line. Kept in the Action layer (not the
     * Domain class) because they're presentational text — the
     * domain doesn't care what the admin labels a line.
     *
     * @var array<string, array<string,string>>
     */
    private const LINE_TITLES = [
        'goalkeeper' => ['ar' => 'حارس المرمى', 'en' => 'Goalkeeper'],
        'defense'    => ['ar' => 'خط الدفاع',    'en' => 'Defense Line'],
        'midfield'   => ['ar' => 'خط الوسط',     'en' => 'Midfield Line'],
        'attack'     => ['ar' => 'خط الهجوم',    'en' => 'Attack Line'],
    ];

    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(array $data): Campaign
    {
        $defaults  = TeamOfSeasonFormation::default();
        $formation = [
            'attack'     => (int) ($data['attack']   ?? $defaults['attack']),
            'midfield'   => (int) ($data['midfield'] ?? $defaults['midfield']),
            'defense'    => (int) ($data['defense']  ?? $defaults['defense']),
            'goalkeeper' => TeamOfSeasonFormation::goalkeeperCount(),
        ];
        TeamOfSeasonFormation::validate($formation);

        return DB::transaction(function () use ($data, $formation) {
            $campaign = Campaign::create([
                'title_ar'       => $data['title_ar'],
                'title_en'       => $data['title_en'],
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'type'           => CampaignType::TeamOfTheSeason->value,
                'league_id'      => $data['league_id'] ?? null,
                'start_at'       => $data['start_at'],
                'end_at'         => $data['end_at'],
                'max_voters'     => $data['max_voters'] ?? null,
                'status'         => CampaignStatus::Draft->value,
                'created_by'     => Auth::id(),
            ]);

            $order = 0;
            foreach (TeamOfSeasonFormation::LINE_ORDER as $slot) {
                $campaign->categories()->create([
                    'title_ar'       => self::LINE_TITLES[$slot]['ar'],
                    'title_en'       => self::LINE_TITLES[$slot]['en'],
                    'category_type'  => CategoryType::Lineup->value,
                    'position_slot'  => $slot,
                    'required_picks' => $formation[$slot],
                    'selection_min'  => $formation[$slot],
                    'selection_max'  => $formation[$slot],
                    'is_active'      => true,
                    'display_order'  => $order++,
                ]);
            }

            $this->log->execute('tos.campaigns.created', $campaign, ['formation' => $formation]);
            return $campaign->load('categories');
        });
    }
}
