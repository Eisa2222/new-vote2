<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\AttachCandidateToCategoryAction;
use App\Modules\Campaigns\Actions\AttachCategoryToCampaignAction;
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
    public function index(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $campaign->load('categories.candidates.player.club', 'categories.candidates.club');
        return view('admin.categories.index', [
            'campaign' => $campaign,
            'players'  => Player::with('club')->orderBy('name_en')->get(),
            'clubs'    => Club::orderBy('name_en')->get(),
            'categoryTypes' => CategoryType::cases(),
        ]);
    }

    public function store(Request $request, Campaign $campaign, AttachCategoryToCampaignAction $a): RedirectResponse
    {
        $this->authorize('update', $campaign);
        $data = $request->validate([
            'title_ar'       => ['required', 'string', 'max:180'],
            'title_en'       => ['required', 'string', 'max:180'],
            'category_type'  => ['required', new Enum(CategoryType::class)],
            'position_slot'  => ['required', 'in:attack,midfield,defense,goalkeeper,any'],
            'selection_min'  => ['required', 'integer', 'min:1', 'max:11'],
            'selection_max'  => ['required', 'integer', 'min:1', 'max:11', 'gte:selection_min'],
            'is_active'      => ['boolean'],
        ]);
        $data['required_picks'] = $data['selection_max'];

        $a->execute($campaign, $data);
        return back()->with('success', __('Category added.'));
    }

    public function update(Request $request, VotingCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->campaign);
        $data = $request->validate([
            'title_ar'       => ['sometimes', 'string', 'max:180'],
            'title_en'       => ['sometimes', 'string', 'max:180'],
            'category_type'  => ['sometimes', new Enum(CategoryType::class)],
            'selection_min'  => ['sometimes', 'integer', 'min:1', 'max:11'],
            'selection_max'  => ['sometimes', 'integer', 'min:1', 'max:11'],
            'is_active'      => ['boolean'],
        ]);
        $category->update($data);
        return back()->with('success', __('Category updated.'));
    }

    public function destroy(VotingCategory $category): RedirectResponse
    {
        $this->authorize('update', $category->campaign);
        $category->delete();
        return back()->with('success', __('Category deleted.'));
    }

    public function storeCandidate(
        Request $request,
        VotingCategory $category,
        AttachCandidateToCategoryAction $a,
    ): RedirectResponse {
        $this->authorize('update', $category->campaign);
        $data = $request->validate([
            'candidate_type' => ['required', new Enum(CandidateType::class)],
            'candidate_id'   => ['required', 'integer'],
        ]);
        $a->execute($category, $data);
        return back()->with('success', __('Candidate added.'));
    }

    public function destroyCandidate(VotingCategoryCandidate $candidate): RedirectResponse
    {
        $this->authorize('update', $candidate->category->campaign);
        $candidate->delete();
        return back()->with('success', __('Candidate removed.'));
    }
}
