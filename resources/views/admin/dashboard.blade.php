@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('page_title', __('Dashboard'))
@section('page_description', __('Overview of the voting system'))

@section('content')
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    // Data comes from AdminLandingController → DashboardData query object.
    // Presentation-only helpers (labels / classes / icons) stay here.
    $typeLabels = [
        'individual_award'   => __('Individual award'),
        'team_award'         => __('Team award'),
        'team_of_the_season' => __('Team of the Season'),
    ];
    $statusClass = [
        'active'    => 'bg-emerald-100 text-emerald-700',
        'published' => 'bg-blue-100 text-blue-700',
        'closed'    => 'bg-gray-100 text-gray-700',
        'draft'     => 'bg-amber-100 text-amber-700',
        'archived'  => 'bg-slate-100 text-slate-600',
    ];

    // Each card now carries a `href` → admins can click a KPI and land
    // straight on the relevant list, no extra navigation step.
    $cards = [
        ['title' => __('Clubs'),            'value' => $counts['clubs'],            'icon' => '🏟️', 'color' => 'from-blue-500 to-cyan-500',       'href' => route('admin.clubs.index')],
        ['title' => __('Sports'),           'value' => $counts['sports'],           'icon' => '🏆', 'color' => 'from-violet-500 to-purple-500',  'href' => route('admin.settings.index')],
        ['title' => __('Players'),          'value' => $counts['players'],          'icon' => '👥', 'color' => 'from-emerald-500 to-green-500',  'href' => route('admin.players.index')],
        ['title' => __('Active campaigns'), 'value' => $counts['active_campaigns'], 'icon' => '🗳️', 'color' => 'from-amber-500 to-orange-500',   'href' => route('admin.campaigns.index').'?status=active'],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
    @foreach($cards as $card)
        <a href="{{ $card['href'] }}"
           class="group rounded-3xl bg-gradient-to-br {{ $card['color'] }} text-white p-6 shadow-lg transition hover:shadow-xl hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-white/60">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-white/80">{{ $card['title'] }}</div>
                    <div class="text-4xl font-bold mt-3">{{ $card['value'] }}</div>
                </div>
                <div class="text-3xl">{{ $card['icon'] }}</div>
            </div>
            <div class="mt-4 text-xs text-white/80 inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                {{ __('View details') }}
                <span aria-hidden="true">{{ $dir === 'rtl' ? '←' : '→' }}</span>
            </div>
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('Latest campaigns') }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Track campaigns and their current state') }}</p>
            </div>
            <a href="{{ route('admin.campaigns.index') }}" class="text-emerald-700 font-semibold">{{ __('View all') }}</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b">
                        <th class="text-start py-3">{{ __('Campaign') }}</th>
                        <th class="text-start py-3">{{ __('Type') }}</th>
                        <th class="text-start py-3">{{ __('Period') }}</th>
                        <th class="text-start py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_campaigns as $campaign)
                        <tr class="border-b last:border-0">
                            <td class="py-4 font-medium">
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:text-emerald-700">
                                    {{ $campaign->localized('title') }}
                                </a>
                            </td>
                            <td class="py-4">{{ $typeLabels[$campaign->type?->value] ?? '' }}</td>
                            <td class="py-4">{{ $campaign->start_at->format('Y-m-d') }} — {{ $campaign->end_at->format('Y-m-d') }}</td>
                            <td class="py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass[$campaign->status?->value] ?? '' }}">
                                    {{ $campaign->status?->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-gray-400">{{ __('No campaigns yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">{{ __('Quick alerts') }}</h2>
            <span class="text-sm text-gray-500">{{ __('Today') }}</span>
        </div>
        <div class="space-y-4">
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
                <div class="font-semibold text-amber-800">{{ __('Campaigns closing soon') }}</div>
                <div class="text-sm text-amber-700 mt-1">{{ $ending_soon }} {{ __('within 48h') }}</div>
            </div>
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4">
                <div class="font-semibold text-rose-800">{{ __('Results pending approval') }}</div>
                <div class="text-sm text-rose-700 mt-1">{{ $pending_approval }} {{ __('awaiting approval') }}</div>
            </div>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4">
                <div class="font-semibold text-emerald-800">{{ __('Total votes received') }}</div>
                <div class="text-sm text-emerald-700 mt-1">{{ number_format($total_votes) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
