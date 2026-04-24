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
            @php $isAr = app()->getLocale() === 'ar'; @endphp
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur border border-white/20 px-3 py-1 text-white/80 mb-4
                        {{ $isAr ? 'text-xs font-semibold' : 'text-xs uppercase tracking-[0.2em]' }}">
                <span>🗳</span>
                <span>{{ __('Official voting') }}</span>
            </div>

            <div class="text-white/70 {{ $isAr ? 'text-xs font-semibold' : 'text-xs uppercase tracking-[0.2em]' }}">{{ $campaign->localized('title') }}</div>
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
            <div class="alert alert-error">
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
            {{-- Custom player picker: photo-first grid with live search.
                 Replaces the old <select> so voters see faces + jerseys
                 + positions, not just names. A hidden `player_id` input
                 carries the selection for form submission. --}}
            @php
                $playersForJs = $players->map(fn ($p) => [
                    'id'       => $p->id,
                    'name'     => $p->localized('name'),
                    'jersey'   => $p->jersey_number,
                    'position' => $p->position?->label(),
                    'photo'    => $p->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null,
                    'initial'  => mb_strtoupper(mb_substr($p->localized('name') ?? '?', 0, 1)),
                ])->values();
            @endphp

            <form method="post" action="{{ route('voting.club.start', $row->voting_link_token) }}"
                  x-data="playerPicker(@js($playersForJs))"
                  @submit="onSubmit"
                  class="space-y-5">
                @csrf
                <input type="hidden" name="player_id" :value="selected?.id ?? ''" required>

                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label class="text-sm font-bold text-ink-900 flex items-center gap-2">
                            <span class="inline-flex w-6 h-6 rounded-full bg-brand-600 text-white items-center justify-center text-xs font-black">1</span>
                            <span>{{ __('Select your name from the roster') }}</span>
                        </label>
                        <span class="text-xs text-ink-500 tabular-nums" x-text="filtered.length + ' / ' + players.length"></span>
                    </div>

                    {{-- Selection preview — big card once they pick someone --}}
                    <template x-if="selected">
                        <div class="mb-3 rounded-2xl bg-brand-50 border-2 border-brand-500 p-4 flex items-center gap-3">
                            <template x-if="selected.photo">
                                <img :src="selected.photo" :alt="selected.name"
                                     class="w-14 h-14 rounded-2xl object-cover border-2 border-white shadow-sm flex-shrink-0">
                            </template>
                            <template x-if="!selected.photo">
                                <div class="w-14 h-14 rounded-2xl bg-brand-600 text-white flex items-center justify-center text-xl font-extrabold flex-shrink-0"
                                     x-text="selected.initial"></div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] uppercase tracking-widest text-brand-700 font-semibold">{{ __('Your pick') }}</div>
                                <div class="font-extrabold text-ink-900 text-lg truncate" x-text="selected.name"></div>
                                <div class="text-xs text-ink-500 flex items-center gap-2 mt-0.5">
                                    <span x-show="selected.jersey" class="font-mono font-bold">#<span x-text="selected.jersey"></span></span>
                                    <span x-show="selected.position">· <span x-text="selected.position"></span></span>
                                </div>
                            </div>
                            <button type="button" @click="clear()"
                                    class="w-8 h-8 rounded-full bg-white border border-ink-200 hover:bg-ink-50 text-ink-500 hover:text-rose-600 flex items-center justify-center transition"
                                    :title="'{{ __('Clear selection') }}'">×</button>
                        </div>
                    </template>

                    {{-- Search --}}
                    <div class="relative mb-3">
                        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-ink-400">🔍</span>
                        <input type="text" x-model="query" @input="activeIndex = 0"
                               placeholder="{{ __('Search by name or jersey...') }}"
                               class="w-full rounded-xl border border-ink-200 bg-white ps-10 pe-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    </div>

                    {{-- Player grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[28rem] overflow-y-auto rounded-2xl border border-ink-200 bg-ink-50/30 p-2">
                        <template x-for="p in filtered" :key="p.id">
                            <button type="button" @click="pick(p)"
                                    class="flex items-center gap-3 rounded-xl bg-white border-2 p-3 text-start transition"
                                    :class="selected?.id === p.id
                                        ? 'border-brand-500 shadow-md'
                                        : 'border-ink-200 hover:border-brand-300 hover:bg-brand-50/40'">
                                <template x-if="p.photo">
                                    <img :src="p.photo" :alt="p.name"
                                         class="w-12 h-12 rounded-xl object-cover border-2 border-ink-100 flex-shrink-0">
                                </template>
                                <template x-if="!p.photo">
                                    <div class="w-12 h-12 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-base font-extrabold flex-shrink-0"
                                         x-text="p.initial"></div>
                                </template>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-ink-900 truncate" x-text="p.name"></div>
                                    <div class="text-xs text-ink-500 flex items-center gap-2 mt-0.5">
                                        <span x-show="p.jersey" class="font-mono font-bold">#<span x-text="p.jersey"></span></span>
                                        <span x-show="p.position && p.jersey">·</span>
                                        <span x-show="p.position" x-text="p.position"></span>
                                    </div>
                                </div>
                                {{-- Checkmark when selected --}}
                                <template x-if="selected?.id === p.id">
                                    <div class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </template>
                            </button>
                        </template>
                        <template x-if="filtered.length === 0">
                            <div class="col-span-full text-center text-ink-500 py-10 text-sm">
                                {{ __('No players match your search.') }}
                            </div>
                        </template>
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

            @push('scripts')
            <script>
                function playerPicker(players) {
                    return {
                        players,
                        selected: null,
                        query: '',
                        activeIndex: 0,
                        get filtered() {
                            const q = (this.query || '').trim().toLowerCase();
                            if (!q) return this.players;
                            return this.players.filter(p =>
                                (p.name || '').toLowerCase().includes(q) ||
                                String(p.jersey ?? '').includes(q) ||
                                (p.position || '').toLowerCase().includes(q)
                            );
                        },
                        pick(p) { this.selected = p; },
                        clear() { this.selected = null; },
                        onSubmit(e) {
                            if (!this.selected) {
                                e.preventDefault();
                                alert('{{ __('Please select your name first.') }}');
                            }
                        },
                    };
                }
            </script>
            @endpush
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
            @php $support = \App\Modules\Shared\Support\Branding::contactEmail(); @endphp
            <a href="mailto:{{ $support }}" class="text-brand-700 font-semibold hover:underline">{{ $support }}</a>
        </p>
    </div>
</div>
@endsection
