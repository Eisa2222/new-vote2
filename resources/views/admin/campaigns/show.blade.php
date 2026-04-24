@extends('layouts.admin')

@section('title', $campaign->localized('title'))
@section('page_title', $campaign->localized('title'))

@php
    $typeLabels = [
        'individual_award' => __('Individual award'),
        'team_award' => __('Team award'),
        'team_of_the_season' => __('Team of the Season'),
    ];
@endphp

@push('scripts')
    <script defer src="{{ asset('js/admin/campaign-live-stats.js') }}"></script>
@endpush

@section('content')
    <div data-campaign-id="{{ $campaign->id }}">

        <div class="flex items-start justify-between mb-6">
            <div>
                <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-slate-500 hover:underline">
                    ← {{ __('Campaigns') }}
                </a>
                <h1 class="text-2xl font-bold text-slate-800 mt-1">{{ $campaign->localized('title') }}</h1>
                <div class="mt-2 flex gap-2 items-center flex-wrap">
                    <span
                        class="badge badge-{{ $campaign->status->value }} px-3 py-1">{{ $campaign->status->label() }}</span>
                    <span class="text-sm text-slate-500">
                        {{ $typeLabels[$campaign->type->value] ?? $campaign->type->value }}
                    </span>
                </div>
            </div>
        </div>

        <x-admin.campaigns.status-banner :campaign="$campaign" />

        <x-admin.campaigns.stats-grid :campaign="$campaign" />

        {{--
      Explicit Tailwind utilities instead of the .btn-save / .btn-ghost
      @apply component classes. The Tailwind Play CDN doesn't always
      pick up @apply in a raw <style> block, which leaves these
      buttons rendering as plain text links (user caught this in QA).
    --}}
        {{-- Categories / candidates management removed from the admin
         surface — the club-scoped ballot always runs the three fixed
         awards (Best Saudi / Best Foreign / TOS) drawing from each
         club's roster automatically, so a manual shortlist UI is no
         longer part of the primary flow. Route still exists for
         backwards compat. --}}
        <div class="flex items-center gap-2 mb-4 flex-wrap">
            <a href="{{ route('admin.campaigns.clubs.index', $campaign) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <span aria-hidden="true">🔗</span>
                <span>{{ __('Manage club voting links') }}</span>
            </a>

            {{-- Results shortcut — surfaced on every campaign from "active"
             onward. Admin can land straight on the calculate/approve/
             announce dashboard without hunting through the side nav. --}}
            @php
                $showResults = in_array($campaign->status->value, ['active', 'closed', 'archived'], true);
            @endphp
            @if ($showResults)
                <a href="{{ route('admin.results.show', $campaign) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-ink-200 bg-white hover:bg-ink-50 text-ink-700 px-4 py-2.5 text-sm font-medium transition">
                    <span aria-hidden="true">🏆</span>
                    <span>{{ __('View results') }}</span>
                </a>
            @endif

            {{-- When the committee has announced the results publicly,
             also expose the public URL so admins can share it. --}}
            @if (optional($campaign->results_visibility)->value === 'announced')
                <a href="{{ route('public.results', $campaign->public_token) }}" target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-4 py-2.5 text-sm font-medium transition">
                    <span aria-hidden="true">📣</span>
                    <span>{{ __('Public results page') }}</span>
                    <span class="opacity-60">↗</span>
                </a>
            @endif
        </div>

        <x-admin.campaigns.danger-zone :campaign="$campaign" />
    </div>
@endsection
