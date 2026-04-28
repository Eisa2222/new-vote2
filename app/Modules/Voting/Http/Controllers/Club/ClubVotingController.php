<?php

declare(strict_types=1);

namespace App\Modules\Voting\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Actions\Club\GetEligibleCandidatesAction;
use App\Modules\Voting\Actions\Club\ResolveClubPlayerSelectionAction;
use App\Modules\Voting\Actions\Club\SaveOptionalVoterProfileAction;
use App\Modules\Voting\Actions\Club\SubmitClubVoteAction;
use App\Modules\Voting\Actions\Club\ValidateClubVotingEntryAction;
use App\Modules\Voting\Enums\AwardType;
use App\Modules\Voting\Exceptions\VotingException;
use App\Modules\Voting\Http\Requests\Club\StoreOptionalVoterProfileRequest;
use App\Modules\Voting\Http\Requests\Club\SubmitClubVoteRequest;
use App\Modules\Voting\Http\Requests\Club\VerifyClubVoterRequest;
use App\Modules\Voting\Models\CampaignClub;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public-facing controller for the club-scoped voting flow.
 *
 *   GET  /vote/club/{token}           entry (select your name)
 *   POST /vote/club/{token}/start     verify player + open ballot
 *   GET  /vote/club/{token}/ballot    ballot (awards + TOS pitch)
 *   POST /vote/club/{token}/submit    persist vote
 *   GET  /vote/club/{token}/success   post-vote success
 *   GET  /vote/club/{token}/profile   optional profile capture
 *   POST /vote/club/{token}/profile   save profile (skippable)
 *
 * Session state for an ongoing voter is stored under the key
 * `club_voter:{token}` so multiple concurrent browser tabs on the
 * same device stay isolated per token.
 */
final class ClubVotingController extends Controller
{
    private const SESSION_TTL_MIN = 30;

    // ─── GET entry ─────────────────────────────────────────────
    public function show(string $token, ResolveClubPlayerSelectionAction $resolve): View
    {
        $row = $this->loadRow($token);
        try {
            app(ValidateClubVotingEntryAction::class)->execute($row);
        } catch (VotingException $e) {
            return view('voting::club.unavailable', [
                'campaign' => $row->campaign,
                'club'     => $row->club,
                'reason'   => $e->getMessage(),
            ]);
        }

        return view('voting::club.entry', [
            'row'     => $row,
            'campaign' => $row->campaign,
            'club'    => $row->club,
            'players' => $resolve->execute($row),
        ]);
    }

    // ─── POST start — verify the dropdown pick ─────────────────
    public function start(VerifyClubVoterRequest $request, string $token): RedirectResponse
    {
        $row = $this->loadRow($token);
        app(ValidateClubVotingEntryAction::class)->execute($row);

        $player = Player::active()->where('id', $request->integer('player_id'))->first();
        abort_unless(
            $player && $player->club_id === $row->club_id,
            422,
            __('The selected player does not belong to this club.')
        );

        // If this player already submitted a vote in this campaign,
        // don't even let them see the ballot again — send them to a
        // dedicated "already voted" view. Previously the duplicate
        // was only caught at submit time, so the ballot (with the
        // full TOS pitch) was reachable and looked like the voter
        // could vote a second time.
        if ($this->hasAlreadyVoted($row->campaign_id, $player->id)) {
            return redirect()->route('voting.club.alreadyVoted', $token);
        }

        // H-4 fix — session fixation. Write the voter state FIRST,
        // THEN rotate the session id (migrate:true is the default and
        // preserves data). Doing it in this order is important: if we
        // regenerate first and the session save on the next line races
        // with the outgoing Set-Cookie header under some drivers, the
        // club_voter:{token} write can land on the old session id and
        // disappear — which shows up for the voter as "ballot takes the
        // vote, submit loops back to the entry page".
        session(["club_voter:$token" => [
            'player_id'  => $player->id,
            'started_at' => now()->toIso8601String(),
        ]]);
        session()->save();

        // Only regenerate the ID (not the token), and only after the
        // voter payload is persisted. migrate:true means data follows.
        $request->session()->migrate(true);

        return redirect()->route('voting.club.ballot', $token);
    }

