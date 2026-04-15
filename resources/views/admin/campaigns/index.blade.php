@extends('layouts.admin')

@section('title', __('Campaigns'))
@section('page_title', __('Voting campaigns'))
@section('page_description', __('Create, publish and close voting campaigns'))

@section('content')
@php
    $counts = [
        'draft'     => \App\Modules\Campaigns\Models\Campaign::where('status', 'draft')->count(),
        'published' => \App\Modules\Campaigns\Models\Campaign::where('status', 'published')->count(),
        'active'    => \App\Modules\Campaigns\Models\Campaign::where('status', 'active')->count(),
        'closed'    => \App\Modules\Campaigns\Models\Campaign::where('status', 'closed')->count(),
    ];
    $statusClass = [
        'active'    => 'bg-emerald-100 text-emerald-700',
        'published' => 'bg-blue-100 text-blue-700',
        'closed'    => 'bg-gray-100 text-gray-700',
        'draft'     => 'bg-amber-100 text-amber-700',
        'archived'  => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 w-full lg:w-auto">
        @foreach(['draft' => __('Draft'), 'published' => __('Published'), 'active' => __('Active'), 'closed' => __('Closed')] as $k => $label)
            <div class="rounded-2xl bg-white border border-gray-200 p-4">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-bold mt-2">{{ $counts[$k] }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    @forelse($campaigns as $campaign)
        @php
            $progress = $campaign->max_voters
                ? min(100, round(($campaign->votes_count / $campaign->max_voters) * 100))
                : null;
        @endphp
        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <a href="{{ route('campaigns.show', $campaign) }}"
                       class="text-xl font-bold hover:text-emerald-700 block">{{ $campaign->localized('title') }}</a>
                    <p class="text-sm text-gray-500 mt-1">{{ $campaign->type?->value }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass[$campaign->status->value] ?? '' }}">
                    {{ $campaign->status->value }}
                </span>
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500">
                        {{ __('Votes') }}:
                        <strong>{{ $campaign->votes_count }}</strong>
                        @if($campaign->max_voters)
                            / {{ $campaign->max_voters }}
                        @endif
                    </span>
                    @if($progress !== null)
                        <span class="font-semibold">{{ $progress }}%</span>
                    @endif
                </div>
                @if($progress !== null)
                    <div class="w-full h-3 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                @else
                    <div class="text-xs text-gray-400">{{ __('No voter cap.') }}</div>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank"
                   class="rounded-2xl border px-4 py-2.5 hover:bg-slate-50">{{ __('Public link') }}</a>
                <a href="{{ route('campaigns.show', $campaign) }}"
                   class="rounded-2xl bg-slate-900 text-white px-4 py-2.5">{{ __('Manage') }}</a>
            </div>
        </div>
    @empty
        <div class="col-span-2 rounded-3xl border border-gray-200 bg-white p-16 text-center text-gray-400">
            {{ __('No campaigns yet.') }}
        </div>
    @endforelse
</div>

<div>{{ $campaigns->links() }}</div>
@endsection
