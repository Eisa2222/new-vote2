@extends('layouts.admin')

@section('title', __('Results'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Manage results for this campaign'))

@section('content')
@php
    use App\Modules\Campaigns\Enums\CampaignType;

    $visibility   = $campaign->results_visibility?->value ?? 'hidden';
    $resultStatus = $result?->status?->value;
    $totalVotes   = $campaign->votes()->count();

    // Workflow progress: 0 (nothing) → 3 (announced).
    $step = match ($resultStatus) {
        'announced'  => 3,
        'approved'   => 2,
        'calculated' => 1,
        default      => 0,
    };

    $isTosWithWinners = $campaign->type === CampaignType::TeamOfTheSeason
        && in_array($resultStatus, ['approved', 'announced'], true);
@endphp

<div class="flex items-center gap-2 text-sm text-ink-500 mb-4">
    <a href="{{ route('admin.results.index') }}" class="hover:underline">{{ __('Results') }}</a>
    <span>·</span>
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:underline">
        {{ $campaign->localized('title') }}
    </a>
</div>

<x-admin.results.workflow-timeline :step="$step" />

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- LEFT: ranking card --}}
    <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
            <h2 class="text-xl font-bold">{{ __('Ranking') }}</h2>
            <x-admin.results.action-bar
                :campaign="$campaign"
                :result="$result"
                :resultStatus="$resultStatus" />
        </div>

        @if(! $result)
            <div class="py-12 text-center">
                <div class="inline-flex w-16 h-16 rounded-full bg-slate-100 text-slate-400 items-center justify-center text-3xl mb-4">📊</div>
                <div class="text-ink-700 font-semibold">{{ __('No results yet') }}</div>
                <p class="text-sm text-ink-500 mt-2 max-w-md mx-auto">
                    {{ __('Results not calculated yet. Click Recalculate to generate.') }}
                </p>
            </div>
        @else
            <x-admin.results.tie-break-panel :result="$result" :campaign="$campaign" />

            @if($isTosWithWinners)
                <x-admin.results.tos-pitch :campaign="$campaign" :result="$result" />
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h3 class="font-bold text-ink-800">{{ __('Full ranking per line') }}</h3>
                    <a href="{{ url('/results/'.$campaign->public_token) }}" target="_blank"
                       class="text-sm text-brand-700 hover:underline">
                        {{ __('View public page') }} ↗
                    </a>
                </div>
            @endif

            <x-admin.results.ranking-list :result="$result" />
        @endif
    </div>

    {{-- RIGHT: state panel --}}
    <x-admin.results.state-panel
        :campaign="$campaign"
        :result="$result"
        :visibility="$visibility"
        :resultStatus="$resultStatus"
        :totalVotes="$totalVotes" />
</div>
@endsection