    // ─── GET ballot ────────────────────────────────────────────
    public function ballot(string $token, GetEligibleCandidatesAction $candidates): View|RedirectResponse
    {
        $row   = $this->loadRow($token);
        $voter = $this->currentVoter($token, $row);

        if ($voter === null) {
            return redirect()->route('voting.club.show', $token);
        }

        // Second guardrail: if the session lingers but the underlying
        // player has already voted (e.g. admin re-seeded, or the user
        // opened two tabs), stop showing the ballot.
        if ($this->hasAlreadyVoted($row->campaign_id, $voter->id)) {
            session()->forget("club_voter:$token");
            return redirect()->route('voting.club.alreadyVoted', $token);
        }

        try {
            app(ValidateClubVotingEntryAction::class)->execute($row);
        } catch (VotingException $e) {
            return view('voting::club.unavailable', [
                'campaign' => $row->campaign,
                'club' => $row->club,
                'reason'   => $e->getMessage(),
                // Forward the voter so the unavailable screen can show
                // their identity card — they stay "signed in".
                'voter'    => $voter ?? null,
            ]);
        }

        // Which awards does this campaign show?
        // Single source of truth on Campaign — see configuredAwards()
        // for the shortlist-mode vs. defaults logic.
        ['saudi' => $showSaudi, 'foreign' => $showForeign, 'tos' => $showTos]
            = $row->campaign->configuredAwards();

        // Group candidates by club_id so the ballot's shared popup can
        // render "clubs → players" for every award (Best Saudi, Best
        // Foreign, and each TOS pitch slot) with a single data shape.
        $saudiByClub   = $showSaudi
            ? $candidates->execute($row->campaign, $voter, AwardType::BestSaudi)->groupBy('club_id')
            : collect();
        $foreignByClub = $showForeign
            ? $candidates->execute($row->campaign, $voter, AwardType::BestForeign)->groupBy('club_id')
            : collect();

        // Flat fallbacks for legacy view code / counts.
        $saudi   = $saudiByClub->flatten(1);
        $foreign = $foreignByClub->flatten(1);

        $tos = [];
        if ($showTos) {
            foreach (PlayerPosition::cases() as $pos) {
                $tos[$pos->value] = $candidates
                    ->execute($row->campaign, $voter, AwardType::TeamOfTheSeason, $pos)
                    ->groupBy('club_id');
            }
        }

        return view('voting::club.ballot', [
            'row'           => $row,
            'campaign'      => $row->campaign,
            'club'          => $row->club,
            'voter'         => $voter,
            'saudi'         => $saudi,
            'foreign'       => $foreign,
            'saudiByClub'   => $saudiByClub,
            'foreignByClub' => $foreignByClub,
            'tos'           => $tos,
            'showSaudi'     => $showSaudi,
            'showForeign'   => $showForeign,
            'showTos'       => $showTos,
        ]);
    }

    // ─── POST submit ───────────────────────────────────────────
    public function submit(SubmitClubVoteRequest $request, string $token, SubmitClubVoteAction $submit): RedirectResponse
    {
        $row   = $this->loadRow($token);
        $voter = $this->currentVoter($token, $row);
        if ($voter === null) {
            return redirect()->route('voting.club.show', $token);
        }

        try {
            $submit->execute($row, $voter, $request->validated(), $request);
        } catch (VotingException $e) {
            return back()->withErrors(['vote' => $e->getMessage()])->withInput();
        }

        // Drop the in-progress voter session, mark the submit so the
        // profile page can fetch the same player, and flash a picks
        // snapshot so the success page can render a confirmation
        // recap (individual awards + TOS pitch).
        session()->forget("club_voter:$token");
        session(["club_voter_done:$token" => $voter->id]);

        return redirect()->route('voting.club.success', $token)
            ->with('submitted_picks', $request->validated());
    }

    // ─── GET success ───────────────────────────────────────────
    public function success(string $token): View
    {
        $row   = $this->loadRow($token);
        $picks = session('submitted_picks');

        $resolved = null;
        if (is_array($picks)) {
            // Turn raw ids into a small, view-friendly object. Done
            // here (not in the view) so Blade stays dumb.
            $saudi   = Player::with('club')->find($picks['best_saudi_player_id']   ?? null);
            $foreign = Player::with('club')->find($picks['best_foreign_player_id'] ?? null);
            $lineup  = [];
            foreach (($picks['lineup'] ?? []) as $slot => $ids) {
                // Preserve the voter's submission ORDER. whereIn()->get()
                // returns by primary-key default, so if the voter picked
                // [10, 5, 7] the view would render [5, 7, 10] and it
                // would look like the lineup was "shuffled". Re-order
                // to match $ids exactly.
                $byId = Player::with('club')->whereIn('id', $ids)->get()->keyBy('id');
                $lineup[$slot] = collect($ids)
                    ->map(fn ($id) => $byId->get((int) $id))
                    ->filter()
                    ->values();
            }
            $resolved = ['saudi' => $saudi, 'foreign' => $foreign, 'lineup' => $lineup];
        }

        // Resolve the voter so the inline contact form on the success
        // page can pre-fill mobile/email if the admin already entered
        // them. session("club_voter_done:$token") was set by submit().
        $playerId = (int) session("club_voter_done:$token");
        $player   = $playerId ? Player::find($playerId) : null;

        return view('voting::club.success', [
            'row'      => $row,
            'campaign' => $row->campaign,
            'club'     => $row->club,
            'picks'    => $resolved,
            'player'   => $player,
        ]);
    }

