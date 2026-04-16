<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Campaigns\Enums\CampaignType;
use App\Modules\Players\Enums\PlayerPosition;
use App\Modules\Shared\Services\SettingsService;
use App\Modules\Sports\Models\Sport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminSettingsController extends Controller
{
    /** Index showing all tabs. */
    public function index(SettingsService $settings): View
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);

        return view('admin.settings.index', [
            'sports'        => Sport::orderBy('name_en')->get(),
            'campaignTypes' => CampaignType::cases(),
            'positions'     => PlayerPosition::cases(),
            'generalSettings' => [
                'app_name'             => $settings->get('app_name', 'SFPA Voting'),
                'contact_email'        => $settings->get('contact_email', 'admin@sfpa.sa'),
                'default_max_voters'   => $settings->get('default_max_voters', ''),
                'default_campaign_days'=> $settings->get('default_campaign_days', '7'),
                'committee_name_ar'    => $settings->get('committee_name_ar', 'لجنة التصويت'),
                'committee_name_en'    => $settings->get('committee_name_en', 'Voting Committee'),
            ],
        ]);
    }

    public function updateGeneral(Request $request, SettingsService $settings): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);

        $data = $request->validate([
            'app_name'              => ['required', 'string', 'max:120'],
            'contact_email'         => ['required', 'email'],
            'default_max_voters'    => ['nullable', 'integer', 'min:1'],
            'default_campaign_days' => ['required', 'integer', 'min:1', 'max:365'],
            'committee_name_ar'     => ['required', 'string', 'max:120'],
            'committee_name_en'     => ['required', 'string', 'max:120'],
        ]);

        $settings->setMany($data, 'general');
        return back()->with('success', __('Settings saved.'));
    }

    public function storeSport(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);

        $data = $request->validate([
            'slug'    => ['nullable', 'string', 'max:60'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'status'  => ['required', 'in:active,inactive'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $request->merge(['slug' => $data['slug']])->validate([
            'slug' => [Rule::unique('sports', 'slug')],
        ]);

        Sport::create($data);
        return back()->with('success', __('Sport added.'));
    }

    public function updateSport(Request $request, Sport $sport): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'status'  => ['required', 'in:active,inactive'],
        ]);

        $sport->update($data);
        return back()->with('success', __('Sport updated.'));
    }

    public function destroySport(Sport $sport): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);

        if ($sport->clubs()->exists()) {
            return back()->withErrors([
                'sport' => __('Cannot delete a sport that is linked to clubs.'),
            ]);
        }
        $sport->delete();
        return back()->with('success', __('Sport removed.'));
    }
}
