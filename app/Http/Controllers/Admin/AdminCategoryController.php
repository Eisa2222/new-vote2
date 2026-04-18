<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\AttachCandidateToCategoryAction;
use App\Modules\Campaigns\Actions\AttachCategoryToCampaignAction;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Campaigns\Enums\CandidateType;
use App\Modules\Campaigns\Enums\CategoryType;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Campaigns\Models\VotingCategory;
use App\Modules\Campaigns\Models\VotingCategoryCandidate;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

final class AdminCategoryController extends Controller
{
    public function index(Campaign $campaign): View|RedirectResponse
    {
        $this->authorize('update', $campaign);

        // Team of the Season campaigns have a dedicated, position-aware UI.
        // Redirect there so admins can't attach an attacker to the GK line.
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

    public function store(Request $request, Campaign $campaign, AttachCategoryToCampaignAction $attacher): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $data = $request->validate([
            'title_ar'      => ['required', 'string', 'max:180'],
            'title_en'      => ['required', 'string', 'max:180'],
            'category_type' => ['required', new Enum(CategoryType::class)],
            'position_slot' => ['required', 'in:attack,midfield,defense,goalkeeper,any'],
            'selection_min' => ['required', 'integer', 'min:1', 'max:11'],
            'selection_max' => ['required', 'integer', 'min:1', 'max:11', 'gte:selection_min'],
            'is_active'     => ['boolean'],
        ]);
        $data['required_picks'] = $data['selection_max'];

        $attacher->execute($campaign, $data);

        return redirect()
            ->route('admin.categories.index', $campaign)
            ->with('success', __('Category added.'));
    }

    public function update(Request $request, VotingCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->campaign);

        $data = $request->validate([
            'title_ar'      => ['sometimes', 'string', 'max:180'],
            'title_en'      => ['sometimes', 'string', 'max:180'],
            'category_type' => ['sometimes', new Enum(CategoryType::class)],
            'selection_min' => ['sometimes', 'integer', 'min:1', 'max:11'],
            'selection_max' => ['sometimes', 'integer', 'min:1', 'max:11'],
            'is_active'     => ['boolean'],
        ]);

        $category->update($data);

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
        Request $request,
        VotingCategory $category,
        AttachCandidateToCategoryAction $attacher,
    ): RedirectResponse {
        $this->authorize('update', $category->campaign);

        $data = $request->validate([
            'candidate_type' => ['required', new Enum(CandidateType::class)],
            'candidate_id'   => ['required', 'integer'],
        ]);

        $attacher->execute($category, $data);

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
