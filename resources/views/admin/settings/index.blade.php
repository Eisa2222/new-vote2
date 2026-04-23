@extends('layouts.admin')

@section('title', __('Settings'))
@section('page_title', __('Settings'))
@section('page_description', __('System preferences, sports catalog, and built-in reference data'))

@section('content')
<div class="max-w-6xl">

    <div class="border-b border-ink-200 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
            <button type="button" data-tab="general"
                    class="tab-btn pb-3 border-b-2 font-semibold whitespace-nowrap transition">
                ⚙️ {{ __('General') }}
            </button>
            <button type="button" data-tab="sports"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                🏆 {{ __('Sports') }}
            </button>
            <button type="button" data-tab="leagues"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                🏅 {{ __('Leagues') }}
            </button>
            <button type="button" data-tab="positions"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                🧍 {{ __('Positions') }}
            </button>
            <button type="button" data-tab="types"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                🗳️ {{ __('Campaign types') }}
            </button>
            <button type="button" data-tab="committee"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                👥 {{ __('Committee') }}
            </button>
            <button type="button" data-tab="mail"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                ✉️ {{ __('Mail (SMTP)') }}
            </button>
            <button type="button" data-tab="sms"
                    class="tab-btn pb-3 border-b-2 border-transparent font-semibold text-ink-500 hover:text-ink-900 whitespace-nowrap transition">
                📱 {{ __('SMS') }}
            </button>
        </nav>
    </div>

    {{-- GENERAL --}}
    <section data-pane="general" class="tab-pane">
        <div class="card space-y-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('General settings') }}</h2>
                <p class="text-sm text-ink-500 mt-1">{{ __('Platform-wide preferences and defaults.') }}</p>
            </div>

            <form method="post" action="/admin/settings/general" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Platform logo — appears in the sidebar, login page,
                     voting screen and anywhere <x-brand.logo/> is used. --}}
                @php
                    $logoUrl = \App\Modules\Shared\Support\Branding::logoUrl();
                @endphp
                <div class="rounded-2xl border border-ink-200 p-4 bg-ink-50/40">
                    <label class="block text-sm font-semibold mb-2">{{ __('Platform logo') }}</label>
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="w-20 h-20 rounded-2xl bg-white border border-ink-200 flex items-center justify-center overflow-hidden">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ __('Current logo') }}" class="w-full h-full object-contain p-2">
                            @else
                                <span class="text-ink-400 text-xs">{{ __('No logo') }}</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-[220px]">
                            <input type="file" name="platform_logo" accept="image/png,image/jpeg,image/webp"
                                   class="block w-full text-sm text-ink-700 file:me-3 file:rounded-lg file:border-0 file:bg-brand-600 file:text-white file:px-3 file:py-1.5 file:text-xs file:font-semibold hover:file:bg-brand-700">
                            <p class="text-xs text-ink-500 mt-1">{{ __('PNG, JPEG or WEBP up to 2 MB. Replaces the default FPA wordmark everywhere.') }}</p>
                            @if($logoUrl)
                                <label class="mt-2 inline-flex items-center gap-2 text-xs text-rose-600">
                                    <input type="checkbox" name="platform_logo_clear" value="1" class="rounded border-ink-300 text-rose-600">
                                    {{ __('Remove current logo') }}
                                </label>
                            @endif
                            @error('platform_logo') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Application name') }}</label>
                        <input name="app_name" value="{{ old('app_name', $generalSettings['app_name']) }}" required
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Contact email') }}</label>
                        <input type="email" name="contact_email"
                               value="{{ old('contact_email', $generalSettings['contact_email']) }}" required
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Default max voters (optional)') }}</label>
                        <input type="number" min="1" name="default_max_voters"
                               value="{{ old('default_max_voters', $generalSettings['default_max_voters']) }}"
                               placeholder="{{ __('Leave empty for unlimited') }}"
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Default campaign duration (days)') }}</label>
                        <input type="number" min="1" max="365" name="default_campaign_days"
                               value="{{ old('default_campaign_days', $generalSettings['default_campaign_days']) }}" required
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Committee name') }} (AR)</label>
                        <input name="committee_name_ar"
                               value="{{ old('committee_name_ar', $generalSettings['committee_name_ar']) }}" required
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">{{ __('Committee name') }} (EN)</label>
                        <input name="committee_name_en"
                               value="{{ old('committee_name_en', $generalSettings['committee_name_en']) }}" required
                               class="w-full rounded-xl border border-ink-200 px-4 py-2.5">
                    </div>
                </div>
                <button class="btn-save">
                    <span>{{ __('Save changes') }}</span>
                </button>
            </form>
        </div>
    </section>

    {{-- SPORTS --}}
    <section data-pane="sports" class="tab-pane hidden space-y-6">
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-bold">{{ __('Sports catalog') }}</h2>
                    <p class="text-sm text-ink-500 mt-1">{{ __('Add, edit or deactivate sports. Sports with linked clubs cannot be deleted.') }}</p>
                </div>
            </div>

            <form method="post" action="/admin/settings/sports"
                  class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6 bg-ink-50 p-4 rounded-xl">
                @csrf
                <input name="name_ar" placeholder="{{ __('Name') }} (AR)" required
                       class="rounded-xl border border-ink-200 px-3 py-2">
                <input name="name_en" placeholder="{{ __('Name') }} (EN)" required
                       class="rounded-xl border border-ink-200 px-3 py-2">
                <input name="slug" placeholder="{{ __('Slug (optional)') }}"
                       class="rounded-xl border border-ink-200 px-3 py-2">
                <select name="status" class="rounded-xl border border-ink-200 px-3 py-2">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </select>
                <button class="btn-save">
                    <span>+</span>
                    <span>{{ __('Add sport') }}</span>
                </button>
            </form>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-ink-500">
                        <th class="text-start py-2">{{ __('Slug') }}</th>
                        <th class="text-start py-2">{{ __('Name') }} AR</th>
                        <th class="text-start py-2">{{ __('Name') }} EN</th>
                        <th class="text-start py-2">{{ __('Status') }}</th>
                        <th class="text-start py-2">{{ __('Clubs') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach($sports as $sport)
                        <tr>
                            <td class="py-3 font-mono text-xs text-ink-500">{{ $sport->slug }}</td>
                            <td class="py-3"><form method="post" action="/admin/settings/sports/{{ $sport->id }}" class="hidden edit-{{ $sport->id }}">@csrf @method('PUT')</form>
                                <input form="none" value="{{ $sport->name_ar }}" disabled
                                       class="w-full bg-transparent border-0 px-0 py-0">
                            </td>
                            <td class="py-3">
                                <input form="none" value="{{ $sport->name_en }}" disabled
                                       class="w-full bg-transparent border-0 px-0 py-0">
                            </td>
                            <td class="py-3">
                                <span class="badge badge-{{ $sport->status->value }}">{{ $sport->status->label() }}</span>
                            </td>
                            <td class="py-3 text-ink-500">{{ $sport->totalClubsCount() }}</td>
                            <td class="py-3 text-end">
                                <form method="post" action="/admin/settings/sports/{{ $sport->id }}"
                                      onsubmit="return confirm('{{ __('Delete this sport?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1 rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-3 py-1.5 text-xs font-medium transition">
                                        <span aria-hidden="true">🗑</span>
                                        <span>{{ __('Delete') }}</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- LEAGUES --}}
    <section data-pane="leagues" class="tab-pane hidden space-y-6">
        <div class="card">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ __('Leagues') }}</h2>
                    <p class="text-sm text-ink-500 mt-1">
                        {{ __('A league belongs to one sport and contains many clubs. Clubs can join multiple leagues across sports (e.g., Al-Ittihad in Roshn Football + Basketball).') }}
                    </p>
                </div>
            </div>

            <form method="post" action="/admin/settings/leagues"
                  class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-6 bg-ink-50 p-4 rounded-xl">
                @csrf
                <select name="sport_id" required class="rounded-xl border border-ink-200 px-3 py-2 md:col-span-1">
                    @foreach($sports as $s)
                        <option value="{{ $s->id }}">{{ $s->localized('name') }}</option>
                    @endforeach
                </select>
                <input name="name_ar" placeholder="{{ __('Name') }} (AR)" required class="rounded-xl border border-ink-200 px-3 py-2">
                <input name="name_en" placeholder="{{ __('Name') }} (EN)" required class="rounded-xl border border-ink-200 px-3 py-2">
                <input name="slug" placeholder="{{ __('Slug (optional)') }}" class="rounded-xl border border-ink-200 px-3 py-2">
                <select name="status" class="rounded-xl border border-ink-200 px-3 py-2">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </select>
                <button class="btn-save">
                    <span>+</span>
                    <span>{{ __('Add league') }}</span>
                </button>
            </form>

            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
                    <tr>
                        <th class="text-start p-3">{{ __('Name') }}</th>
                        <th class="text-start p-3">{{ __('Sport') }}</th>
                        <th class="text-start p-3">{{ __('Clubs') }}</th>
                        <th class="text-start p-3">{{ __('Campaigns') }}</th>
                        <th class="text-start p-3">{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse($leagues as $l)
                        <tr class="hover:bg-ink-50">
                            <td class="p-3">
                                <div class="font-medium">{{ $l->name_ar }}</div>
                                <div class="text-xs text-ink-500">{{ $l->name_en }}</div>
                            </td>
                            <td class="p-3 text-ink-700">{{ $l->sport?->localized('name') }}</td>
                            <td class="p-3 text-ink-500">{{ $l->clubs_count }}</td>
                            <td class="p-3 text-ink-500">{{ $l->campaigns_count }}</td>
                            <td class="p-3">
                                <span class="badge badge-{{ $l->status->value }}">{{ $l->status->label() }}</span>
                            </td>
                            <td class="p-3 text-end">
                                <form method="post" action="/admin/settings/leagues/{{ $l->id }}"
                                      onsubmit="return confirm('{{ __('Delete this league?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1 rounded-lg border border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-3 py-1.5 text-xs font-medium transition">
                                        <span aria-hidden="true">🗑</span>
                                        <span>{{ __('Delete') }}</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-ink-500">
                            {{ __('No leagues yet. Add one above.') }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- POSITIONS (read-only, system-level) --}}
    <section data-pane="positions" class="tab-pane hidden space-y-4">
        <div class="card">
            <h2 class="text-xl font-bold">{{ __('Built-in player positions') }}</h2>
            <p class="text-sm text-ink-500 mt-1">
                {{ __('These four positions are part of the core system because the Team of the Season rule (3-3-4-1) and position-based category filtering depend on them.') }}
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                @foreach($positions as $p)
                    <div class="rounded-2xl border border-ink-200 p-4 text-center">
                        <div class="text-sm text-ink-500">{{ strtoupper($p->value) }}</div>
                        <div class="text-lg font-bold text-brand-700 mt-1">{{ $p->label() }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl bg-info-500/5 border border-info-500/30 text-info-500 p-4 text-sm">
                ℹ️ {{ __('Adding a new position requires a code change to maintain domain invariants (TOS formation). If you need an additional position for a non-football sport, contact the development team.') }}
            </div>
        </div>
    </section>

    {{-- COMMITTEE --}}
    <section data-pane="committee" class="tab-pane hidden space-y-4">
        <div class="card">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ __('Voting Committee') }}</h2>
                    <p class="text-sm text-ink-500 mt-1">
                        {{ __('Committee members approve, announce, or hide voting results. They cannot create campaigns or manage clubs/players.') }}
                    </p>
                </div>
                <a href="/admin/users/create"
                   class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 font-medium text-sm">
                    + {{ __('Add member') }}
                </a>
            </div>

            <div class="rounded-2xl bg-brand-50 border border-brand-200 p-4 text-sm text-brand-900 mb-4">
                💡 {{ __('To create a committee member: go to Users → New User → assign the "committee" role.') }}
            </div>

            <div class="rounded-xl border border-ink-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-ink-50 text-ink-500 text-xs uppercase">
                        <tr>
                            <th class="text-start p-3">{{ __('Name') }}</th>
                            <th class="text-start p-3">{{ __('Email') }}</th>
                            <th class="text-start p-3">{{ __('Status') }}</th>
                            <th class="text-start p-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse($committeeMembers as $m)
                            <tr class="hover:bg-ink-50">
                                <td class="p-3 font-medium">{{ $m->name }}</td>
                                <td class="p-3 text-ink-500">{{ $m->email }}</td>
                                <td class="p-3">
                                    @if(($m->status ?? 'active') === 'active')
                                        <span class="badge badge-active">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge badge-inactive">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <a href="/admin/users/{{ $m->id }}/edit"
                                       class="rounded-lg border border-ink-200 hover:bg-ink-50 px-3 py-1.5 text-xs font-medium">
                                        ✏️ {{ __('Edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-ink-500">
                                    {{ __('No committee members yet. Create a user and assign the "committee" role.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                <h3 class="font-bold text-ink-800 mb-2">{{ __('What committee members can do') }}</h3>
                <ul class="text-sm text-ink-700 space-y-1.5 list-disc ps-5">
                    <li>{{ __('Recalculate voting results') }}</li>
                    <li>{{ __('Approve results for publication') }}</li>
                    <li>{{ __('Announce results to the public') }}</li>
                    <li>{{ __('Hide results after announcement (emergency takedown)') }}</li>
                </ul>
                <h3 class="font-bold text-ink-800 mt-4 mb-2">{{ __('What committee members cannot do') }}</h3>
                <ul class="text-sm text-ink-500 space-y-1.5 list-disc ps-5">
                    <li>{{ __('Create or edit campaigns, clubs, or players') }}</li>
                    <li>{{ __('Manage other users or permissions') }}</li>
                    <li>{{ __('Change system settings') }}</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- CAMPAIGN TYPES (read-only, system-level) --}}
    <section data-pane="types" class="tab-pane hidden space-y-4">
        <div class="card">
            <h2 class="text-xl font-bold">{{ __('Built-in campaign types') }}</h2>
            <p class="text-sm text-ink-500 mt-1">
                {{ __('Every campaign belongs to one of these three system types. Each drives different voting logic in the backend.') }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
                @foreach($campaignTypes as $t)
                    <div class="rounded-2xl border border-ink-200 p-5">
                        <div class="text-xs text-ink-500 uppercase tracking-wide">{{ $t->value }}</div>
                        <div class="text-lg font-bold mt-2">
                            @switch($t->value)
                                @case('individual_award') {{ __('Individual award') }} @break
                                @case('team_award') {{ __('Team award') }} @break
                                @case('team_of_the_season') {{ __('Team of the Season') }} @break
                            @endswitch
                        </div>
                        <p class="text-sm text-ink-500 mt-2 leading-6">
                            @switch($t->value)
                                @case('individual_award') {{ __('Vote for one or more players (e.g., player of the year).') }} @break
                                @case('team_award') {{ __('Vote for clubs or teams.') }} @break
                                @case('team_of_the_season') {{ __('Voters build the lineup — GK=1 fixed, outfield sum=10 flexible.') }} @break
                            @endswitch
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 rounded-2xl bg-info-500/5 border border-info-500/30 text-info-500 p-4 text-sm">
                ℹ️ {{ __('Campaign types are enum-backed because each type has a distinct submit/validate/result pipeline. A new type requires a corresponding backend implementation.') }}
            </div>
        </div>
    </section>

    {{-- MAIL (SMTP) ------------------------------------------------ --}}
    <section data-pane="mail" class="tab-pane hidden space-y-4">
        <div class="card space-y-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('Mail (SMTP)') }}</h2>
                <p class="text-sm text-ink-500 mt-1">
                    {{ __('Configure the outgoing SMTP server used for password-reset, user invites, and admin notifications.') }}
                </p>
            </div>

            @if (session('warning'))
                <div class="rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 p-3 text-sm">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm space-y-1">
                    @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('admin.settings.mail.update') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4"
                  x-data="{ showPwd: false }">
                @csrf

                <div class="md:col-span-2 rounded-2xl bg-info-500/5 border border-info-500/30 p-4 text-sm text-info-500">
                    {{ __('Current status') }}:
                    @if($mailPasswordSet)
                        ✓ {{ __('SMTP credentials are saved and encrypted.') }}
                    @else
                        ℹ️ {{ __('No SMTP server configured yet — emails currently use the :driver mailer from .env.', ['driver' => config('mail.default')]) }}
                    @endif
                </div>

                <div>
                    <label class="field-label">{{ __('SMTP host') }}</label>
                    <input name="mail_host" value="{{ old('mail_host', $mailSettings['mail_host']) }}"
                           placeholder="smtp.example.com" required class="field-input">
                </div>

                <div>
                    <label class="field-label">{{ __('Port') }}</label>
                    <input type="number" name="mail_port" value="{{ old('mail_port', $mailSettings['mail_port'] ?: '587') }}"
                           min="1" max="65535" required class="field-input">
                    <p class="field-help">{{ __('Typical: 587 (TLS), 465 (SSL), 25 (plaintext).') }}</p>
                </div>

                <div>
                    <label class="field-label">{{ __('Username') }}</label>
                    <input name="mail_username" value="{{ old('mail_username', $mailSettings['mail_username']) }}"
                           autocomplete="off" class="field-input">
                </div>

                <div>
                    <label class="field-label">
                        {{ __('Password') }}
                        @if($mailPasswordSet)
                            <span class="text-ink-400 text-xs font-normal">({{ __('leave empty to keep current') }})</span>
                        @endif
                    </label>
                    <div class="relative">
                        <input name="mail_password" :type="showPwd ? 'text' : 'password'"
                               autocomplete="new-password"
                               placeholder="{{ $mailPasswordSet ? '••••••••' : '' }}"
                               class="field-input {{ app()->getLocale() === 'ar' ? 'pl-12' : 'pr-12' }}">
                        <button type="button" @click="showPwd = !showPwd"
                                class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} flex items-center px-3 text-ink-500 hover:text-brand-700">
                            <span x-text="showPwd ? '🙈' : '👁'"></span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="field-label">{{ __('Encryption') }}</label>
                    <select name="mail_encryption" class="field-select">
                        @foreach(['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => __('None')] as $v => $l)
                            <option value="{{ $v }}" @selected(old('mail_encryption', $mailSettings['mail_encryption'] ?: 'tls') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label">{{ __('From address') }}</label>
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $mailSettings['mail_from_address']) }}"
                           placeholder="no-reply@your-domain.sa" required class="field-input">
                </div>

                <div class="md:col-span-2">
                    <label class="field-label">{{ __('From name') }}</label>
                    <input name="mail_from_name" value="{{ old('mail_from_name', $mailSettings['mail_from_name']) }}"
                           required class="field-input">
                </div>

                <div class="md:col-span-2 rounded-2xl border border-ink-200 bg-ink-50/40 p-4">
                    <label class="field-label flex items-center gap-2">
                        <span>🧪</span>
                        <span>{{ __('Send a test email after saving (optional)') }}</span>
                    </label>
                    <input type="email" name="test_to" value="{{ old('test_to') }}"
                           placeholder="you@example.com" class="field-input">
                    <p class="field-help">
                        {{ __('If provided, a confirmation email is sent to this address right after the settings are saved.') }}
                    </p>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button class="btn-save">
                        <span>{{ __('Save mail settings') }}</span>
                    </button>
                    <span class="text-xs text-ink-500">
                        🔒 {{ __('The password is stored encrypted using the application key.') }}
                    </span>
                </div>
            </form>
        </div>
    </section>

    {{-- SMS --------------------------------------------------------- --}}
    <section data-pane="sms" class="tab-pane hidden space-y-4">
        <div class="card space-y-5" x-data="{ driver: @js($smsSettings['sms_driver']), showT: false, showU: false }">
            <div>
                <h2 class="text-xl font-bold">{{ __('SMS gateway') }}</h2>
                <p class="text-sm text-ink-500 mt-1">
                    {{ __('Configure the outgoing SMS provider used for voter OTP, campaign alerts, and admin notifications.') }}
                </p>
            </div>

            <form method="post" action="{{ route('admin.settings.sms.update') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="field-label">{{ __('Provider') }}</label>
                    <select name="sms_driver" x-model="driver" class="field-select max-w-sm">
                        <option value="log">{{ __('Log (development — writes to application log)') }}</option>
                        <option value="twilio">Twilio</option>
                        <option value="unifonic">Unifonic (Saudi Arabia)</option>
                    </select>
                    <p class="field-help">
                        {{ __('Pick "Log" while testing — every send is written to storage/logs/laravel.log instead of costing real credits.') }}
                    </p>
                </div>

                <div x-show="driver === 'twilio'" x-cloak class="rounded-2xl border border-ink-200 p-4 space-y-4">
                    <h3 class="font-bold text-ink-800">Twilio</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">{{ __('Account SID') }}</label>
                            <input name="sms_twilio_sid" value="{{ old('sms_twilio_sid', $smsSettings['sms_twilio_sid']) }}"
                                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" class="field-input">
                        </div>
                        <div>
                            <label class="field-label">
                                {{ __('Auth token') }}
                                @if($smsHasSecrets['twilio_token'])
                                    <span class="text-ink-400 text-xs font-normal">({{ __('leave empty to keep current') }})</span>
                                @endif
                            </label>
                            <div class="relative">
                                <input name="sms_twilio_token" :type="showT ? 'text' : 'password'" autocomplete="new-password"
                                       placeholder="{{ $smsHasSecrets['twilio_token'] ? '••••••••' : '' }}"
                                       class="field-input {{ app()->getLocale() === 'ar' ? 'pl-12' : 'pr-12' }}">
                                <button type="button" @click="showT = !showT"
                                        class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} flex items-center px-3 text-ink-500 hover:text-brand-700">
                                    <span x-text="showT ? '🙈' : '👁'"></span>
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="field-label">{{ __('From number (E.164)') }}</label>
                            <input name="sms_twilio_from" value="{{ old('sms_twilio_from', $smsSettings['sms_twilio_from']) }}"
                                   placeholder="+19715551234" class="field-input max-w-sm">
                            <p class="field-help">{{ __('A Twilio-verified sender number including the country code.') }}</p>
                        </div>
                    </div>
                </div>

                <div x-show="driver === 'unifonic'" x-cloak class="rounded-2xl border border-ink-200 p-4 space-y-4">
                    <h3 class="font-bold text-ink-800">Unifonic</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">
                                {{ __('AppSid') }}
                                @if($smsHasSecrets['unifonic_appsid'])
                                    <span class="text-ink-400 text-xs font-normal">({{ __('leave empty to keep current') }})</span>
                                @endif
                            </label>
                            <div class="relative">
                                <input name="sms_unifonic_appsid" :type="showU ? 'text' : 'password'" autocomplete="new-password"
                                       placeholder="{{ $smsHasSecrets['unifonic_appsid'] ? '••••••••' : '' }}"
                                       class="field-input {{ app()->getLocale() === 'ar' ? 'pl-12' : 'pr-12' }}">
                                <button type="button" @click="showU = !showU"
                                        class="absolute inset-y-0 {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} flex items-center px-3 text-ink-500 hover:text-brand-700">
                                    <span x-text="showU ? '🙈' : '👁'"></span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="field-label">{{ __('Sender ID') }}</label>
                            <input name="sms_unifonic_sender" value="{{ old('sms_unifonic_sender', $smsSettings['sms_unifonic_sender']) }}"
                                   placeholder="SFPA" class="field-input">
                            <p class="field-help">{{ __('Alphanumeric sender name approved by Unifonic (max 11 chars).') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-ink-200 bg-ink-50/40 p-4 space-y-3">
                    <label class="field-label flex items-center gap-2">
                        <span>🧪</span>
                        <span>{{ __('Send a test SMS after saving (optional)') }}</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input name="test_to" type="text" value="{{ old('test_to') }}"
                               placeholder="+9665XXXXXXXX  {{ __('or') }}  05XXXXXXXX" class="field-input">
                        <input name="test_message" type="text" maxlength="300" value="{{ old('test_message') }}"
                               placeholder="{{ __('SMS test from SFPA Voting') }}" class="field-input">
                    </div>
                    <p class="field-help">
                        {{ __('Saudi numbers can be entered as 05XXXXXXXX — they are auto-converted to +966 E.164 format.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button class="btn-save">
                        <span>{{ __('Save SMS settings') }}</span>
                    </button>
                    <span class="text-xs text-ink-500">
                        🔒 {{ __('Auth tokens / AppSid are stored encrypted.') }}
                    </span>
                </div>
            </form>
        </div>
    </section>

</div>

@push('scripts')
<script>
    const tabs = document.querySelectorAll('[data-tab]');
    const panes = document.querySelectorAll('[data-pane]');
    function show(tab) {
        tabs.forEach(b => {
            const on = b.dataset.tab === tab;
            b.classList.toggle('border-brand-600', on);
            b.classList.toggle('text-brand-700', on);
            b.classList.toggle('border-transparent', !on);
            b.classList.toggle('text-ink-500', !on);
        });
        panes.forEach(p => p.classList.toggle('hidden', p.dataset.pane !== tab));
        localStorage.setItem('settings_tab', tab);
    }
    tabs.forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));
    show(localStorage.getItem('settings_tab') || 'general');
</script>
@endpush
@endsection
