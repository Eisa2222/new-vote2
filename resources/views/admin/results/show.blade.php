@extends('layouts.admin')

@section('title', __('Results'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Manage results for this campaign'))

@section('content')
@php
    $visibility = $campaign->results_visibility?->value ?? 'hidden';
    $totalVotes = $result?->items->sum('votes_count') ?: 0;
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('Ranking') }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Current visibility') }}:
                    <span class="font-semibold">{{ $visibility }}</span>
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <form method="post" action="{{ route('results.calculate', $campaign) }}">
                    @csrf
                    <button class="rounded-2xl border px-4 py-2.5 hover:bg-slate-50">{{ __('Recalculate') }}</button>
                </form>
                @if($result)
                    @if($result->status->value === 'calculated')
                        <form method="post" action="{{ route('results.approve', $result) }}">
                            @csrf
                            <button class="rounded-2xl border px-4 py-2.5 hover:bg-slate-50">{{ __('Approve') }}</button>
                        </form>
                    @endif
                    @if($result->status->value === 'approved')
                        <form method="post" action="{{ route('results.announce', $result) }}">
                            @csrf
                            <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5">{{ __('Announce') }}</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('results.hide', $result) }}">
                        @csrf
                        <button class="rounded-2xl border px-4 py-2.5 hover:bg-slate-50">{{ __('Hide') }}</button>
                    </form>
                @endif
            </div>
        </div>

        @if(! $result)
            <div class="py-12 text-center text-gray-400">
                {{ __('Results not calculated yet. Click Recalculate to generate.') }}
            </div>
        @else
            @foreach($result->items->groupBy('voting_category_id') as $categoryId => $items)
                @php($category = $items->first()->category)
                <div class="mb-6">
                    <h3 class="font-semibold text-slate-700 mb-3">{{ $category->localized('title') }}</h3>
                    <div class="space-y-3">
                        @foreach($items->sortBy('rank') as $item)
                            <?php
                                $catTotal = $items->sum('votes_count') ?: 1;
                                $pct = round(($item->votes_count / $catTotal) * 100);
                                $label = $item->candidate->player?->localized('name')
                                      ?? $item->candidate->club?->localized('name');
                            ?>
                            <div class="rounded-2xl border p-4 {{ $item->is_winner ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200' }}">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-500">#{{ $item->rank }}</span>
                                        <span class="font-medium">{{ $label }}</span>
                                        @if($item->is_winner)
                                            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-600 text-white">★ {{ __('Winner') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-bold">{{ $item->votes_count }}</span>
                                        <span class="text-gray-500">({{ $pct }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $item->is_winner ? 'bg-emerald-500' : 'bg-slate-400' }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        <h2 class="text-xl font-bold">{{ __('Result state') }}</h2>

        <div class="rounded-2xl bg-slate-50 p-4">
            <div class="text-sm text-gray-500">{{ __('Calculation') }}</div>
            <div class="font-bold mt-1">{{ $result?->status->value ?? '—' }}</div>
            @if($result?->calculated_at)
                <div class="text-xs text-gray-500 mt-1">{{ $result->calculated_at->format('Y-m-d H:i') }}</div>
            @endif
        </div>
        <div class="rounded-2xl bg-slate-50 p-4">
            <div class="text-sm text-gray-500">{{ __('Public visibility') }}</div>
            <div class="font-bold mt-1">{{ $visibility }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4">
            <div class="text-sm text-gray-500">{{ __('Total votes') }}</div>
            <div class="font-bold mt-1">{{ $campaign->votes()->count() }}</div>
        </div>

        @if($result?->status->value === 'approved')
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                {{ __('Results are ready to be announced.') }}
            </div>
        @endif
    </div>
</div>
@endsection
