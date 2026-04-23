@extends('voting::club._layout')
@section('title', __('Thank you'))

@section('content')
<div class="card max-w-xl mx-auto text-center space-y-5">
    <div class="text-6xl">🎉</div>
    <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900">{{ __('Your vote has been recorded') }}</h1>
    <p class="text-ink-500">
        {{ __('Thank you for participating in :title.', ['title' => $campaign->localized('title')]) }}
    </p>

    <div class="rounded-2xl bg-brand-50 border border-brand-200 p-5 text-start text-sm text-brand-800">
        <div class="font-bold mb-1">{{ __('Be the first to know when results are announced') }}</div>
        <div class="text-brand-700">
            {{ __('Share your contact details below (optional) and we will let you know the moment the committee announces the winners.') }}
        </div>
    </div>

    <div class="flex items-center justify-center gap-2 flex-wrap">
        <a href="{{ route('voting.club.profile', $row->voting_link_token) }}" class="btn-save">
            <span aria-hidden="true">✉️</span>
            <span>{{ __('Add my contact details') }}</span>
        </a>
        <a href="{{ route('public.campaigns') }}" class="btn-ghost">
            {{ __('Skip — take me out') }}
        </a>
    </div>
</div>
@endsection
