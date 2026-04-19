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
use App\Modules\Shared\Http\Requests\UpdateMailSettingsRequest;
use App\Modules\Shared\Services\SettingsService;
use App\Modules\Shared\Support\MailConfig;
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
            'mailSettings'     => $this->readMailSettings($settings),
            'mailPasswordSet'  => MailConfig::isPasswordSet($settings),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();

        // Separate the logo file/flags from the key-value settings.
        $logoFile  = $request->file('platform_logo');
        $clear     = (bool) ($data['platform_logo_clear'] ?? false);
        unset($data['platform_logo'], $data['platform_logo_clear']);

        if ($logoFile) {
            // Store under public/branding/ so it's web-accessible via
            // the storage symlink. Old file is deleted if any.
            $this->deleteStoredLogo($settings);
            $path = $logoFile->store('branding', 'public');
            $settings->set('platform_logo_path', $path, 'general');
        } elseif ($clear) {
            $this->deleteStoredLogo($settings);
            $settings->set('platform_logo_path', '', 'general');
        }

        $settings->setMany($data, 'general');

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('Settings saved.'));
    }

    private function deleteStoredLogo(SettingsService $settings): void
    {
        $old = (string) $settings->get('platform_logo_path', '');
        if ($old && ! str_starts_with($old, 'http') && ! str_starts_with($old, 'data:')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }
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

    // ─── Mail (SMTP) ───────────────────────────────────────────────

    /** @return array<string,string> */
    private function readMailSettings(SettingsService $settings): array
    {
        return [
            'mail_host'         => (string) $settings->get('mail_host', env('MAIL_HOST', '')),
            'mail_port'         => (string) $settings->get('mail_port', (string) env('MAIL_PORT', '587')),
            'mail_username'     => (string) $settings->get('mail_username', env('MAIL_USERNAME', '')),
            'mail_encryption'   => (string) $settings->get('mail_encryption', env('MAIL_ENCRYPTION', 'tls') ?: 'tls'),
            'mail_from_address' => (string) $settings->get('mail_from_address', env('MAIL_FROM_ADDRESS', '')),
            'mail_from_name'    => (string) $settings->get('mail_from_name', env('MAIL_FROM_NAME', 'SFPA Voting')),
        ];
    }

    public function updateMail(UpdateMailSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();
        $newPassword = trim((string) ($data['mail_password'] ?? ''));
        unset($data['mail_password']);

        // Store password encrypted. Empty value = keep the current one,
        // matching the "leave blank to keep" UX.
        if ($newPassword !== '') {
            $settings->set('mail_password', MailConfig::encryptSafe($newPassword), 'mail');
        }

        $testTo = trim((string) ($data['test_to'] ?? ''));
        unset($data['test_to']);

        // Normalise the "none" encryption choice to an empty string so
        // Laravel's mailer treats it as no-encryption (plaintext/STARTTLS-off).
        if (($data['mail_encryption'] ?? null) === 'none') {
            $data['mail_encryption'] = '';
        }

        $settings->setMany($data, 'mail');

        // Re-apply to runtime config so the test email uses the new values
        // without needing a full request cycle.
        MailConfig::apply($settings);

        $flash = __('Mail settings saved.');
        if ($testTo !== '') {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    __('This is a test email from the SFPA Voting platform confirming your SMTP settings are working.'),
                    function ($msg) use ($testTo) {
                        $msg->to($testTo)->subject(__('SMTP test email'));
                    },
                );
                $flash .= ' '.__('A test email was sent to :to.', ['to' => $testTo]);
            } catch (\Throwable $e) {
                return redirect()
                    ->route('admin.settings.index')
                    ->with('warning', __('Settings saved but the test email failed: :err', ['err' => $e->getMessage()]));
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', $flash);
    }
}
