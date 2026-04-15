<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Services\CampaignAvailabilityService;
use App\Modules\Voting\Actions\CheckVoterSessionAction;
use App\Modules\Voting\Actions\CreateVoterSessionAction;
use App\Modules\Voting\Actions\GetPublicCampaignAction;
use App\Modules\Voting\Actions\PreventDuplicateVoteByPlayerAction;
use App\Modules\Voting\Actions\SubmitVoteAction;
use App\Modules\Voting\Actions\VerifyVoterIdentityAction;
use App\Modules\Voting\Http\Requests\SubmitVoteRequest;
use App\Modules\Voting\Http\Requests\VerifyVoterRequest;
use App\Modules\Voting\Support\IdentityNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class PublicVoteController extends Controller
{
    /** Step 1: Show verification screen (or redirect to vote form if already verified). */
    public function show(string $token, CheckVoterSessionAction $check): View|RedirectResponse
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        abort_unless(
            app(CampaignAvailabilityService::class)->isAvailable($campaign),
            410,
            __('This campaign is not open for voting.'),
        );

        if ($check->execute($campaign)) {
            return redirect()->route('voting.form', $token);
        }
        return view('voting::verify', compact('campaign'));
    }

    /** Step 2: Verify identity and create session. */
    public function verify(
        string $token,
        VerifyVoterRequest $request,
        VerifyVoterIdentityAction $verify,
        CreateVoterSessionAction $create,
        PreventDuplicateVoteByPlayerAction $dup,
    ): RedirectResponse {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        abort_unless(
            app(CampaignAvailabilityService::class)->isAvailable($campaign),
            410,
            __('This campaign is not open for voting.'),
        );

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

    /** Step 3: Show the voting form (verified only). */
    public function form(string $token, CheckVoterSessionAction $check, GetPublicCampaignAction $loader): View|RedirectResponse
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        $session  = $check->execute($campaign);

        if (! $session) {
            return redirect()->route('voting.show', $token);
        }

        $campaign = $loader->execute($token);
        $voter = [
            'masked' => IdentityNormalizer::mask((string) $session['value']),
            'method' => $session['method'],
        ];

        return view('voting::public', compact('campaign', 'voter'));
    }

    /** Step 4: Submit the vote. */
    public function submit(
        string $token,
        SubmitVoteRequest $request,
        SubmitVoteAction $action,
        CreateVoterSessionAction $session,
    ): RedirectResponse {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        $action->execute($campaign, $request, $request->validated('selections'));
        $session->clear($campaign);
        return redirect()->route('voting.thanks', $token);
    }

    public function thanks(string $token): View
    {
        $campaign = Campaign::where('public_token', $token)->firstOrFail();
        return view('voting::thanks', compact('campaign'));
    }
}
