<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Services\CampaignAvailabilityService;
use App\Modules\Voting\Actions\CheckVoterSessionAction;
use App\Modules\Voting\Actions\CreateVoterSessionAction;
use App\Modules\Voting\Actions\GetPublicCampaignAction;
use App\Modules\Voting\Actions\PreventDuplicateVoteByPlayerAction;
use App\Modules\Voting\Actions\SubmitTeamOfSeasonVoteAction;
use App\Modules\Voting\Actions\SubmitVoteAction;
use App\Modules\Voting\Actions\VerifyVoterIdentityAction;
use App\Modules\Voting\Http\Requests\SubmitTeamOfSeasonVoteRequest;
use App\Modules\Voting\Http\Requests\SubmitVoteRequest;
use App\Modules\Voting\Http\Requests\VerifyVoterRequest;
use App\Modules\Voting\Support\IdentityNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublicVoteController extends Controller
{
    /**
     * Public directory of campaigns that are currently open for voting.
     * Each card links to /vote/{token} which handles the verify-then-vote
     * flow.
     */
    public function index(CampaignAvailabilityService $avail): View
    {
        // Candidates shown publicly: Active (voting is live) and Published
        // (approved, waiting for start_at). Anything Draft / PendingApproval
        // / Rejected / Closed / Archived is hidden.
        $candidates = Campaign::query()
            ->whereIn('status', ['active', 'published'])
            ->withCount('votes')
            ->orderByDesc('start_at')
            ->get();

        // Filter out anything the availability service says isn't actually
        // voteable (e.g. end_at in past, max_voters hit). We still keep
        // "not started yet" so upcoming campaigns are visible as teasers.
        $open     = $candidates->filter(fn ($c) => $avail->reasonFor($c) === CampaignAvailabilityService::OK)->values();
        $upcoming = $candidates->filter(fn ($c) => $avail->reasonFor($c) === CampaignAvailabilityService::NOT_STARTED)->values();

        return view('voting::public_index', compact('open', 'upcoming'));
    }

    public function show(string $token, CheckVoterSessionAction $check): View|RedirectResponse
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();

        if ($unavailable = $this->unavailableView($campaign)) {
            return $unavailable;
        }

        if ($check->execute($campaign)) {
            return redirect()->route('voting.form', $token);
        }
        return view('voting::verify', compact('campaign'));
    }

    /**
     * Returns a rendered "campaign unavailable" view if the campaign
     * can't accept votes right now; null when voting is open.
     * Keeps the three public endpoints (show, verify, form) DRY.
     */
    private function unavailableView(Campaign $campaign): ?View
    {
        $reason = app(CampaignAvailabilityService::class)->reasonFor($campaign);
        if ($reason === CampaignAvailabilityService::OK) {
            return null;
        }
        return view('voting::unavailable', compact('campaign', 'reason'));
    }

    public function verify(
        string $token,
        VerifyVoterRequest $request,
        VerifyVoterIdentityAction $verify,
        CreateVoterSessionAction $create,
        PreventDuplicateVoteByPlayerAction $dup,
    ): RedirectResponse|View {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();

        // Campaign might have filled up between the time the voter opened
        // the page and the time they submitted the identity form.
        if ($unavailable = $this->unavailableView($campaign)) {
            return $unavailable;
        }

        try {
            $result = $verify->execute(
                $request->input('national_id'),
                $request->input('mobile'),
            );
        } catch (\App\Modules\Voting\Exceptions\VotingException $e) {
            return back()->withInput()->withErrors(['identity' => $e->getMessage()]);
        }

        if ($dup->hasVoted($campaign, $result['player']->id)) {
            return back()->withErrors(['identity' => __('You have already voted in this campaign.')]);
        }

        $create->execute($campaign, $result['player'], $result['method'], $result['value']);
        return redirect()->route('voting.form', $token);
    }

    public function form(string $token, CheckVoterSessionAction $check, GetPublicCampaignAction $loader): View|RedirectResponse
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();

        if ($unavailable = $this->unavailableView($campaign)) {
            return $unavailable;
        }

        $session  = $check->execute($campaign);
        if (! $session) {
            return redirect()->route('voting.show', $token);
        }

        $campaign = $loader->execute($token);
        $voter = [
            'masked' => IdentityNormalizer::mask((string) $session['value']),
            'method' => $session['method'],
        ];

        $view = $campaign->type === CampaignType::TeamOfTheSeason ? 'voting::tos' : 'voting::public';
        return view($view, compact('campaign', 'voter'));
    }

    public function submit(
        string $token,
        Request $request,
        SubmitVoteAction $action,
        SubmitTeamOfSeasonVoteAction $tosAction,
        CreateVoterSessionAction $session,
    ): RedirectResponse|View {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();

        // Race: another voter may have been the one to hit max_voters
        // between the form loading and this POST. Show the same friendly
        // page instead of a raw 410.
        if ($unavailable = $this->unavailableView($campaign)) {
            return $unavailable;
        }

        $submittedLineup = null;

        try {
            if ($campaign->type === CampaignType::TeamOfTheSeason) {
                /** @var SubmitTeamOfSeasonVoteRequest $tosReq */
                $tosReq = app(SubmitTeamOfSeasonVoteRequest::class);
                $payload = $tosReq->validated();
                $tosAction->execute($campaign, $request, [
                    'attack'     => $payload['attack'],
                    'midfield'   => $payload['midfield'],
                    'defense'    => $payload['defense'],
                    'goalkeeper' => $payload['goalkeeper'],
                ]);
                $submittedLineup = [
                    'attack'     => $payload['attack'],
                    'midfield'   => $payload['midfield'],
                    'defense'    => $payload['defense'],
                    'goalkeeper' => $payload['goalkeeper'],
                ];
            } else {
                /** @var SubmitVoteRequest $regReq */
                $regReq = app(SubmitVoteRequest::class);
                $action->execute($campaign, $request, $regReq->validated('selections'));
            }
        } catch (\App\Modules\Voting\Exceptions\VotingException $e) {
            return back()->withErrors(['voting' => $e->getMessage()]);
        }

        $session->clear($campaign);
        $redirect = redirect()->route('voting.thanks', $token);
        if ($submittedLineup) {
            $redirect->with('submittedLineup', $submittedLineup);
        }
        return $redirect;
    }

    public function thanks(string $token): View
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();

        // If this was a TOS vote, hydrate the submitted lineup from flashed session data
        // so the voter can see their picks on a pitch recap.
        $picks = null;
        if ($campaign->type === CampaignType::TeamOfTheSeason
            && ($lineup = session('submittedLineup')) !== null) {
            $picks = $this->resolveSubmittedPlayers($campaign, $lineup);
        }

        return view('voting::thanks', compact('campaign', 'picks'));
    }

    /**
     * @param  array<string, int[]>  $lineup
     * @return array<string, array<\App\Modules\Players\Models\Player>>
     */
    private function resolveSubmittedPlayers(Campaign $campaign, array $lineup): array
    {
        $campaign->loadMissing('categories.candidates.player.club');
        $byCandidate = [];
        foreach ($campaign->categories as $cat) {
            foreach ($cat->candidates as $cand) {
                $byCandidate[$cand->id] = $cand->player;
            }
        }

        $picks = [];
        foreach (['attack', 'midfield', 'defense', 'goalkeeper'] as $slot) {
            $picks[$slot] = [];
            foreach ($lineup[$slot] ?? [] as $id) {
                if (isset($byCandidate[$id])) {
                    $picks[$slot][] = $byCandidate[$id];
                }
            }
        }
        return $picks;
    }
}
