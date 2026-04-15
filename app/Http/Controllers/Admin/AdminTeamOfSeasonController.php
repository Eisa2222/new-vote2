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
        return view('admin.tos.create');
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
        ]);

        $campaign = $action->execute($data);
        return redirect("/admin/tos/{$campaign->id}/candidates")
            ->with('success', __('Campaign created. Now attach the candidates for each line.'));
    }

    public function candidates(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $campaign->load('categories.candidates.player.club');

        $byPosition = [];
        foreach (PlayerPosition::cases() as $pos) {
            $byPosition[$pos->value] = Player::active()
                ->with('club')->where('position', $pos->value)
                ->orderBy('name_en')->get();
        }

        return view('admin.tos.candidates', [
            'campaign'   => $campaign,
            'byPosition' => $byPosition,
            'formation'  => TeamOfSeasonFormation::MAP,
        ]);
    }

    public function attachCandidates(
        Request $request,
        Campaign $campaign,
        AttachTeamOfSeasonCandidatesAction $action,
    ): RedirectResponse {
        $this->authorize('update', $campaign);
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:voting_categories,id'],
            'player_ids'  => ['required', 'array', 'min:1'],
            'player_ids.*'=> ['integer', 'exists:players,id'],
        ]);

        $category = $campaign->categories()->findOrFail($data['category_id']);

        try {
            $added = $action->execute($campaign, $category, $data['player_ids']);
            return back()->with('success', __(':n candidates added.', ['n' => $added]));
        } catch (\DomainException $e) {
            return back()->withErrors(['player_ids' => $e->getMessage()]);
        }
    }
}
