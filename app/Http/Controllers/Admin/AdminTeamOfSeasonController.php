<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\AttachTeamOfSeasonCandidatesAction;
use App\Modules\Campaigns\Actions\AutoPopulateTeamOfSeasonFromLeagueAction;
use App\Modules\Campaigns\Actions\CreateTeamOfSeasonCampaignAction;
use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Http\Requests\StoreTeamOfSeasonCampaignRequest;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Leagues\Models\League;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminTeamOfSeasonController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Campaign::class);
        return view('admin.tos.create', [
            'default'  => TeamOfSeasonFormation::default(),
            'minLine'  => TeamOfSeasonFormation::MIN_LINE,
            'maxLine'  => TeamOfSeasonFormation::MAX_LINE,
            'outfield' => TeamOfSeasonFormation::OUTFIELD_TOTAL,
            'leagues'  => League::active()->with('sport')->orderBy('name_en')->get(),
        ]);
    }

    public function store(
        StoreTeamOfSeasonCampaignRequest $request,
        CreateTeamOfSeasonCampaignAction $create,
        AutoPopulateTeamOfSeasonFromLeagueAction $autoPopulate,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $campaign = $create->execute($data);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['formation' => $e->getMessage()]);
        }

        $summary = ['attached' => 0, 'no_clubs' => false, 'no_active_players' => false];
        if ($request->wantsAutoPopulate()) {
            $summary = $autoPopulate->execute($campaign, (int) $data['league_id']);
        }

        return redirect("/admin/tos/{$campaign->id}/candidates")
            ->with('success', $this->autoPopulateMessage($summary));
    }

    private function autoPopulateMessage(array $summary): string
    {
        if ($summary['attached'] > 0) {
            return __('Campaign created and :n players auto-attached from the league.', ['n' => $summary['attached']]);
        }
        if ($summary['no_clubs']) {
            return __('Campaign created. The selected league has NO clubs linked to it, so no players were auto-attached. Link clubs to the league from Settings → Leagues, or add candidates manually below.');
        }
        if ($summary['no_active_players']) {
            return __('Campaign created. The league has clubs but no active players with positions set. Add candidates manually below.');
        }
        return __('Campaign created. Now attach the candidates for each line.');
    }

    public function candidates(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $campaign->load('categories.candidates.player.club');

        $alreadyAttached = $campaign->categories
            ->flatMap->candidates
            ->pluck('player_id')
            ->filter()
            ->all();

        $availableByPosition = [];
        foreach (PlayerPosition::cases() as $pos) {
            $availableByPosition[$pos->value] = Player::active()
                ->with('club')
                ->where('position', $pos->value)
                ->whereNotIn('id', $alreadyAttached)
                ->orderBy('name_en')
                ->get();
        }

        return view('admin.tos.candidates', [
            'campaign'            => $campaign,
            'availableByPosition' => $availableByPosition,
            'formation'           => TeamOfSeasonFormation::fromCampaign($campaign),
        ]);
    }

    /**
     * Unified attach: accepts player_ids[] across all positions and routes
     * each player to the line that matches their position. Backward
     * compatible — also accepts the legacy {category_id, player_ids[]} payload.
     */
    public function attachCandidates(
        Request $request,
        Campaign $campaign,
        AttachTeamOfSeasonCandidatesAction $action,
    ): RedirectResponse {
        $this->authorize('update', $campaign);

        // Legacy payload (single category)
        if ($request->filled('category_id')) {
            $data = $request->validate([
                'category_id'  => ['required', 'integer', 'exists:voting_categories,id'],
                'player_ids'   => ['required', 'array', 'min:1'],
                'player_ids.*' => ['integer', 'exists:players,id'],
            ]);
            $category = $campaign->categories()->findOrFail($data['category_id']);
            try {
                $added = $action->execute($campaign, $category, $data['player_ids']);
                return back()->with('success', __(':n candidates added.', ['n' => $added]));
            } catch (\DomainException $e) {
                return back()->withErrors(['player_ids' => $e->getMessage()]);
            }
        }

        // New unified payload — auto-route by position
        $data = $request->validate([
            'player_ids'   => ['required', 'array', 'min:1'],
            'player_ids.*' => ['integer', 'exists:players,id'],
        ]);

        $players = Player::active()->whereIn('id', $data['player_ids'])->get();
        $byPosition = $players->groupBy(fn ($p) => $p->position?->value);
        $totalAttached = 0;

        foreach ($byPosition as $slot => $group) {
            $category = $campaign->categories()->where('position_slot', $slot)->first();
            if (! $category) continue;
            try {
                $totalAttached += $action->execute($campaign, $category, $group->pluck('id')->all());
            } catch (\DomainException $e) {
                return back()->withErrors(['player_ids' => $e->getMessage()]);
            }
        }

        return back()->with('success', __(':n candidates added.', ['n' => $totalAttached]));
    }
}
