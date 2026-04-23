@extends('voting::club._layout')
@section('title', __('Cast your vote'))

@section('content')
@php
    // Flatten TOS data for the client: per-slot array of {club, players}.
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
                    'photo'  => $p->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null,
                ])->values(),
            ];
        }
    }
@endphp

<div x-data="clubBallot(@js($tosForJs))" x-cloak>

    {{-- ── Hero ──────────────────────────────────────────────── --}}
    <section class="rounded-3xl bg-gradient-to-br from-brand-800 via-brand-700 to-brand-500 text-white p-6 md:p-8 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                <div>
                    <div class="text-xs uppercase tracking-[0.2em] text-white/70">{{ $campaign->localized('title') }}</div>
                    <h1 class="text-3xl md:text-4xl font-extrabold mt-1">{{ __('Your vote counts') }}</h1>
                </div>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur rounded-2xl px-4 py-3">
                    <div class="text-center">
                        <div class="text-[10px] uppercase tracking-wider text-white/70">{{ __('Progress') }}</div>
                        <div class="text-2xl font-extrabold tabular-nums">
                            <span x-text="filledCount"></span><span class="text-white/60 text-lg">/<span x-text="totalSlots"></span></span>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Voter identity chip — glass variant for dark hero. --}}
            @include('voting::club._partials.voter-card', ['voter' => $voter, 'club' => $club, 'variant' => 'glass'])
        </div>
    </section>

    @if($errors->any())
        <div class="mt-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4 text-sm">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('voting.club.submit', $row->voting_link_token) }}" class="mt-6 space-y-6"
          @submit="onSubmit">
        @csrf

        {{-- ── Section 1: Best Saudi — only if the admin linked a
             voting_category with award_type=best_saudi ─────────── --}}
        @if($showSaudi)
        <section class="card !p-0 overflow-hidden">
            <header class="p-5 md:p-6 flex items-center justify-between gap-3 border-b border-ink-100 bg-gradient-to-r from-brand-50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-brand-600 text-white flex items-center justify-center text-xl shadow-sm">🏆</div>
                    <div>
                        <h2 class="text-lg font-bold text-ink-900 leading-tight">{{ __('Best Saudi Player') }}</h2>
                        <p class="text-xs text-ink-500 mt-0.5">
                            {{ __('Pick one nominee') }} · {{ $saudi->count() }} {{ __('candidates') }}
                        </p>
                    </div>
                </div>
                <span class="text-xs font-semibold rounded-full px-2.5 py-1"
                      :class="saudiPicked ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-500'"
                      x-text="saudiPicked ? '✓ {{ __('Done') }}' : '{{ __('Pending') }}'"></span>
            </header>
            <div class="p-5 md:p-6">
                @if($saudi->isEmpty())
                    <div class="text-center text-ink-500 py-12 border-2 border-dashed border-ink-200 rounded-2xl">
                        {{ __('No eligible candidates for this award.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                         @change="saudiPicked = !!document.querySelector('input[name=best_saudi_player_id]:checked')">
                        @foreach($saudi as $p)
                            @include('voting::club._partials.candidate-card', [
                                'input' => ['type' => 'radio', 'name' => 'best_saudi_player_id', 'value' => $p->id],
                                'player' => $p,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ── Section 2: Best Foreign — only if configured ───── --}}
        @if($showForeign)
        <section class="card !p-0 overflow-hidden">
            <header class="p-5 md:p-6 flex items-center justify-between gap-3 border-b border-ink-100 bg-gradient-to-r from-amber-50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm">🌍</div>
                    <div>
                        <h2 class="text-lg font-bold text-ink-900 leading-tight">{{ __('Best Foreign Player') }}</h2>
                        <p class="text-xs text-ink-500 mt-0.5">
                            {{ __('Pick one nominee') }} · {{ $foreign->count() }} {{ __('candidates') }}
                        </p>
                    </div>
                </div>
                <span class="text-xs font-semibold rounded-full px-2.5 py-1"
                      :class="foreignPicked ? 'bg-amber-100 text-amber-700' : 'bg-ink-100 text-ink-500'"
                      x-text="foreignPicked ? '✓ {{ __('Done') }}' : '{{ __('Pending') }}'"></span>
            </header>
            <div class="p-5 md:p-6">
                @if($foreign->isEmpty())
                    <div class="text-center text-ink-500 py-12 border-2 border-dashed border-ink-200 rounded-2xl">
                        {{ __('No eligible candidates for this award.') }}
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                         @change="foreignPicked = !!document.querySelector('input[name=best_foreign_player_id]:checked')">
                        @foreach($foreign as $p)
                            @include('voting::club._partials.candidate-card', [
                                'input' => ['type' => 'radio', 'name' => 'best_foreign_player_id', 'value' => $p->id],
                                'player' => $p,
                                'accent' => 'amber',
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
        @endif

        {{-- ── Section 3: Team of the Season — only if configured --}}
        @if($showTos)
        <section class="card !p-0 overflow-hidden">
            <header class="p-5 md:p-6 flex items-center justify-between gap-3 border-b border-ink-100 bg-gradient-to-r from-emerald-50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xl shadow-sm">⚽</div>
                    <div>
                        <h2 class="text-lg font-bold text-ink-900 leading-tight">{{ __('Team of the Season') }}</h2>
                        <p class="text-xs text-ink-500 mt-0.5">
                            {{ __('Tap a slot on the pitch to pick a player') }} · 4-3-3
                        </p>
                    </div>
                </div>
                <span class="text-xs font-semibold rounded-full px-2.5 py-1 tabular-nums"
                      :class="total === 11 ? 'bg-emerald-100 text-emerald-700' : 'bg-ink-100 text-ink-500'">
                    <span x-text="total"></span>/11
                </span>
            </header>

            <div class="p-5 md:p-6">
                <div class="rounded-3xl bg-gradient-to-b from-emerald-700 to-emerald-900 p-5 md:p-8 relative overflow-hidden">
                    {{-- decorative pitch grid --}}
                    <div class="absolute inset-0 opacity-15"
                         style="background:
                            repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);"></div>
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="absolute inset-x-0 top-1/2 border-t-2 border-white/20"></div>
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-20 h-20 rounded-full border-2 border-white/20"></div>
                    </div>

                    @foreach(['attack','midfield','defense','goalkeeper'] as $slot)
                        @php
                            $count = App\Modules\Voting\Support\Formation::slots()[$slot];
                            $meta  = [
                                'attack'     => ['icon' => '⚡', 'label' => __('Attack'),     'color' => 'from-rose-500 to-rose-600'],
                                'midfield'   => ['icon' => '⚙️', 'label' => __('Midfield'),   'color' => 'from-emerald-500 to-emerald-600'],
                                'defense'    => ['icon' => '🛡', 'label' => __('Defense'),    'color' => 'from-blue-500 to-blue-600'],
                                'goalkeeper' => ['icon' => '🧤', 'label' => __('Goalkeeper'), 'color' => 'from-amber-500 to-amber-600'],
                            ][$slot];
                        @endphp
                        <div class="relative mb-3 last:mb-0">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-white/80 mb-2 flex items-center gap-1">
                                <span>{{ $meta['icon'] }}</span> <span>{{ $meta['label'] }}</span>
                                <span class="text-white/50">— {{ $count }}</span>
                            </div>
                            <div class="grid gap-2" style="grid-template-columns: repeat({{ $count }}, minmax(0,1fr));">
                                @for($i = 0; $i < $count; $i++)
                                    <button type="button" @click="openSlot('{{ $slot }}', {{ $i }})"
                                            class="relative rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur border-2 border-dashed border-white/30 p-3 text-center transition min-h-[92px] flex flex-col items-center justify-center text-white overflow-hidden group">
                                        <template x-if="picks.{{ $slot }}[{{ $i }}]">
                                            <div class="relative w-full">
                                                <div class="absolute inset-0 bg-gradient-to-br {{ $meta['color'] }} opacity-90 -m-3 rounded-xl"></div>
                                                <div class="relative">
                                                    <template x-if="picks.{{ $slot }}[{{ $i }}]?.photo">
                                                        <img :src="picks.{{ $slot }}[{{ $i }}]?.photo" class="w-10 h-10 rounded-full mx-auto object-cover border-2 border-white">
                                                    </template>
                                                    <template x-if="!picks.{{ $slot }}[{{ $i }}]?.photo">
                                                        <div class="w-10 h-10 rounded-full mx-auto bg-white/20 flex items-center justify-center text-base mt-0.5">👤</div>
                                                    </template>
                                                    <div class="font-bold text-[11px] truncate mt-1.5"
                                                         x-text="picks.{{ $slot }}[{{ $i }}]?.name"></div>
                                                    <div class="text-[9px] text-white/80 truncate"
                                                         x-text="picks.{{ $slot }}[{{ $i }}]?.club_name"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!picks.{{ $slot }}[{{ $i }}]">
                                            <div class="text-white/70 group-hover:text-white group-hover:scale-110 transition">
                                                <div class="text-2xl leading-none">＋</div>
                                                <div class="text-[9px] uppercase tracking-wider mt-1">{{ __('Add') }}</div>
                                            </div>
                                        </template>
                                    </button>
                                    <input type="hidden" name="lineup[{{ $slot }}][]"
                                           :value="picks.{{ $slot }}[{{ $i }}]?.player_id ?? ''">
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Slot-picker popup ──────────────────────── --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false"></div>
                    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
                        <header class="p-5 bg-gradient-to-r from-emerald-600 to-emerald-800 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-wider text-white/70">{{ __('Pick for') }}</div>
                                    <h3 class="text-xl font-extrabold" x-text="currentSlotLabel"></h3>
                                </div>
                                <button type="button" @click="open = false"
                                        class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-xl">&times;</button>
                            </div>
                            <div class="mt-3" x-show="!selectedClub">
                                <input type="text" x-model="clubQuery"
                                       placeholder="{{ __('Search club...') }}"
                                       class="w-full rounded-xl bg-white/10 border border-white/30 text-white placeholder:text-white/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-white/40">
                            </div>
                            <div class="mt-3" x-show="selectedClub">
                                <button type="button" @click="selectedClub = null; clubQuery = ''"
                                        class="text-xs text-white/90 hover:underline">← {{ __('Change club') }}</button>
                            </div>
                        </header>

                        <div class="overflow-y-auto flex-1 p-4 bg-ink-50/40">
                            <div x-show="!selectedClub" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <template x-for="club in filteredClubs" :key="club.club_id">
                                    <button type="button" @click="selectedClub = club"
                                            class="flex items-center justify-between rounded-xl border border-ink-200 bg-white hover:border-brand-400 hover:bg-brand-50 p-3 text-start transition">
                                        <span class="font-semibold" x-text="club.club_name"></span>
                                        <span class="text-xs text-ink-500" x-text="club.players.length + ' {{ __('players') }}'"></span>
                                    </button>
                                </template>
                                <template x-if="filteredClubs.length === 0">
                                    <div class="col-span-full text-center text-ink-500 py-10">{{ __('No clubs match.') }}</div>
                                </template>
                            </div>

                            <div x-show="selectedClub" class="space-y-2">
                                <template x-for="p in selectedClub?.players || []" :key="p.id">
                                    <button type="button" @click="choose(p)"
                                            class="w-full flex items-center gap-3 rounded-xl border border-ink-200 bg-white hover:border-brand-400 hover:bg-brand-50 p-3 text-start transition"
                                            :class="isAlreadyPicked(p.id) ? 'opacity-40 cursor-not-allowed' : ''"
                                            :disabled="isAlreadyPicked(p.id)">
                                        <template x-if="p.photo">
                                            <img :src="p.photo" class="w-10 h-10 rounded-full object-cover border border-ink-200">
                                        </template>
                                        <template x-if="!p.photo">
                                            <div class="w-10 h-10 rounded-full bg-ink-100 flex items-center justify-center text-lg">👤</div>
                                        </template>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold truncate" x-text="p.name"></div>
                                            <div class="text-xs text-ink-500" x-show="p.jersey">#<span x-text="p.jersey"></span></div>
                                        </div>
                                        <span x-show="isAlreadyPicked(p.id)" class="text-xs text-brand-700 font-semibold">✓ {{ __('picked') }}</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </section>
        @endif

        {{-- ── Sticky submit bar ─────────────────────────────── --}}
        <div class="sticky bottom-0 inset-x-0 z-20 pt-4 pb-2 -mx-4 px-4 md:mx-0 md:px-0 bg-gradient-to-t from-white via-white/95 to-transparent">
            <div class="rounded-2xl border border-ink-200 bg-white shadow-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="text-sm">
                    <div class="font-bold text-ink-900" x-text="isReady ? '{{ __('Ready to submit') }}' : '{{ __('Complete all selections to submit') }}'"></div>
                    <div class="text-xs text-ink-500 mt-1 flex items-center gap-3 flex-wrap">
                        @if($showSaudi)
                            <span class="flex items-center gap-1">
                                <span :class="saudiPicked ? 'text-brand-700' : 'text-ink-400'" x-text="saudiPicked ? '✓' : '○'"></span>
                                <span>{{ __('Saudi') }}</span>
                            </span>
                        @endif
                        @if($showForeign)
                            <span class="flex items-center gap-1">
                                <span :class="foreignPicked ? 'text-amber-600' : 'text-ink-400'" x-text="foreignPicked ? '✓' : '○'"></span>
                                <span>{{ __('Foreign') }}</span>
                            </span>
                        @endif
                        @if($showTos)
                            <span class="flex items-center gap-1">
                                <span :class="total === 11 ? 'text-emerald-700' : 'text-ink-400'" x-text="total === 11 ? '✓' : '○'"></span>
                                <span>TOS (<span x-text="total"></span>/11)</span>
                            </span>
                        @endif
                    </div>
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-6 py-3 font-bold shadow-lg transition"
                        :class="isReady
                            ? 'bg-brand-600 hover:bg-brand-700 text-white'
                            : 'bg-ink-200 text-ink-500 cursor-not-allowed'"
                        :disabled="!isReady">
                    <span aria-hidden="true">✓</span>
                    <span>{{ __('Submit my vote') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function clubBallot(tosData) {
    // These flags reflect what the server rendered — isReady adapts
    // to the awards actually present on the page.
    const SHOW_SAUDI   = @json($showSaudi);
    const SHOW_FOREIGN = @json($showForeign);
    const SHOW_TOS     = @json($showTos);
    const TOTAL_SLOTS  = (SHOW_SAUDI ? 1 : 0) + (SHOW_FOREIGN ? 1 : 0) + (SHOW_TOS ? 11 : 0);

    return {
        tosData,
        picks: { goalkeeper: [], defense: [], midfield: [], attack: [] },
        open: false,
        currentSlot: null,
        currentIndex: 0,
        selectedClub: null,
        clubQuery: '',
        saudiPicked: false,
        foreignPicked: false,
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
            return q ? list.filter(c => (c.club_name || '').toLowerCase().includes(q)) : list;
        },
        get total() {
            return Object.values(this.picks).reduce((s, arr) => s + arr.filter(Boolean).length, 0);
        },
        get totalSlots() { return TOTAL_SLOTS; },
        get filledCount() {
            return this.total + (this.saudiPicked ? 1 : 0) + (this.foreignPicked ? 1 : 0);
        },
        get isReady() {
            // Only require the awards that are actually on the page.
            if (SHOW_SAUDI   && !this.saudiPicked)   return false;
            if (SHOW_FOREIGN && !this.foreignPicked) return false;
            if (SHOW_TOS     && this.total !== 11)   return false;
            return true;
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
                player_id: p.id, name: p.name, club_name: this.selectedClub.club_name, photo: p.photo,
            };
            this.open = false;
        },
        isAlreadyPicked(id) {
            return Object.values(this.picks).some(arr => arr.some(pick => pick && pick.player_id === id));
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
