<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Models\EmailTemplate;
use App\Modules\Notifications\Support\TemplateRegistry;
use App\Modules\Notifications\Support\TemplateRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminEmailTemplateController extends Controller
{
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
    }

    /**
     * List every known event + a matrix of which (type, locale) pairs
     * already have a saved template. Each cell is a link to the editor.
     */
    public function index(): View
    {
        $this->authorizeManage();

        $existing = EmailTemplate::all()->keyBy(
            fn ($r) => $r->key.'|'.($r->campaign_type ?? '').'|'.$r->locale,
        );

        return view('admin.email-templates.index', [
            'events'     => TemplateRegistry::EVENTS,
            'types'      => TemplateRegistry::awardTypes(),
            'locales'    => TemplateRegistry::locales(),
            'existing'   => $existing,
        ]);
    }

    /**
     * Editor for one (key, campaign_type, locale) combination. Loads
     * the existing row if it exists, otherwise renders a blank form
     * seeded from the generic fallback so admins don't start empty.
     */
    public function edit(Request $request): View
    {
        $this->authorizeManage();

        $key          = (string) $request->query('key', '');
        $campaignType = $request->query('type');
        $campaignType = $campaignType === '' ? null : $campaignType;
        $locale       = (string) $request->query('locale', 'en');

        abort_unless(TemplateRegistry::knows($key), 404);

        $row = EmailTemplate::query()
            ->where('key', $key)
            ->where('locale', $locale)
            ->when($campaignType === null, fn ($q) => $q->whereNull('campaign_type'))
            ->when($campaignType !== null, fn ($q) => $q->where('campaign_type', $campaignType))
            ->first();

        // Seed a blank editor with whatever generic fallback exists so
        // the admin sees a useful starting point instead of a void.
        if (! $row) {
            $fallback = EmailTemplate::resolve($key, $campaignType, $locale);
            $row      = new EmailTemplate([
                'key'           => $key,
                'campaign_type' => $campaignType,
                'locale'        => $locale,
                'subject'       => $fallback?->subject ?? '',
                'body'          => $fallback?->body    ?? '',
                'is_active'     => true,
            ]);
        }

        return view('admin.email-templates.edit', [
            'row'    => $row,
            'event'  => TemplateRegistry::EVENTS[$key],
            'vars'   => TemplateRegistry::varsFor($key),
            'key'    => $key,
            'type'   => $campaignType,
            'locale' => $locale,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $data = $request->validate([
            'key'           => ['required', 'string', 'max:80'],
            'campaign_type' => ['nullable', 'string', 'max:40'],
            'locale'        => ['required', 'string', 'in:ar,en'],
            'subject'       => ['required', 'string', 'max:240'],
            'body'          => ['required', 'string', 'max:20000'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        abort_unless(TemplateRegistry::knows($data['key']), 404);

        $data['campaign_type'] = $data['campaign_type'] === '' ? null : $data['campaign_type'];
        $data['is_active']     = (bool) ($data['is_active'] ?? true);

        EmailTemplate::updateOrCreate(
            [
                'key'           => $data['key'],
                'campaign_type' => $data['campaign_type'],
                'locale'        => $data['locale'],
            ],
            [
                'subject'   => $data['subject'],
                'body'      => $data['body'],
                'is_active' => $data['is_active'],
            ],
        );

        return redirect()
            ->route('admin.email-templates.index')
            ->with('success', __('Email template saved.'));
    }

    /**
     * Live preview — renders the current draft against sample variables
     * so the admin can see the interpolation before saving.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeManage();
        $subject = (string) $request->input('subject', '');
        $body    = (string) $request->input('body', '');

        $sample = [
            'platform.name'       => \App\Modules\Shared\Support\Branding::name(),
            'campaign.title'      => __('Player of the Year 2026'),
            'campaign.start_at'   => now()->format('Y-m-d H:i'),
            'campaign.end_at'     => now()->addDays(7)->format('Y-m-d H:i'),
            'campaign.public_url' => url('/vote/SAMPLE'),
            'voter.name'          => __('Ahmed Ali'),
            'voter.club'          => __('Al-Hilal'),
            'admin.name'          => auth()->user()?->name ?? 'Admin',
            'user.name'           => auth()->user()?->name ?? 'New user',
            'invite.url'          => url('/password/reset/SAMPLE'),
            'reason'              => __('Please add more candidates.'),
            'winners_list'        => "🥇 Ahmed Ali\n🥈 Saud Abdulhamid\n🥉 Salem Al-Dawsari",
        ];

        return response()->json([
            'subject' => TemplateRenderer::interpolate($subject, $sample),
            'body'    => TemplateRenderer::interpolate($body, $sample),
        ]);
    }
}
