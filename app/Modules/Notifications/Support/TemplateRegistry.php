<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Support;

use App\Modules\Campaigns\Enums\CampaignType;

/**
 * Catalogue of every template key the app understands + the variables
 * each key exposes to the admin while editing its body.
 *
 * Keeping this in one place means the admin UI, the renderer, and the
 * default seeder stay in sync — there is no "runtime surprise" where
 * the editor offers {some.var} that the sender doesn't actually expose.
 */
final class TemplateRegistry
{
    public const EVENTS = [
        'campaign.published' => [
            'label'    => 'Campaign opened (voter invite)',
            'scope'    => 'voter',
            'per_type' => true,
            'vars'     => ['platform.name', 'campaign.title', 'campaign.start_at', 'campaign.end_at', 'campaign.public_url', 'voter.name'],
        ],
        'campaign.closing_soon' => [
            'label'    => 'Campaign closing soon (reminder)',
            'scope'    => 'voter',
            'per_type' => true,
            'vars'     => ['platform.name', 'campaign.title', 'campaign.end_at', 'campaign.public_url', 'voter.name'],
        ],
        'campaign.results_announced' => [
            'label'    => 'Results announced',
            'scope'    => 'voter',
            'per_type' => true,
            'vars'     => ['platform.name', 'campaign.title', 'campaign.public_url', 'winners_list'],
        ],
        'campaign.approved' => [
            'label'    => 'Campaign approved by committee',
            'scope'    => 'admin',
            'per_type' => false,
            'vars'     => ['platform.name', 'campaign.title', 'admin.name'],
        ],
        'campaign.rejected' => [
            'label'    => 'Campaign rejected by committee',
            'scope'    => 'admin',
            'per_type' => false,
            'vars'     => ['platform.name', 'campaign.title', 'admin.name', 'reason'],
        ],
        'user.invited' => [
            'label'    => 'User invitation',
            'scope'    => 'admin',
            'per_type' => false,
            'vars'     => ['platform.name', 'user.name', 'invite.url'],
        ],
    ];

    /** @return array<int,string> list of keys that accept a per-award-type override */
    public static function perTypeKeys(): array
    {
        $keys = [];
        foreach (self::EVENTS as $k => $meta) {
            if (! empty($meta['per_type'])) $keys[] = $k;
        }
        return $keys;
    }

    public static function awardTypes(): array
    {
        // Null = generic; the others mirror the CampaignType enum.
        return array_merge([null], array_map(fn (CampaignType $t) => $t->value, CampaignType::cases()));
    }

    public static function locales(): array
    {
        return ['ar', 'en'];
    }

    public static function knows(string $key): bool
    {
        return isset(self::EVENTS[$key]);
    }

    public static function varsFor(string $key): array
    {
        return self::EVENTS[$key]['vars'] ?? [];
    }
}
