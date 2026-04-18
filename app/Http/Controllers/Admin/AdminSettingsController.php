<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Leagues\Actions\DeleteLeagueAction;
use App\Modules\Leagues\Http\Requests\StoreLeagueRequest;
use App\Modules\Leagues\Models\League;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Shared\Http\Requests\UpdateGeneralSettingsRequest;
use App\Modules\Shared\Services\SettingsService;
use App\Modules\Sports\Actions\DeleteSportAction;
use App\Modules\Sports\Http\Requests\StoreSportRequest;
use App\Modules\Sports\Http\Requests\UpdateSportRequest;
use App\Modules\Sports\Models\Sport;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class AdminSettingsController extends Controller
{
    /** General-settings keys stored in the `settings` table. */
    private const GENERAL_SETTING_DEFAULTS = [
        'app_name'              => 'SFPA Voting',
        'contact_email'         => 'admin@sfpa.sa',
        'default_max_voters'    => '',
        'default_campaign_days' => '7',
        'committee_name_ar'     => 'لجنة التصويت',
        'committee_name_en'     => 'Voting Committee',
    ];

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
    }

    public function index(SettingsService $settings): View
    {
        $this->authorizeManage();

        return view('admin.settings.index', [
            'sports'           => Sport::orderBy('name_en')->get(),
            'leagues'          => League::with('sport')->withCount(['clubs', 'campaigns'])->orderBy('name_en')->get(),
            'campaignTypes'    => CampaignType::cases(),
            'positions'        => PlayerPosition::cases(),
            'committeeMembers' => User::role('committee')->orderBy('name')->get(),
            'generalSettings'  => $this->readGeneralSettings($settings),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $settings->setMany($request->validated(), 'general');

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('Settings saved.'));
    }

    // ─── Sports ────────────────────────────────────────────────

    public function storeSport(StoreSportRequest $request): RedirectResponse
    {
        Sport::create($request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('Sport added.'));
    }

    public function updateSport(UpdateSportRequest $request, Sport $sport): RedirectResponse
    {
        $sport->update($request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('Sport updated.'));
    }

    public function destroySport(Sport $sport, DeleteSportAction $deleter): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $deleter->execute($sport);
            return redirect()
                ->route('admin.settings.index')
                ->with('success', __('Sport removed.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.settings.index')
                ->withErrors(['sport' => $exception->getMessage()]);
        }
    }

    // ─── Leagues ───────────────────────────────────────────────

    public function storeLeague(StoreLeagueRequest $request): RedirectResponse
    {
        League::create($request->validated());

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('League added.'));
    }

    public function destroyLeague(League $league, DeleteLeagueAction $deleter): RedirectResponse
    {
        $this->authorizeManage();

        try {
            $deleter->execute($league);
            return redirect()
                ->route('admin.settings.index')
                ->with('success', __('League removed.'));
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.settings.index')
                ->withErrors(['league' => $exception->getMessage()]);
        }
    }

    /** JSON endpoint — returns clubs belonging to a league (used by campaign create). */
    public function leagueClubs(League $league): JsonResponse
    {
        $authorized = auth()->user()?->can('users.manage')
            || auth()->user()?->can('campaigns.create');
        abort_unless($authorized, 403);

        $clubs = $league->clubs()
            ->active()
            ->orderBy('name_en')
            ->get(['clubs.id', 'clubs.name_ar', 'clubs.name_en']);

        return response()->json(['data' => $clubs]);
    }

    /** @return array<string,string> */
    private function readGeneralSettings(SettingsService $settings): array
    {
        $result = [];
        foreach (self::GENERAL_SETTING_DEFAULTS as $key => $default) {
            $result[$key] = (string) $settings->get($key, $default);
        }
        return $result;
    }
}
