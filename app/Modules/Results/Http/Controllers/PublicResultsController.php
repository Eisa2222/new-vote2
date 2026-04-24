<?php

declare(strict_types=1);

namespace App\Modules\Results\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Domain\ResultVisibilityRule;
use App\Modules\Results\Enums\ResultStatus;
use App\Modules\Results\Models\CampaignResult;
use Illuminate\Contracts\View\View;

final class PublicResultsController extends Controller
{
    /**
     * Public announcements index — lists every campaign whose result
     * is publicly visible. Used as the main landing for the archive
     * of announced voting winners.
     */
    public function index(ResultVisibilityRule $vis): View
    {
        $announced = CampaignResult::query()
            ->whereIn('status', [ResultStatus::Announced->value])
            ->with([
                'campaign',
                'items' => fn ($q) => $q->where('is_winner', true)->orderBy('rank'),
                'items.candidate.player.club',
                'items.candidate.club',
                'items.category',
            ])
            ->orderByDesc('announced_at')
            ->get()
            // Filter through the ResultVisibilityRule so we respect whatever
            // per-campaign toggle the committee set; avoids leaking "hidden"
            // results that were technically announced but later hidden.
            ->filter(fn (CampaignResult $r) => $vis->isPublic($r->campaign, $r))
            ->values();

        return view('results::public_index', ['results' => $announced]);
    }

    public function show(string $token, ResultVisibilityRule $vis): View
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        $result   = CampaignResult::where('campaign_id', $campaign->id)
            ->with('items.candidate.player.club', 'items.candidate.club', 'items.category')
            ->first();

        // When results aren't public yet, show a friendly "coming soon"
        // page instead of a 404. The campaign itself is already public
        // (existence known via /campaigns/{token}/stats), so 404 would
        // be misleading anyway. We only reveal whether voting is still
        // running or has closed — no ranking data leaks.
        if (! $vis->isPublic($campaign, $result)) {
            return view('results::coming_soon', [
                'campaign' => $campaign,
                // Two phases the visitor might be in:
                //   • voting still open → "voting hasn't ended yet"
                //   • voting closed but committee hasn't announced → "being reviewed"
                'phase' => now()->lt($campaign->end_at) ? 'voting_open' : 'awaiting_announcement',
            ]);
        }

        $view = $campaign->type === CampaignType::TeamOfTheSeason
            ? 'results::public_tots' : 'results::public';

        return view($view, compact('campaign', 'result'));
    }
}
