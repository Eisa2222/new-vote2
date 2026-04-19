@extends('layouts.admin')

@section('title', $campaign->localized('title'))
@section('page_title', $campaign->localized('title'))

@php
    $typeLabels = [
        'individual_award'   => __('Individual award'),
        'team_award'         => __('Team award'),
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
                <span class="badge badge-{{ $campaign->status->value }} px-3 py-1">{{ $campaign->status->label() }}</span>
                <span class="text-sm text-slate-500">
                    {{ $typeLabels[$campaign->type->value] ?? $campaign->type->value }}
                </span>
            </div>
        </div>
    </div>

    <x-admin.campaigns.status-banner :campaign="$campaign" />

    <x-admin.campaigns.stats-grid :campaign="$campaign" />

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('admin.categories.index', $campaign) }}" class="btn-save">
            <span>+</span>
            <span>{{ __('Manage categories & candidates') }}</span>
        </a>
    </div>

    <x-admin.campaigns.categories-list :campaign="$campaign" />

    <x-admin.campaigns.danger-zone :campaign="$campaign" />
</div>
@endsection
