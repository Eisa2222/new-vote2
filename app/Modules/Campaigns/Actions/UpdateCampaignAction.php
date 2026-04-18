<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Actions;

use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Users\Actions\LogActivityAction;

/**
 * Single source of truth for "is this campaign safe to edit?".
 *
 * Both the admin web controller and the JSON API funnel through here so
 * we cannot have a situation where the UI hides the Edit button while the
 * API still accepts a PUT — that gap was a real audit finding.
 *
 * Editability rule: only Draft and Rejected campaigns can have their
 * core fields (title / dates / type / max_voters) changed. Anything
 * already published, active, closed or archived is frozen — modifying
 * dates after voting started would silently invalidate the audit trail.
 */
final class UpdateCampaignAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(Campaign $campaign, array $data): Campaign
    {
        if (! self::isEditable($campaign)) {
            throw new \DomainException(
                __('Only Draft or Rejected campaigns can be edited. This campaign is :status.', [
                    'status' => $campaign->status->label(),
                ]),
            );
        }

        $before = $campaign->only(['title_ar', 'title_en', 'start_at', 'end_at', 'type', 'max_voters']);
        $campaign->update($data);

        $this->log->execute('campaigns.updated', $campaign, [
            'before' => $before,
            'after'  => $campaign->only(array_keys($before)),
        ]);

        return $campaign->fresh();
    }

    public static function isEditable(Campaign $campaign): bool
    {
        return in_array($campaign->status, [
            CampaignStatus::Draft,
            CampaignStatus::Rejected,
        ], true);
    }
}
