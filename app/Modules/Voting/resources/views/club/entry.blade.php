@extends('voting::club._layout')
@section('title', __('Cast your vote'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ── Hero card ───────────────────────────────────────────
         Brand-gradient hero that mirrors the ballot page. Gives the
         landing screen the same polish as the rest of the flow so the
         voter never feels like they dropped to a plain form. --}}
    <div class="relative rounded-3xl overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500"></div>
        <div class="absolute inset-0 opacity-15"
             style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative p-8 md:p-10 text-white text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur border border-white/20 px-3 py-1 text-xs uppercase tracking-[0.2em] text-white/80 mb-4">
                <span>🗳</span>
                <span>{{ __('Official voting') }}</span>
            </div>

            <div class="text-xs uppercase tracking-[0.2em] text-white/70">{{ $campaign->localized('title') }}</div>
            <h1 class="text-3xl md:text-4xl font-extrabold mt-2">{{ $club->localized('name') }}</h1>
            <p class="text-white/85 mt-4 max-w-lg mx-auto leading-7">
                {{ __('Pick your name from the list to start voting for this season\'s awards.') }}
            </p>

            {{-- Mini stats so the landing feels informative --}}
            <div class="mt-6 inline-flex items-center gap-4 rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-5 py-3 text-sm">
                <div class="text-center">
                    <div class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Closes') }}</div>
                    <div class="font-bold">{{ $campaign->end_at->format('Y-m-d') }}</div>
                </div>
                <div class="w-px h-8 bg-white/30"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Roster') }}</div>
                    <div class="font-bold tabular-nums">{{ $players->count() }} {{ __('players') }}</div>
                </div>
                @if($row->max_voters)
                    <div class="w-px h-8 bg-white/30"></div>
                    <div class="text-center">
                        <div class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Voted') }}</div>
                        <div class="font-bold tabular-nums">{{ $row->current_voters_count }} / {{ $row->max_voters }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Name picker card ───────────────────────────────────── --}}
    <div class="card space-y-5">
        @if($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 p-3 text-sm flex items-start gap-2">
                <span class="text-lg leading-none">⚠️</span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if($players->isEmpty())
            <div class="text-center py-10 border-2 border-dashed border-amber-300 bg-amber-50/40 rounded-2xl space-y-2">
                <div class="text-4xl">👥</div>
                <div class="font-bold text-amber-900">{{ __('No eligible players yet') }}</div>
                <p class="text-sm text-amber-800 max-w-sm mx-auto leading-7">
                    {{ __('No eligible players found for this club. Please contact the organisers.') }}
                </p>
            </div>
        @else
            <form method="post" action="{{ route('voting.club.start', $row->voting_link_token) }}" class="space-y-5"
                  x-data="{ selected: '' }">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-ink-900 mb-2 flex items-center gap-2">
                        <span class="inline-flex w-6 h-6 rounded-full bg-brand-600 text-white items-center justify-center text-xs font-black">1</span>
                        <span>{{ __('Select your name from the roster') }}</span>
                    </label>

                    <div class="relative">
                        <select name="player_id" required x-model="selected"
                                class="w-full appearance-none rounded-xl border-2 border-ink-200 bg-white ps-12 pe-10 py-3.5 text-base font-semibold text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                            <option value="">— {{ __('Select your name') }} —</option>
                            @foreach($players as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->localized('name') }}@if($p->jersey_number) &nbsp;· #{{ $p->jersey_number }}@endif
                                </option>
                            @endforeach
                        </select>
                        {{-- Decorative avatar icon on the leading edge --}}
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold">
                                👤
                            </div>
                        </div>
                        {{-- Dropdown arrow --}}
                        <div class="absolute inset-y-0 end-0 flex items-center pe-4 pointer-events-none text-ink-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.2 7.2a1 1 0 011.4 0L10 10.6l3.4-3.4a1 1 0 111.4 1.4l-4.1 4.1a1 1 0 01-1.4 0L5.2 8.6a1 1 0 010-1.4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3.5 text-base font-bold shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-brand-600"
                        :disabled="!selected">
                    <span aria-hidden="true">🗳</span>
                    <span>{{ __('Start voting') }}</span>
                    <span aria-hidden="true">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                </button>

                <p class="text-xs text-ink-500 text-center pt-2 leading-6">
                    🔒 {{ __('By proceeding you confirm that you are the selected player. Your vote is private and counted once.') }}
                </p>
            </form>
        @endif
    </div>

    {{-- ── Help card ─────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-ink-200 bg-white p-5 text-center text-sm text-ink-600">
        <div class="flex items-center justify-center gap-2 mb-1 text-ink-900 font-semibold">
            <span>💬</span>
            <span>{{ __('Need help?') }}</span>
        </div>
        <p class="text-ink-500">
            {{ __('Questions? Contact the organisers at') }}
            <a href="mailto:contact@sfpa.sa" class="text-brand-700 font-semibold hover:underline">contact@sfpa.sa</a>
        </p>
    </div>
</div>
@endsection
