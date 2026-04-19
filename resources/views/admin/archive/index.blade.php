@extends('layouts.admin')

@section('title', __('Archive'))
@section('page_title', __('Archive'))
@section('page_description', __('Restore or permanently delete items that were previously archived.'))

@section('content')
@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
    @forelse($tiles as $t)
        <a href="{{ $t['href'] }}"
           class="group relative overflow-hidden rounded-3xl bg-white border border-ink-200 p-6 shadow-sm transition hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r {{ $t['color'] }}"></div>
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm text-ink-500">{{ $t['label'] }}</div>
                    <div class="text-4xl font-extrabold mt-3 text-ink-900 tabular-nums">{{ $t['count'] }}</div>
                    <div class="text-[11px] text-ink-500 mt-1 uppercase tracking-wider">{{ __('archived') }}</div>
                </div>
                <div class="text-3xl">{{ $t['icon'] }}</div>
            </div>
            <div class="mt-4 text-xs text-brand-700 inline-flex items-center gap-1">
                {{ __('Open archive') }}
                <span aria-hidden="true">{{ $dir === 'rtl' ? '←' : '→' }}</span>
            </div>
        </a>
    @empty
        <div class="col-span-full rounded-3xl border border-ink-200 bg-white p-12 text-center text-ink-500">
            {{ __('You do not have permission to view any archive section.') }}
        </div>
    @endforelse
</div>

<div class="mt-8 rounded-3xl bg-brand-50 border border-brand-200 p-5 text-sm text-brand-800">
    💡 {{ __('Items in the archive are not lost — they can be restored at any time. Permanent deletion is only available to super administrators.') }}
</div>
@endsection
