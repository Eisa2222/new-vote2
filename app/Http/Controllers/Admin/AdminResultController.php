<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Results\Actions\AnnounceResultsAction;
use App\Modules\Results\Actions\ApproveResultsAction;
use App\Modules\Results\Actions\CalculateCampaignResultsAction;
use App\Modules\Results\Actions\HideResultsAction;
use App\Modules\Results\Models\CampaignResult;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AdminResultController extends Controller
{
    private function requirePermission(string $perm): void
    {
        abort_unless(auth()->user()?->can($perm), 403);
    }

    public function index(): View
    {
        $this->requirePermission('results.view');

        $campaigns = Campaign::with('result')
            ->whereIn('status', ['active', 'closed', 'published'])
            ->orderByDesc('id')->paginate(20);

        return view('admin.results.index', compact('campaigns'));
    }

    public function show(Campaign $campaign): View
    {
        $this->requirePermission('results.view');

        $result = $campaign->result()->with('items.candidate.player.club', 'items.candidate.club', 'items.category')
            ->first();

        return view('admin.results.show', compact('campaign', 'result'));
    }

    public function calculate(Campaign $campaign, CalculateCampaignResultsAction $a): RedirectResponse
    {
        $this->requirePermission('results.calculate');
        $a->execute($campaign->load('categories'));
        return back()->with('success', __('Results recalculated.'));
    }

    public function approve(CampaignResult $result, ApproveResultsAction $a): RedirectResponse
    {
        $this->requirePermission('results.approve');
        try { $a->execute($result); return back()->with('success', __('Results approved.')); }
        catch (\DomainException $e) { return back()->withErrors(['status' => $e->getMessage()]); }
    }

    public function hide(CampaignResult $result, HideResultsAction $a): RedirectResponse
    {
        $this->requirePermission('results.hide');
        $a->execute($result);
        return back()->with('success', __('Results hidden.'));
    }

    public function announce(CampaignResult $result, AnnounceResultsAction $a): RedirectResponse
    {
        $this->requirePermission('results.announce');
        try { $a->execute($result); return back()->with('success', __('Results announced.')); }
        catch (\DomainException $e) { return back()->withErrors(['status' => $e->getMessage()]); }
    }
}
