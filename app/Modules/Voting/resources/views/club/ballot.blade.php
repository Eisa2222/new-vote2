@extends('voting::club._layout')
@section('title', __('Cast your vote'))

@section('content')
@php
    // Flatten TOS data into a compact structure the client can use
    // without re-fetching — each slot gets a `clubs` list and each
    // club a `players` list.
    $tosForJs = [];
    foreach ($tos as $slot => $byClub) {
        $tosForJs[$slot] = [];
        foreach ($byClub as $clubId => $players) {
            $club = $players->first()?->club;
            if (!$club) continue;
            $tosForJs[$slot][] = [
                'club_id'   => $clubId,
                'club_name' => $club->localized('name'),
                'players'   => $players->map(fn ($p) => [
                    'id'     => $p->id,
                    'name'   => $p->localized('name'),
                    'jersey' => $p->jersey_number,
                ])->values(),
            ];
        }
    }
@endphp

<div x-data="clubBallot(@js($tosForJs))" x-cloak>
    <div class="mb-6 text-center">
        <div class="text-sm uppercase tracking-widest text-brand-700 font-semibold">{{ $campaign->localized('title') }}</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 mt-1">{{ __('Your vote') }}</h1>
        <p class="text-ink-500 text-sm mt-2">
            {{ __('Hello') }} <strong>{{ $voter->localized('name') }}</strong> · {{ $club->localized('name') }}
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('voting.club.submit', $row->voting_link_token) }}" class="space-y-6"
          @submit="onSubmit">
        @csrf

        {{-- ── BEST SAUDI ────────────────────────────────────────── --}}
        <div class="card space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold flex items-center gap-2"><span>🏆</span> {{ __('Best Saudi Player') }}</h2>
                <span class="text-xs text-ink-500">{{ $saudi->count() }} {{ __('candidates') }}</span>
            </div>
            @if($saudi->isEmpty())
                <div class="text-center text-ink-500 py-6">{{ __('No eligible candidates.') }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($saudi as $p)
                        <label class="flex items-center gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 transition">
                            <input type="radio" name="best_saudi_player_id" value="{{ $p->id }}" required
                                   class="w-4 h-4 rounded-full border-ink-300 text-brand-600 focus:ring-brand-500">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold truncate">{{ $p->localized('name') }}</div>
                                <div class="text-xs text-ink-500 truncate">
                                    {{ $p->club?->localized('name') }}
                                    @if($p->jersey_number) · #{{ $p->jersey_number }} @endif
                                    · {{ $p->position?->label() }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── BEST FOREIGN ──────────────────────────────────────── --}}
        <div class="card space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold flex items-center gap-2"><span>🌍</span> {{ __('Best Foreign Player') }}</h2>
                <span class="text-xs text-ink-500">{{ $foreign->count() }} {{ __('candidates') }}</span>
            </div>
            @if($foreign->isEmpty())
                <div class="text-center text-ink-500 py-6">{{ __('No eligible candidates.') }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($foreign as $p)
                        <label class="flex items-center gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 transition">
                            <input type="radio" name="best_foreign_player_id" value="{{ $p->id }}" required
                                   class="w-4 h-4 rounded-full border-ink-300 text-brand-600 focus:ring-brand-500">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold truncate">{{ $p->localized('name') }}</div>
                                <div class="text-xs text-ink-500 truncate">
                                    {{ $p->club?->localized('name') }}
                                    @if($p->jersey_number) · #{{ $p->jersey_number }} @endif
                                    · {{ $p->position?->label() }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── TEAM OF THE SEASON (interactive pitch) ────────────── --}}
        <div class="card space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-lg font-bold flex items-center gap-2"><span>⚽</span> {{ __('Team of the Season') }}</h2>
                <span class="text-xs font-semibold" :class="total === 11 ? 'text-brand-700' : 'text-ink-500'">
                    <span x-text="total"></span> / 11 {{ __('players selected') }}
                </span>
            </div>

            <div class="rounded-3xl bg-gradient-to-b from-brand-700 to-brand-900 p-6 relative overflow-hidden">
                {{-- pitch lines (decorative) --}}
                <div class="absolute inset-0 opacity-10"
                     style="background:
                        repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);"></div>

                @foreach(['attack','midfield','defense','goalkeeper'] as $slot)
                    @php
                        $count = App\Modules\Voting\Support\Formation::slots()[$slot];
                        $labels = [
                            'attack'     => ['icon' => '⚡', 'label' => __('Attack')],
                            'midfield'   => ['icon' => '⚙️', 'label' => __('Midfield')],
                            'defense'    => ['icon' => '🛡', 'label' => __('Defense')],
                            'goalkeeper' => ['icon' => '🧤', 'label' => __('Goalkeeper')],
                        ][$slot];
                    @endphp
                    <div class="relative mb-3 last:mb-0">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-white/80 mb-2 flex items-center gap-1">
                            <span>{{ $labels['icon'] }}</span> <span>{{ $labels['label'] }} ({{ $count }})</span>
                        </div>
                        <div class="grid gap-2" style="grid-template-columns: repeat({{ $count }}, minmax(0,1fr));">
                            @for($i = 0; $i < $count; $i++)
                                <button type="button" @click="openSlot('{{ $slot }}', {{ $i }})"
                                        class="rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur border border-white/30 p-3 text-center transition min-h-[70px] flex flex-col items-center justify-center text-white">
                                    <template x-if="picks.{{ $slot }}[{{ $i }}]">
                                        <div>
                                            <div class="font-bold text-xs truncate max-w-[100px]"
                                                 x-text="picks.{{ $slot }}[{{ $i }}]?.name"></div>
                                            <div class="text-[10px] text-white/80 truncate max-w-[100px]"
                                                 x-text="picks.{{ $slot }}[{{ $i }}]?.club_name"></div>
                                        </div>
                                    </template>
                                    <template x-if="!picks.{{ $slot }}[{{ $i }}]">
                                        <div class="text-2xl">＋</div>
                                    </template>
                                </button>
                                <input type="hidden" name="lineup[{{ $slot }}][]"
                                       :value="picks.{{ $slot }}[{{ $i }}]?.player_id ?? ''">
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── Slot-picker popup ──────────────────────────────── --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="open = false"></div>
                    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
                        <header class="p-5 bg-gradient-to-r from-brand-700 to-brand-500 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-white/70">{{ __('Pick for') }}</div>
                                    <h3 class="text-xl font-extrabold" x-text="currentSlotLabel"></h3>
                                </div>
                                <button type="button" @click="open = false"
                                        class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30">&times;</button>
                            </div>
                            <div class="mt-3" x-show="!selectedClub">
                                <input type="text" x-model="clubQuery"
                                       placeholder="{{ __('Search club...') }}"
                                       class="w-full rounded-xl bg-white/10 border border-white/30 text-white placeholder:text-white/60 px-3 py-2 text-sm focus:outline-none">
                            </div>
                            <div class="mt-3" x-show="selectedClub">
                                <button type="button" @click="selectedClub = null; clubQuery = ''"
                                        class="text-xs text-white/90 hover:underline">← {{ __('Change club') }}</button>
                            </div>
                        </header>

                        <div class="overflow-y-auto flex-1 p-4">
                            {{-- Step 1: pick club --}}
                            <div x-show="!selectedClub" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <template x-for="club in filteredClubs" :key="club.club_id">
                                    <button type="button" @click="selectedClub = club"
                                            class="flex items-center justify-between rounded-xl border border-ink-200 hover:border-brand-400 hover:bg-brand-50 p-3 text-start">
                                        <span class="font-semibold" x-text="club.club_name"></span>
                                        <span class="text-xs text-ink-500" x-text="club.players.length + ' {{ __('players') }}'"></span>
                                    </button>
                                </template>
                                <template x-if="filteredClubs.length === 0">
                                    <div class="col-span-full text-center text-ink-500 py-8">{{ __('No clubs match.') }}</div>
                                </template>
                            </div>

                            {{-- Step 2: pick player --}}
                            <div x-show="selectedClub" class="space-y-2">
                                <template x-for="p in selectedClub?.players || []" :key="p.id">
                                    <button type="button" @click="choose(p)"
                                            class="w-full flex items-center justify-between rounded-xl border border-ink-200 hover:border-brand-400 hover:bg-brand-50 p-3 text-start"
                                            :class="isAlreadyPicked(p.id) ? 'opacity-40 cursor-not-allowed' : ''"
                                            :disabled="isAlreadyPicked(p.id)">
                                        <span>
                                            <span class="font-semibold" x-text="p.name"></span>
                                            <span class="text-xs text-ink-500 ms-2" x-show="p.jersey">#<span x-text="p.jersey"></span></span>
                                        </span>
                                        <span x-show="isAlreadyPicked(p.id)" class="text-xs text-brand-700">✓ {{ __('picked') }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- ── SUBMIT ───────────────────────────────────────────── --}}
        <div class="card flex items-center justify-between gap-2">
            <div class="text-sm">
                <div class="font-semibold" x-text="isReady ? '{{ __('Ready to submit') }}' : '{{ __('Complete all selections to submit') }}'"></div>
                <div class="text-xs text-ink-500">
                    🏆 <span x-text="isReady ? '✓' : '—'"></span> · 🌍 <span x-text="isReady ? '✓' : '—'"></span> · ⚽ <span x-text="total"></span>/11
                </div>
            </div>
            <button class="btn-save" :disabled="!isReady">
                <span aria-hidden="true">✓</span>
                <span>{{ __('Submit vote') }}</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function clubBallot(tosData) {
    return {
        tosData,                    // { slot: [ { club_id, club_name, players:[...] } ] }
        picks: { goalkeeper: [], defense: [], midfield: [], attack: [] },
        open: false,
        currentSlot: null,
        currentIndex: 0,
        selectedClub: null,
        clubQuery: '',
        slotLabels: {
            goalkeeper: '{{ __('Goalkeeper') }}',
            defense:    '{{ __('Defense') }}',
            midfield:   '{{ __('Midfield') }}',
            attack:     '{{ __('Attack') }}',
        },
        get currentSlotLabel() { return this.slotLabels[this.currentSlot] || ''; },
        get filteredClubs() {
            const q = (this.clubQuery || '').toLowerCase().trim();
            const list = (this.tosData[this.currentSlot] || []);
            if (!q) return list;
            return list.filter(c => (c.club_name || '').toLowerCase().includes(q));
        },
        get total() {
            return Object.values(this.picks).reduce((s, arr) => s + arr.filter(Boolean).length, 0);
        },
        get isReady() {
            if (this.total !== 11) return false;
            const saudi   = document.querySelector('input[name="best_saudi_player_id"]:checked');
            const foreign = document.querySelector('input[name="best_foreign_player_id"]:checked');
            return !!(saudi && foreign);
        },
        openSlot(slot, idx) {
            this.currentSlot = slot;
            this.currentIndex = idx;
            this.selectedClub = null;
            this.clubQuery = '';
            this.open = true;
        },
        choose(p) {
            if (this.isAlreadyPicked(p.id)) return;
            this.picks[this.currentSlot][this.currentIndex] = {
                player_id: p.id,
                name: p.name,
                club_name: this.selectedClub.club_name,
            };
            this.open = false;
        },
        isAlreadyPicked(id) {
            return Object.values(this.picks)
                .some(arr => arr.some(pick => pick && pick.player_id === id));
        },
        onSubmit(e) {
            if (!this.isReady) {
                e.preventDefault();
                alert('{{ __('Please complete all selections before submitting.') }}');
            }
        },
    };
}
</script>
@endpush
@endsection
