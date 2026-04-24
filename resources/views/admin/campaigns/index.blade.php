@extends('layouts.admin')

@section('title', __('Campaigns'))
@section('page_title', __('Voting campaigns'))
@section('page_description', __('Create, publish and close voting campaigns'))

@section('content')

    <x-admin.campaigns.pending-approval-queue :pending="$pending" />

    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('admin.campaigns.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700
          text-white px-4 py-2.5 text-sm font-semibold shadow-sm transition">
            <span aria-hidden="true">+</span>
            <span>{{ __('New Campaign') }}</span>
        </a>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <x-admin.campaigns.status-counters :counts="$counts" />


    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
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
