<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Actions\CloseVotingCampaignAction;
use App\Modules\Campaigns\Actions\CreateVotingCampaignAction;
use App\Modules\Campaigns\Actions\DeleteCampaignAction;
use App\Modules\Campaigns\Actions\PublishVotingCampaignAction;
use App\Modules\Campaigns\Actions\UpdateCampaignAction;
use App\Modules\Campaigns\Http\Requests\StoreCampaignRequest;
use App\Modules\Campaigns\Http\Requests\UpdateCampaignRequest;
use App\Modules\Campaigns\Http\Resources\CampaignResource;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        $paginator = Campaign::query()
            ->withCount('votes')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'),   fn ($q) => $q->where('type',   $request->string('type')))
            ->orderByDesc('id')
            ->paginate(15);

        return CampaignResource::collection($paginator);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);
        return new CampaignResource(
            $campaign->load('categories.candidates.player.club', 'categories.candidates.club')
                ->loadCount('votes')
        );
    }

    public function store(StoreCampaignRequest $request, CreateVotingCampaignAction $action): CampaignResource
    {
        return new CampaignResource($action->execute($request->validated()));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, UpdateCampaignAction $action): CampaignResource
    {
        // UpdateCampaignAction enforces the "Draft or Rejected only" rule
        // — same guard the admin web controller uses, so the API cannot
        // mutate a Published / Active / Closed campaign by mistake.
        return new CampaignResource($action->execute($campaign, $request->validated()));
    }

    public function publish(Campaign $campaign, PublishVotingCampaignAction $action): CampaignResource
    {
        $this->authorize('publish', $campaign);
        return new CampaignResource($action->execute($campaign));
    }

    public function close(Campaign $campaign, CloseVotingCampaignAction $action): CampaignResource
    {
        $this->authorize('close', $campaign);
        return new CampaignResource($action->execute($campaign));
    }

    public function destroy(Request $request, Campaign $campaign, DeleteCampaignAction $action): JsonResponse
    {
        // Use the dedicated delete policy (was incorrectly checking `update`)
        // and route through DeleteCampaignAction so the same vote-count
        // safety net applied in the admin UI also protects the API.
        $this->authorize('delete', $campaign);
        $action->execute($campaign, force: $request->boolean('force'));
        return response()->json(status: 204);
    }
}