    // ─── GET profile ───────────────────────────────────────────
    public function profileForm(string $token): View|RedirectResponse
    {
        $row = $this->loadRow($token);
        $playerId = (int) session("club_voter_done:$token");
        if (! $playerId) {
            return redirect()->route('voting.club.show', $token);
        }

        return view('voting::club.profile', [
            'row'      => $row,
            'campaign' => $row->campaign,
            'club'     => $row->club,
            'player'   => Player::find($playerId),
        ]);
    }

    // ─── POST profile ──────────────────────────────────────────
    public function saveProfile(StoreOptionalVoterProfileRequest $request, string $token, SaveOptionalVoterProfileAction $save): RedirectResponse
    {
        $playerId = (int) session("club_voter_done:$token");
        if (! $playerId) {
            return redirect()->route('voting.club.show', $token);
        }

        $player = Player::find($playerId);
        if ($player) {
            $save->execute($player, $request->validated());
        }

        session()->forget("club_voter_done:$token");
        // Land on a clean thank-you screen, NOT the stats dashboard.
        // The voter just gave us their contact details — they want a
        // confirmation, not to be redirected somewhere unfamiliar.
        // The `saved` flag lets the thanks view tailor the headline
        // ("data saved + thank you for voting") vs. the plain skip
        // path ("thank you for voting").
        return redirect()->route('voting.club.thanks', $token)
            ->with('voter_thanks_saved', true);
    }

    // ─── GET thanks ────────────────────────────────────────────
    // Clean post-flow thank-you screen. Reachable from:
    //   • saveProfile() with `voter_thanks_saved` flash → headline
    //     reads "your details are saved + thanks for voting"
    //   • the Finish button on the success page → plain
    //     "thanks for voting"
    // No state required beyond the campaign row, so no session check.
    public function thanks(string $token): View
    {
        $row = $this->loadRow($token);
        return view('voting::club.thanks', [
            'row'      => $row,
            'campaign' => $row->campaign,
            'club'     => $row->club,
            'savedDetails' => (bool) session('voter_thanks_saved', false),
        ]);
    }

    // ─── GET already voted ─────────────────────────────────────
    // Dedicated "you cannot vote twice" view so the voter gets a clear
    // explanation instead of a silent redirect or a confusing reopening
    // of the ballot.
    public function alreadyVoted(string $token): View
    {
        $row = $this->loadRow($token);
        return view('voting::club.already-voted', [
            'row'      => $row,
            'campaign' => $row->campaign,
            'club'     => $row->club,
        ]);
    }

    // ─── helpers ───────────────────────────────────────────────

    private function hasAlreadyVoted(int $campaignId, int $playerId): bool
    {
        return \App\Modules\Voting\Models\Vote::where('campaign_id', $campaignId)
            ->where('player_id', $playerId)
            ->exists();
    }

    private function loadRow(string $token): CampaignClub
    {
        return CampaignClub::with(['campaign', 'club'])
            ->where('voting_link_token', $token)
            ->firstOrFail();
    }

    /** Pulls the "signed-in" voter from the session and re-checks eligibility. */
    private function currentVoter(string $token, CampaignClub $row): ?Player
    {
        $s = session("club_voter:$token");
        if (! $s || empty($s['player_id'])) return null;

        // TTL check — 30 minutes is long enough to build a TOS lineup.
        if (! empty($s['started_at'])) {
            try {
                $t = \Carbon\Carbon::parse($s['started_at']);
                if ($t->lt(now()->subMinutes(self::SESSION_TTL_MIN))) {
                    session()->forget("club_voter:$token");
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        $player = Player::active()->find($s['player_id']);
        if (! $player || $player->club_id !== $row->club_id) {
            session()->forget("club_voter:$token");
            return null;
        }
        return $player;
    }
}
