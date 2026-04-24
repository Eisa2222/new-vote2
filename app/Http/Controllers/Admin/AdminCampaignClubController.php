<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Voting\Actions\Club\GenerateCampaignClubLinksAction;
use App\Modules\Voting\Models\CampaignClub;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin panel for managing which clubs participate in a campaign —
 * the source of the new flow's "one link per club" feature.
 *
 *   GET    /admin/campaigns/{campaign}/clubs
 *   POST   /admin/campaigns/{campaign}/clubs                  (attach)
 *   PATCH  /admin/campaigns/{campaign}/clubs/{row}            (edit max/active)
 *   POST   /admin/campaigns/{campaign}/clubs/{row}/regenerate (new token)
 *   DELETE /admin/campaigns/{campaign}/clubs/{row}
 */
final class AdminCampaignClubController extends Controller
{
    public function index(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        // League list + per-club league pivot, so the admin can filter
        // the attach-clubs grid by league and bulk-tick all clubs
        // from a single league without hand-picking one by one.
        $leagues = \App\Modules\Leagues\Models\League::orderBy('name_en')->get(['id', 'name_ar', 'name_en']);
        $clubLeagues = \Illuminate\Support\Facades\DB::table('club_league')
            ->select('club_id', 'league_id')
            ->get()
            ->groupBy('club_id')
            ->map(fn ($g) => $g->pluck('league_id')->all())
            ->toArray();

        return view('admin.campaign-clubs.index', [
            'campaign'    => $campaign,
            'leagues'     => $leagues,
            'clubLeagues' => $clubLeagues,
            'rows'     => $campaign->campaignClubs()->with('club')->get(),
            'allClubs' => Club::orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request, Campaign $campaign, GenerateCampaignClubLinksAction $action): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'club_ids'    => ['required', 'array', 'min:1'],
            'club_ids.*'  => ['integer', 'exists:clubs,id'],
            'max_voters'  => ['nullable', 'integer', 'min:1'],
        ]);

        // Merge with existing rows — never shrinks the list by accident
        $existing = $campaign->campaignClubs()->pluck('club_id')->all();
        $merged   = array_values(array_unique(array_merge($existing, $data['club_ids'])));

        // Laravel's `validate(['integer'])` rule checks the TYPE of the
        // input but does NOT cast — the value stays a string. The action
        // signature is `?int`, so cast explicitly here (empty → null).
        $maxPerClub = isset($data['max_voters']) && $data['max_voters'] !== ''
            ? (int) $data['max_voters']
            : null;

        $action->execute($campaign, $merged, $maxPerClub);

        return back()->with('success', __('Club links generated.'));
    }

    public function update(Request $request, Campaign $campaign, CampaignClub $row): RedirectResponse
    {
        $this->authorize('update', $campaign);
        abort_unless($row->campaign_id === $campaign->id, 404);

        $data = $request->validate([
            'max_voters' => ['nullable', 'integer', 'min:1'],
            'is_active'  => ['nullable', 'boolean'],
        ]);
        $row->update([
            // Same string-vs-int gotcha as store() — cast explicitly so
            // the DB gets a proper NULL / int value, not '5' / ''.
            'max_voters' => (isset($data['max_voters']) && $data['max_voters'] !== '')
                ? (int) $data['max_voters']
                : null,
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', __('Club link updated.'));
    }

    public function regenerate(Campaign $campaign, CampaignClub $row): RedirectResponse
    {
        $this->authorize('update', $campaign);
        abort_unless($row->campaign_id === $campaign->id, 404);

        // Rotating the token invalidates every previously-shared URL
        // for this club — useful if a link leaks publicly.
        $row->update(['voting_link_token' => CampaignClub::generateUniqueToken()]);
        return back()->with('success', __('A new voting link has been generated.'));
    }

    public function destroy(Campaign $campaign, CampaignClub $row): RedirectResponse
    {
        $this->authorize('update', $campaign);
        abort_unless($row->campaign_id === $campaign->id, 404);

        // Soft-disable instead of delete when votes already reference
        // the row. The global FK handler turns a constraint error into
        // a friendly flash, but this is clearer UX.
        if ($campaign->votes()->where('campaign_club_id', $row->id)->exists()) {
            $row->update(['is_active' => false]);
            return back()->with('success', __('Club link disabled (votes already recorded).'));
        }
        $row->delete();
        return back()->with('success', __('Club link removed.'));
    }
}
