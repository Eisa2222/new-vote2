@extends('layouts.admin')

@section('title', __('Campaigns'))
@section('page_title', __('Voting campaigns'))
@section('page_description', __('Create, publish and close voting campaigns'))

@section('content')

<x-admin.campaigns.pending-approval-queue :pending="$pending" />

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <x-admin.campaigns.status-counters :counts="$counts" />

    <div class="flex gap-2 flex-wrap">
        {{--
          "New Team of the Season" shortcut removed — the club-scoped
          ballot bakes TOS into every campaign (3-3-4-1) alongside the
          two individual awards, so a separate TOS-only campaign type
          is no longer needed. The dedicated admin.tos.* routes remain
          for backwards compatibility with old links / tests.
        --}}

        {{--
          "New campaign" primary CTA — redesigned.
          Uses a brand-gradient fill, a subtle inner ring, a larger
          plus icon inside a glass chip, and a small arrow that hints
          at "you'll go to the next step". Same height as neighbouring
          buttons so it doesn't break the row.
        --}}
        @php $isAr = app()->getLocale() === 'ar'; @endphp
        <a href="{{ route('admin.campaigns.create') }}"
           class="group relative inline-flex items-center gap-3 rounded-xl
                  bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800
                  hover:from-brand-500 hover:via-brand-600 hover:to-brand-700
                  text-white ps-3 pe-5 py-2.5 font-bold whitespace-nowrap
                  shadow-lg shadow-brand-900/20 hover:shadow-xl
                  ring-1 ring-white/10 hover:ring-white/20
                  transition-all duration-200">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg
                         bg-white/15 backdrop-blur text-lg leading-none
                         group-hover:bg-white/25 group-hover:scale-105 transition"
                  aria-hidden="true">+</span>
            <span>{{ __('New Campaign') }}</span>
            <span class="opacity-70 group-hover:opacity-100
                         group-hover:{{ $isAr ? '-translate-x-0.5' : 'translate-x-0.5' }}
                         transition"
                  aria-hidden="true">{{ $isAr ? '←' : '→' }}</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mt-5">
    @forelse($campaigns as $campaign)
        <x-admin.campaigns.campaign-card :campaign="$campaign" />
    @empty
        <div class="col-span-2 rounded-3xl border border-gray-200 bg-white p-16 text-center text-gray-400">
            {{ __('No campaigns yet.') }}
        </div>
    @endforelse
</div>

<div>{{ $campaigns->links() }}</div>
@endsection
