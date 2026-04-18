<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Enums\CampaignStatus;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Actions\AnnounceResultsAction;
use App\Modules\Results\Actions\ApproveResultsAction;
use App\Modules\Results\Actions\CalculateCampaignResultsAction;
use App\Modules\Results\Actions\HideResultsAction;
use App\Modules\Results\Actions\ResolveTieAction;
use App\Modules\Results\Models\CampaignResult;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminResultController extends Controller
{
    /** Statuses whose results are worth opening in the admin. */
    private const RESULT_RELEVANT_STATUSES = [
        CampaignStatus::Active->value,
        CampaignStatus::Closed->value,
        CampaignStatus::Published->value,
    ];

    private function requirePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    public function index(): View
    {
        $this->requirePermission('results.view');

        $campaigns = Campaign::with('result')
            ->whereIn('status', self::RESULT_RELEVANT_STATUSES)
            ->orderByDesc('id')
            ->paginate(config('voting.pagination.results'));

        return view('admin.results.index', compact('campaigns'));
    }

    public function show(Campaign $campaign): View
    {
        $this->requirePermission('results.view');

        $result = $campaign->result()
            ->with(['items.candidate.player.club', 'items.candidate.club', 'items.category'])
            ->first();

        return view('admin.results.show', compact('campaign', 'result'));
    }

    public function calculate(Campaign $campaign, CalculateCampaignResultsAction $calculator): RedirectResponse
    {
        $this->requirePermission('results.calculate');

        $calculator->execute($campaign->load('categories'));

        return redirect()
            ->route('admin.results.show', $campaign)
            ->with('success', __('Results recalculated.'));
    }

    public function approve(CampaignResult $result, ApproveResultsAction $approver): RedirectResponse
    {
        $this->requirePermission('results.approve');

        try {
            $approver->execute($result);
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->with('success', __('Results approved.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->withErrors(['status' => $exception->getMessage()]);
        }
    }

    public function hide(CampaignResult $result, HideResultsAction $hider): RedirectResponse
    {
        $this->requirePermission('results.hide');

        $hider->execute($result);

        return redirect()
            ->route('admin.results.show', $result->campaign_id)
            ->with('success', __('Results hidden.'));
    }

    public function announce(CampaignResult $result, AnnounceResultsAction $announcer): RedirectResponse
    {
        $this->requirePermission('results.announce');

        try {
            $announcer->execute($result);
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->with('success', __('Results announced.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->withErrors(['status' => $exception->getMessage()]);
        }
    }

    /**
     * Committee resolves a tie inside one voting category — picks which
     * of the tied candidates take the remaining winner slot(s).
     */
    public function resolveTie(
        Request $request,
        CampaignResult $result,
        ResolveTieAction $resolver,
    ): RedirectResponse {
        $this->requirePermission('results.approve');

        $validated = $request->validate([
            'category_id'   => ['required', 'integer'],
            'winner_ids'    => ['required', 'array', 'min:1'],
            'winner_ids.*'  => ['integer'],
        ]);

        try {
            $resolver->execute($result, (int) $validated['category_id'], $validated['winner_ids']);
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->with('success', __('Tie resolved.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.results.show', $result->campaign_id)
                ->withErrors(['tie' => $exception->getMessage()]);
        }
    }
}
