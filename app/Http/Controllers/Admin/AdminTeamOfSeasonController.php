<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\AttachTeamOfSeasonCandidatesAction;
use App\Modules\Campaigns\Actions\CreateTeamOfSeasonCampaignAction;
use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;
use App\Modules\Campaigns\Models\Campaign;
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
        ]);
    }

    public function store(Request $request, CreateTeamOfSeasonCampaignAction $action): RedirectResponse
    {
        $this->authorize('create', Campaign::class);
        $data = $request->validate([
            'title_ar'       => ['required', 'string', 'max:180'],
            'title_en'       => ['required', 'string', 'max:180'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'start_at'       => ['required', 'date'],
            'end_at'         => ['required', 'date', 'after:start_at'],
            'max_voters'     => ['nullable', 'integer', 'min:1'],
            'attack'         => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
            'midfield'       => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
            'defense'        => ['required', 'integer', 'min:'.TeamOfSeasonFormation::MIN_LINE, 'max:'.TeamOfSeasonFormation::MAX_LINE],
        ]);

        try {
            $campaign = $action->execute($data);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['formation' => $e->getMessage()]);
        }

        return redirect("/admin/tos/{$campaign->id}/candidates")
            ->with('success', __('Campaign created. Now attach the candidates for each line.'));
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
