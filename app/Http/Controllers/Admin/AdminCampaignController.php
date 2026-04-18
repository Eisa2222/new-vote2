<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\ActivateVotingCampaignAction;
use App\Modules\Campaigns\Actions\ApproveCampaignAction;
use App\Modules\Campaigns\Actions\ArchiveVotingCampaignAction;
use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Actions\CreateVotingCampaignAction;
use App\Modules\Campaigns\Actions\DeleteCampaignAction;
use App\Modules\Campaigns\Actions\PublishVotingCampaignAction;
use App\Modules\Campaigns\Actions\RejectCampaignAction;
use App\Modules\Campaigns\Actions\SubmitCampaignForApprovalAction;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Http\Requests\StoreCampaignRequest;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Leagues\Models\League;
use App\Modules\Players\Models\Player;
use App\Modules\Voting\Services\LiveVoterCountService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminCampaignController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::withCount('votes')
            ->orderByDesc('id')
            ->paginate(config('voting.pagination.campaigns'));

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('admin.campaigns.form', [
            'types'   => CampaignType::cases(),
            'players' => Player::with('club')->orderBy('name_en')->get(),
            'clubs'   => Club::orderBy('name_en')->get(),
            'leagues' => League::active()->with('sport')->orderBy('name_en')->get(),
        ]);
    }

    public function store(StoreCampaignRequest $request, CreateVotingCampaignAction $creator): RedirectResponse
    {
        try {
            $campaign = $creator->execute($request->toActionPayload());
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.campaigns.create')
                ->withInput()
                ->withErrors(['categories' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with('success', __('Campaign created.'));
    }

    public function show(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $campaign
            ->load(['categories.candidates.player.club', 'categories.candidates.club'])
            ->loadCount('votes');

        return view('admin.campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $this->assertEditable($campaign);

        return view('admin.campaigns.edit', [
            'campaign' => $campaign,
            'types'    => CampaignType::cases(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);
        $this->assertEditable($campaign);

        $data = $request->validate([
            'title_ar'       => ['required', 'string', 'max:180'],
            'title_en'       => ['required', 'string', 'max:180'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'type'           => ['required', 'in:individual_award,team_award,team_of_the_season'],
            'start_at'       => ['required', 'date'],
            'end_at'         => ['required', 'date', 'after:start_at'],
            'max_voters'     => ['nullable', 'integer', 'min:1'],
        ]);

        $campaign->update($data);

        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with('success', __('Campaign updated.'));
    }

    public function publish(Campaign $campaign, PublishVotingCampaignAction $publisher): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'publish',
            fn () => $publisher->execute($campaign),
            __('Campaign published.'),
        );
    }

    public function activate(Campaign $campaign, ActivateVotingCampaignAction $activator): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'publish',
            fn () => $activator->execute($campaign),
            __('Campaign activated.'),
        );
    }

    public function close(Campaign $campaign, CloseVotingCampaignAction $closer): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'close',
            fn () => $closer->execute($campaign),
            __('Campaign closed.'),
        );
    }

    public function archive(Campaign $campaign, ArchiveVotingCampaignAction $archiver): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'update',
            fn () => $archiver->execute($campaign),
            __('Campaign archived.'),
        );
    }

    /** Live stats JSON for admin dashboard polling. */
    public function stats(Campaign $campaign, LiveVoterCountService $counter): JsonResponse
    {
        $this->authorize('view', $campaign);
        return response()->json(['data' => $counter->stats($campaign)]);
    }

    // ─── Committee approval flow ─────────────────────────────────

    public function submitForApproval(Campaign $campaign, SubmitCampaignForApprovalAction $submitter): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'submitApproval',
            fn () => $submitter->execute($campaign),
            __('Campaign submitted to committee for approval.'),
        );
    }

    public function approve(Campaign $campaign, ApproveCampaignAction $approver): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'approve',
            fn () => $approver->execute($campaign),
            __('Campaign approved. It is now Published.'),
        );
    }

    public function reject(Request $request, Campaign $campaign, RejectCampaignAction $rejecter): RedirectResponse
    {
        return $this->runLifecycleAction($campaign, 'approve',
            fn () => $rejecter->execute($campaign, $request->input('reason')),
            __('Campaign rejected. The admin can edit and resubmit.'),
        );
    }

    public function destroy(Campaign $campaign, DeleteCampaignAction $deleter): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        try {
            $deleter->execute($campaign);
            return redirect()
                ->route('admin.campaigns.index')
                ->with('success', __('Campaign deleted.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.campaigns.show', $campaign)
                ->withErrors(['delete' => $exception->getMessage()]);
        }
    }

    // ─── Internals ───────────────────────────────────────────────

    /**
     * Runs a lifecycle callback (publish / activate / close / approve / …)
     * with the standard try/authorize/redirect envelope, so the individual
     * controller actions can stay one-liners.
     */
    private function runLifecycleAction(
        Campaign $campaign,
        string $ability,
        \Closure $action,
        string $successMessage,
    ): RedirectResponse {
        $this->authorize($ability, $campaign);

        try {
            $action();
            return redirect()
                ->route('admin.campaigns.show', $campaign)
                ->with('success', $successMessage);
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.campaigns.show', $campaign)
                ->withErrors(['status' => $exception->getMessage()]);
        }
    }

    private function assertEditable(Campaign $campaign): void
    {
        abort_unless(
            $campaign->status === CampaignStatus::Draft,
            403,
            __('Only draft campaigns can be edited.'),
        );
    }
}
