@extends('layouts.admin')

@section('title', __('Campaigns'))
@section('page_title', __('Voting campaigns'))
@section('page_description', __('Create, publish and close voting campaigns'))

@section('content')

<x-admin.campaigns.pending-approval-queue :pending="$pending" />

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <x-admin.campaigns.status-counters :counts="$counts" />

    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.tos.create') }}"
           class="inline-flex items-center justify-center rounded-2xl border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-5 py-3 font-semibold whitespace-nowrap">
            ⚽ {{ __('New Team of the Season') }}
        </a>
        <a href="{{ route('admin.campaigns.create') }}"
           class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 font-semibold whitespace-nowrap">
            + {{ __('New Campaign') }}
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
