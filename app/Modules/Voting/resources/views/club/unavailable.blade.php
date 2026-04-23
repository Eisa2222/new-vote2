@extends('voting::club._layout')
@section('title', __('Voting unavailable'))

@section('content')
<div class="card max-w-xl mx-auto text-center space-y-4">
    <div class="text-5xl">⏳</div>
    <h1 class="text-2xl font-bold text-ink-900">{{ __('Voting is not available right now') }}</h1>
    <p class="text-ink-500">{{ $reason }}</p>
    @if($campaign)
        <div class="text-sm text-ink-500 mt-4">
            <strong>{{ $campaign->localized('title') }}</strong>
            @if($club) · {{ $club->localized('name') }} @endif
        </div>
    @endif
</div>
@endsection
