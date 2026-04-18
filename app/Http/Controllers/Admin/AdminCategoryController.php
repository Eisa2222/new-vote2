<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\AttachCandidateToCategoryAction;
use App\Modules\Campaigns\Actions\AttachCategoryToCampaignAction;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Campaigns\Http\Requests\StoreCandidateRequest;
use App\Modules\Campaigns\Http\Requests\StoreCategoryRequest;
use App\Modules\Campaigns\Http\Requests\UpdateCategoryRequest;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Campaigns\Models\VotingCategoryCandidate;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AdminCategoryController extends Controller
{
    public function index(Campaign $campaign): View|RedirectResponse
    {
        $this->authorize('update', $campaign);

        // Team of the Season campaigns have a dedicated, position-aware UI
        // so admins can't attach an attacker to the goalkeeper line.
        if ($campaign->type === CampaignType::TeamOfTheSeason) {
            return redirect()->route('admin.tos.candidates', $campaign);
        }

        $campaign->load([
            'categories.candidates.player.club',
            'categories.candidates.club',
        ]);

        return view('admin.categories.index', [
            'campaign'      => $campaign,
            'players'       => Player::with('club')->orderBy('name_en')->get(),
            'clubs'         => Club::orderBy('name_en')->get(),
            'categoryTypes' => CategoryType::cases(),
        ]);
    }

    public function store(
        StoreCategoryRequest $request,
        Campaign $campaign,
        AttachCategoryToCampaignAction $attacher,
    ): RedirectResponse {
        $attacher->execute($campaign, $request->toActionPayload());

        return redirect()
            ->route('admin.categories.index', $campaign)
            ->with('success', __('Category added.'));
    }

    public function update(UpdateCategoryRequest $request, VotingCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index', $category->campaign)
            ->with('success', __('Category updated.'));
    }

    public function destroy(VotingCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->campaign);
        $campaign = $category->campaign;

        $category->delete();

        return redirect()
            ->route('admin.categories.index', $campaign)
            ->with('success', __('Category deleted.'));
    }

    public function storeCandidate(
        StoreCandidateRequest $request,
        VotingCategory $category,
        AttachCandidateToCategoryAction $attacher,
    ): RedirectResponse {
        $attacher->execute($category, $request->validated());

        return redirect()
            ->route('admin.categories.index', $category->campaign)
            ->with('success', __('Candidate added.'));
    }

    public function destroyCandidate(VotingCategoryCandidate $candidate): RedirectResponse
    {
        $this->authorize('update', $candidate->category->campaign);
        $campaign = $candidate->category->campaign;

        $candidate->delete();

        return redirect()
            ->route('admin.categories.index', $campaign)
            ->with('success', __('Candidate removed.'));
    }
}
