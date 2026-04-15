<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\ActivateVotingCampaignAction;
use App\Modules\Campaigns\Actions\ArchiveVotingCampaignAction;
use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Actions\CreateVotingCampaignAction;
use App\Modules\Campaigns\Actions\PublishVotingCampaignAction;
use App\Modules\Voting\Services\LiveVoterCountService;
use Illuminate\Http\JsonResponse;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminCampaignController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);
        $campaigns = Campaign::withCount('votes')->orderByDesc('id')->paginate(15);
        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);
        return view('admin.campaigns.form', [
            'types'   => CampaignType::cases(),
            'players' => Player::with('club')->orderBy('name_en')->get(),
            'clubs'   => Club::orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request, CreateVotingCampaignAction $action): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'title_ar'       => ['required', 'string', 'max:180'],
            'title_en'       => ['required', 'string', 'max:180'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'type'           => ['required', 'in:individual_award,team_award,team_of_the_season'],
            'start_at'       => ['required', 'date'],
            'end_at'         => ['required', 'date', 'after:start_at'],
            'max_voters'     => ['nullable', 'integer', 'min:1'],

            'categories'                        => ['required', 'array', 'min:1'],
            'categories.*.title_ar'             => ['required', 'string', 'max:180'],
            'categories.*.title_en'             => ['required', 'string', 'max:180'],
            'categories.*.position_slot'        => ['required', 'in:attack,midfield,defense,goalkeeper,any'],
            'categories.*.required_picks'       => ['required', 'integer', 'min:1', 'max:11'],
            'categories.*.player_ids'           => ['array'],
            'categories.*.player_ids.*'         => ['integer', 'exists:players,id'],
            'categories.*.club_ids'             => ['array'],
            'categories.*.club_ids.*'           => ['integer', 'exists:clubs,id'],
        ]);

        // Reshape candidates for the Action
        foreach ($data['categories'] as &$cat) {
            $cand = [];
            foreach ($cat['player_ids'] ?? [] as $pid) $cand[] = ['player_id' => $pid];
            foreach ($cat['club_ids']   ?? [] as $cid) $cand[] = ['club_id'   => $cid];
            $cat['candidates'] = $cand;
            unset($cat['player_ids'], $cat['club_ids']);
        }
        unset($cat);

        try {
            $campaign = $action->execute($data);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['categories' => $e->getMessage()]);
        }

        return redirect('/admin/campaigns/'.$campaign->id)->with('success', __('Campaign created.'));
    }

    public function show(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);
        $campaign->load('categories.candidates.player.club', 'categories.candidates.club')->loadCount('votes');
        return view('admin.campaigns.show', compact('campaign'));
    }

    public function publish(Campaign $campaign, PublishVotingCampaignAction $a): RedirectResponse
    {
        $this->authorize('publish', $campaign);
        try {
            $a->execute($campaign);
            return back()->with('success', __('Campaign published.'));
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function close(Campaign $campaign, CloseVotingCampaignAction $a): RedirectResponse
    {
        $this->authorize('close', $campaign);
        try {
            $a->execute($campaign);
            return back()->with('success', __('Campaign closed.'));
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function activate(Campaign $campaign, ActivateVotingCampaignAction $a): RedirectResponse
    {
        $this->authorize('publish', $campaign);
        try {
            $a->execute($campaign);
            return back()->with('success', __('Campaign activated.'));
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function archive(Campaign $campaign, ArchiveVotingCampaignAction $a): RedirectResponse
    {
        $this->authorize('update', $campaign);
        try {
            $a->execute($campaign);
            return back()->with('success', __('Campaign archived.'));
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    /** Live stats JSON for admin dashboard polling. */
    public function stats(Campaign $campaign, LiveVoterCountService $counter): JsonResponse
    {
        $this->authorize('view', $campaign);
        return response()->json(['data' => $counter->stats($campaign)]);
    }
}
