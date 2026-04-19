@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('page_title', __('Dashboard'))
@section('page_description', __('Overview of the voting system'))

@section('content')
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';

    $typeLabels = [
        'individual_award'   => __('Individual award'),
        'team_award'         => __('Team award'),
        'team_of_the_season' => __('Team of the Season'),
    ];
    $statusClass = [
        'active'           => 'bg-brand-100 text-brand-700',
        'published'        => 'bg-info-500/10 text-info-500',
        'closed'           => 'bg-ink-100 text-ink-700',
        'draft'            => 'bg-warning-500/10 text-warning-500',
        'pending_approval' => 'bg-amber-100 text-amber-800',
        'rejected'         => 'bg-rose-100 text-rose-700',
        'archived'         => 'bg-ink-100 text-ink-500',
    ];

    // Primary KPI cards — brand greens are the hero; supporting cards
    // use tonal variants so the whole dashboard feels one theme rather
    // than a random gradient assortment.
    $cards = [
        [
            'title' => __('Players'), 'value' => $counts['players'],
            'icon'  => '👥', 'href' => route('admin.players.index'),
            'ring'  => 'from-brand-700 to-brand-500',
        ],
        [
            'title' => __('Clubs'), 'value' => $counts['clubs'],
            'icon'  => '🏟️', 'href' => route('admin.clubs.index'),
            'ring'  => 'from-brand-600 to-brand-400',
        ],
        [
            'title' => __('Active campaigns'), 'value' => $counts['active_campaigns'],
            'icon'  => '🗳️', 'href' => route('admin.campaigns.index').'?status=active',
            'ring'  => 'from-accent-600 to-accent-400',
        ],
        [
            'title' => __('Votes today'), 'value' => $votes_today,
            'icon'  => '📈', 'href' => route('admin.campaigns.index'),
            'ring'  => 'from-brand-800 to-brand-600',
        ],
    ];

    // Secondary tiles — small, monochrome, more status-info than hero.
    $mini = [
        ['label' => __('Total votes'),         'value' => number_format($total_votes),       'icon' => '🧮'],
        ['label' => __('Votes last 7 days'),   'value' => number_format($votes_last_7_days), 'icon' => '📊'],
        ['label' => __('Pending approval'),    'value' => $pending_campaigns,                'icon' => '⏳', 'href' => route('admin.campaigns.index').'?status=pending_approval'],
        ['label' => __('Published campaigns'), 'value' => $published_campaigns,              'icon' => '📣'],
        ['label' => __('Closed campaigns'),    'value' => $closed_campaigns,                 'icon' => '🔒'],
        ['label' => __('Sports'),              'value' => $counts['sports'],                 'icon' => '🏆', 'href' => route('admin.settings.index')],
    ];
@endphp

{{-- Hero KPI row ------------------------------------------------------ --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
    @foreach($cards as $card)
        <a href="{{ $card['href'] }}"
           class="group relative overflow-hidden rounded-3xl bg-white border border-ink-200 p-6 shadow-sm transition hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            {{-- Brand-colour accent ring on the top edge. --}}
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r {{ $card['ring'] }}"></div>
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-ink-500">{{ $card['title'] }}</div>
                    <div class="text-4xl font-extrabold mt-3 text-ink-900 tabular-nums">{{ $card['value'] }}</div>
                </div>
                <div class="text-3xl">{{ $card['icon'] }}</div>
            </div>
            <div class="mt-4 text-xs text-brand-700 inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                {{ __('View details') }}
                <span aria-hidden="true">{{ $dir === 'rtl' ? '←' : '→' }}</span>
            </div>
        </a>
    @endforeach
</div>

{{-- Secondary tile strip ---------------------------------------------- --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
    @foreach($mini as $m)
        @php $Tag = isset($m['href']) ? 'a' : 'div'; @endphp
        <{{ $Tag }}
            @isset($m['href']) href="{{ $m['href'] }}" @endisset
            class="flex items-center gap-3 rounded-2xl bg-white border border-ink-200 px-3 py-3 hover:border-brand-400 transition">
            <div class="text-xl">{{ $m['icon'] }}</div>
            <div class="min-w-0">
                <div class="text-[11px] text-ink-500 truncate">{{ $m['label'] }}</div>
                <div class="text-lg font-bold text-ink-900 tabular-nums">{{ $m['value'] }}</div>
            </div>
        </{{ $Tag }}>
    @endforeach
</div>

{{-- Campaign table + alerts ------------------------------------------- --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 card">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('Latest campaigns') }}</h2>
                <p class="text-sm text-ink-500 mt-1">{{ __('Track campaigns and their current state') }}</p>
            </div>
            <a href="{{ route('admin.campaigns.index') }}" class="text-brand-700 font-semibold hover:underline">{{ __('View all') }}</a>
        </div>

        <div class="overflow-x-auto -mx-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-ink-500 border-b border-ink-200">
                        <th class="text-start py-3 px-2">{{ __('Campaign') }}</th>
                        <th class="text-start py-3 px-2">{{ __('Type') }}</th>
                        <th class="text-start py-3 px-2">{{ __('Period') }}</th>
                        <th class="text-start py-3 px-2">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_campaigns as $campaign)
                        <tr class="border-b border-ink-100 last:border-0 hover:bg-ink-50">
                            <td class="py-3 px-2 font-medium">
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:text-brand-700">
                                    {{ $campaign->localized('title') }}
                                </a>
                            </td>
                            <td class="py-3 px-2 text-ink-600">{{ $typeLabels[$campaign->type?->value] ?? '' }}</td>
                            <td class="py-3 px-2 text-ink-600 whitespace-nowrap">{{ $campaign->start_at->format('Y-m-d') }} — {{ $campaign->end_at->format('Y-m-d') }}</td>
                            <td class="py-3 px-2">
                                <span class="badge {{ $statusClass[$campaign->status?->value] ?? 'bg-ink-100 text-ink-700' }}">
                                    {{ $campaign->status?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-ink-400">{{ __('No campaigns yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">{{ __('Quick alerts') }}</h2>
            <span class="text-sm text-ink-500">{{ __('Today') }}</span>
        </div>
        <div class="space-y-3">
            <a href="{{ route('admin.campaigns.index') }}?status=active"
               class="block rounded-2xl bg-amber-50 border border-amber-200 p-4 hover:bg-amber-100 transition">
                <div class="font-semibold text-amber-800">{{ __('Campaigns closing soon') }}</div>
                <div class="text-sm text-amber-700 mt-1">{{ $ending_soon }} {{ __('within 48h') }}</div>
            </a>
            <a href="{{ route('admin.results.index') }}"
               class="block rounded-2xl bg-rose-50 border border-rose-200 p-4 hover:bg-rose-100 transition">
                <div class="font-semibold text-rose-800">{{ __('Results pending approval') }}</div>
                <div class="text-sm text-rose-700 mt-1">{{ $pending_approval }} {{ __('awaiting approval') }}</div>
            </a>
            <div class="rounded-2xl bg-brand-50 border border-brand-200 p-4">
                <div class="font-semibold text-brand-800">{{ __('Total votes received') }}</div>
                <div class="text-sm text-brand-700 mt-1">{{ number_format($total_votes) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
