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
                <button class="btn-brand">{{ __('Save changes') }}</button>
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
                <button class="btn-brand">+ {{ __('Add sport') }}</button>
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
                                    <button class="text-danger-600 text-xs hover:underline">{{ __('Delete') }}</button>
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
                <button class="btn-brand">+ {{ __('Add league') }}</button>
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
                                    <button class="text-danger-600 text-xs hover:underline">{{ __('Delete') }}</button>
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
