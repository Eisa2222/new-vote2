@extends('voting::club._layout')
@section('title', __('Already voted'))

@section('content')
<div class="card max-w-xl mx-auto text-center space-y-5">
    <div class="text-6xl">✅</div>
    <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900">{{ __('You have already voted') }}</h1>
    <p class="text-ink-500">
        {{ __('Your ballot for :title was recorded. Each voter may only participate once per campaign.', ['title' => $campaign->localized('title')]) }}
    </p>

    <div class="rounded-2xl bg-brand-50 border border-brand-200 p-4 text-sm text-brand-800">
        {{ __('Results will be announced by the committee once the campaign ends.') }}
    </div>

    <div class="flex items-center justify-center gap-2 flex-wrap">
        <a href="{{ route('public.campaigns') }}" class="btn-save">
            <span aria-hidden="true">🏠</span>
            <span>{{ __('Back to campaigns') }}</span>
        </a>
    </div>
</div>
@endsection
