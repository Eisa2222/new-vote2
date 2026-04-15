@extends('layouts.admin')

@section('title', __('Results'))
@section('page_title', __('Results'))
@section('page_description', __('Approve, hide or announce campaign results'))

@section('content')
<div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
    @forelse($campaigns as $c)
        @php
            $r = $c->result;
            $visibility = $c->results_visibility?->value ?? 'hidden';
            $visClass = [
                'hidden'    => 'bg-gray-100 text-gray-700',
                'approved'  => 'bg-blue-100 text-blue-700',
                'announced' => 'bg-emerald-100 text-emerald-700',
            ][$visibility] ?? 'bg-gray-100 text-gray-700';
        @endphp
        <a href="{{ route('results.show', $c) }}"
           class="block rounded-2xl border border-gray-200 p-5 hover:border-emerald-400 hover:shadow-md transition">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <div class="font-bold text-lg">{{ $c->localized('title') }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ __('Status') }}: {{ $c->status->value }} ·
                        {{ __('Votes') }}: {{ $c->votes()->count() }}
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $visClass }}">
                        {{ __('Visibility') }}: {{ $visibility }}
                    </span>
                    @if($r)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                            {{ $r->status->value }}
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                            {{ __('Not calculated') }}
                        </span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-16 text-center text-gray-400">{{ __('No results to manage yet.') }}</div>
    @endforelse
    <div>{{ $campaigns->links() }}</div>
</div>
@endsection
