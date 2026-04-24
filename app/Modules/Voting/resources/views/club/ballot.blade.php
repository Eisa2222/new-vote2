@extends('voting::club._layout')
@section('title', __('Cast your vote'))

@section('content')
    @php
        // Everything the Alpine popup needs, shipped in one JSON bundle.
        // For each slot-type (best_saudi / best_foreign / tos-position) we
        // pre-group by club so the popup can render "clubs → players".
        $toJsBucket = function ($byClub) {
            $out = [];
            foreach ($byClub as $clubId => $players) {
                $first = $players->first();
                $club = $first?->club;
                if (!$club) {
                    continue;
                }
                $out[] = [
                    'club_id' => $clubId,
                    'club_name' => $club->localized('name'),
                    'players' => $players
                        ->map(
                            fn($p) => [
                                'id' => $p->id,
                                'name' => $p->localized('name'),
                                'jersey' => $p->jersey_number,
                                // Localized position label (e.g. "هجوم" / "Attack")
                                // so the popup row can show name · position · #jersey
                                // in one glance, matching the admin roster layout.
                                'position' => $p->position?->label(),
                                'photo' => $p->photo_path
                                    ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                                    : null,
                            ],
                        )
                        ->values(),
                ];
            }
            return $out;
        };

        $jsData = [
            'best_saudi' => $toJsBucket($saudiByClub),
            'best_foreign' => $toJsBucket($foreignByClub),
        ];
        foreach ($tos ?? [] as $pos => $byClub) {
            $jsData['tos_' . $pos] = $toJsBucket($byClub);
        }
    @endphp

    @php $isAr = app()->getLocale() === 'ar'; @endphp

    <div x-data="clubBallot(@js($jsData))" x-cloak>

        {{-- ── Hero ──────────────────────────────────────────────── --}}
        <section
            class="rounded-3xl bg-gradient-to-br from-brand-800 via-brand-700 to-brand-500 text-white p-6 md:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;">
            </div>
            <div class="relative">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-5">
                    <div class="min-w-0">
                        <div
                            class="{{ $isAr ? 'text-xs font-semibold' : 'text-xs uppercase tracking-[0.2em]' }} text-white/70 truncate">
                            {{ $campaign->localized('title') }}
                        </div>
                        <h1 class="text-3xl md:text-4xl font-extrabold mt-1">{{ __('Your vote counts') }}</h1>
                        <p class="text-white/80 text-sm mt-2 max-w-md leading-6">
                            {{ __('Complete the questions below. You can change your picks before submitting.') }}
                        </p>
                    </div>
                    {{-- Progress ring — more expressive than a plain ratio
                     number; gives the voter visual reward as picks fill. --}}
                    <div class="flex items-center gap-3 bg-white/10 backdrop-blur rounded-2xl px-4 py-3 self-start">
                        <div class="relative w-14 h-14 flex-shrink-0">
                            <svg viewBox="0 0 44 44" class="w-14 h-14 -rotate-90">
                                <circle cx="22" cy="22" r="18" fill="none" stroke="currentColor"
                                    stroke-width="4" class="text-white/20" />
                                <circle cx="22" cy="22" r="18" fill="none" stroke="currentColor"
                                    stroke-width="4" stroke-linecap="round" class="text-white transition-all"
                                    pathLength="100" :stroke-dasharray="'100'"
                                    :stroke-dashoffset="100 - Math.round((filledCount / totalSlots) * 100)" />
                            </svg>
                            <div
                                class="absolute inset-0 flex items-center justify-center text-xs font-extrabold tabular-nums">
                                <span x-text="filledCount"></span><span class="text-white/60">/<span
                                        x-text="totalSlots"></span></span>
                            </div>
                        </div>
                        <div
                            class="{{ $isAr ? 'text-xs font-semibold' : 'text-[10px] uppercase tracking-wider' }} text-white/80 leading-tight">
                            {{ __('Progress') }}
                        </div>
                    </div>
                </div>
                @include('voting::club._partials.voter-card', [
                    'voter' => $voter,
                    'club' => $club,
                    'variant' => 'glass',
                ])
            </div>
        </section>

        @if ($errors->any())
            <div class="mt-4 alert alert-error">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('voting.club.submit', $row->voting_link_token) }}" class="mt-6 space-y-6"
            @submit="onSubmit">
            @csrf

            {{--
          Every section below is a BIG TAP-TARGET card. Clicking opens
          the shared Alpine popup (defined once at the bottom of this
          template) which walks the voter through club → player in
          two clear steps. Same UX for Best Saudi / Best Foreign / TOS.
        --}}

            {{-- ── 1. Best Saudi ─────────────────────────────────── --}}
            @if ($showSaudi)
                <section class="bg-white rounded-3xl border-2 border-ink-200 shadow-sm overflow-hidden">
                    <button type="button" @click="openSlot('best_saudi', 0)"
                        class="w-full text-start flex items-center gap-4 p-5 md:p-6 transition"
                        :class="picks.best_saudi[0] ? 'bg-brand-50/40' : 'hover:bg-ink-50'">
                        <div
                            class="w-14 h-14 rounded-2xl bg-brand-600 text-white flex items-center justify-center text-2xl shadow-sm flex-shrink-0">
                            🏆</div>
                        <div class="flex-1 min-w-0">
                            <div
                                class="{{ $isAr ? 'text-[11px] font-bold' : 'text-[10px] uppercase tracking-widest font-semibold' }} text-ink-500">
                                {{ __('Question 1 of :n', ['n' => ($showSaudi ? 1 : 0) + ($showForeign ? 1 : 0) + ($showTos ? 1 : 0)]) }}
                            </div>
                            <div class="text-lg md:text-xl font-extrabold text-ink-900">{{ __('Best Saudi Player') }}</div>
                            {{-- Empty state --}}
                            <template x-if="!picks.best_saudi[0]">
                                <div class="text-sm text-ink-500 mt-1">
                                    {{ __('Tap to pick a nominee — choose a club, then a player.') }}</div>
                            </template>
                            {{-- Selected state --}}
                            <template x-if="picks.best_saudi[0]">
                                <div class="flex items-center gap-3 mt-2">
                                    <template x-if="picks.best_saudi[0]?.photo">
                                        <img :src="picks.best_saudi[0].photo"
                                            class="w-10 h-10 rounded-xl object-cover border-2 border-brand-500">
                                    </template>
                                    <template x-if="!picks.best_saudi[0]?.photo">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center font-bold">
                                            👤</div>
                                    </template>
                                    <div class="min-w-0">
                                        <div class="font-bold text-ink-900 truncate" x-text="picks.best_saudi[0]?.name">
                                        </div>
                                        <div class="text-xs text-ink-500" x-text="picks.best_saudi[0]?.club_name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <span class="text-xs font-bold rounded-full px-3 py-1 flex-shrink-0"
                            :class="picks.best_saudi[0] ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-500'">
                            <span x-show="picks.best_saudi[0]">✓ {{ __('Done') }}</span>
                            <span x-show="!picks.best_saudi[0]">{{ __('Tap to pick') }}</span>
                        </span>
                    </button>
                    <input type="hidden" name="best_saudi_player_id" :value="picks.best_saudi[0]?.player_id ?? ''">
                </section>
            @endif

            {{-- ── 2. Best Foreign ──────────────────────────────── --}}
            @if ($showForeign)
                <section class="bg-white rounded-3xl border-2 border-ink-200 shadow-sm overflow-hidden">
                    <button type="button" @click="openSlot('best_foreign', 0)"
                        class="w-full text-start flex items-center gap-4 p-5 md:p-6 transition"
                        :class="picks.best_foreign[0] ? 'bg-amber-50/40' : 'hover:bg-ink-50'">
                        <div
                            class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-sm flex-shrink-0">
                            🌍</div>
                        <div class="flex-1 min-w-0">
                            <div
                                class="{{ $isAr ? 'text-[11px] font-bold' : 'text-[10px] uppercase tracking-widest font-semibold' }} text-ink-500">
                                {{ __('Question :i of :n', ['i' => $showSaudi ? 2 : 1, 'n' => ($showSaudi ? 1 : 0) + ($showForeign ? 1 : 0) + ($showTos ? 1 : 0)]) }}
                            </div>
                            <div class="text-lg md:text-xl font-extrabold text-ink-900">{{ __('Best Foreign Player') }}
                            </div>
                            <template x-if="!picks.best_foreign[0]">
                                <div class="text-sm text-ink-500 mt-1">
                                    {{ __('Tap to pick a nominee — choose a club, then a player.') }}</div>
                            </template>
                            <template x-if="picks.best_foreign[0]">
                                <div class="flex items-center gap-3 mt-2">
                                    <template x-if="picks.best_foreign[0]?.photo">
                                        <img :src="picks.best_foreign[0].photo"
                                            class="w-10 h-10 rounded-xl object-cover border-2 border-amber-500">
                                    </template>
                                    <template x-if="!picks.best_foreign[0]?.photo">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                            👤</div>
                                    </template>
                                    <div class="min-w-0">
                                        <div class="font-bold text-ink-900 truncate" x-text="picks.best_foreign[0]?.name">
                                        </div>
                                        <div class="text-xs text-ink-500" x-text="picks.best_foreign[0]?.club_name"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <span class="text-xs font-bold rounded-full px-3 py-1 flex-shrink-0"
                            :class="picks.best_foreign[0] ? 'bg-amber-100 text-amber-700' : 'bg-ink-100 text-ink-500'">
                            <span x-show="picks.best_foreign[0]">✓ {{ __('Done') }}</span>
                            <span x-show="!picks.best_foreign[0]">{{ __('Tap to pick') }}</span>
                        </span>
                    </button>
                    <input type="hidden" name="best_foreign_player_id" :value="picks.best_foreign[0]?.player_id ?? ''">
                </section>
            @endif

            {{-- ── 3. Team of the Season pitch ──────────────────── --}}
            @if ($showTos)
                <section class="bg-white rounded-3xl border-2 border-ink-200 shadow-sm overflow-hidden">
                    <header
                        class="p-5 md:p-6 flex items-center justify-between gap-3 border-b border-ink-100 bg-gradient-to-r from-emerald-50 to-transparent">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-11 h-11 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xl shadow-sm">
                                ⚽</div>
                            <div>
                                <div
                                    class="{{ $isAr ? 'text-[11px] font-bold' : 'text-[10px] uppercase tracking-widest font-semibold' }} text-ink-500">
                                    {{ __('Question :i of :n', ['i' => ($showSaudi ? 1 : 0) + ($showForeign ? 1 : 0) + 1, 'n' => ($showSaudi ? 1 : 0) + ($showForeign ? 1 : 0) + ($showTos ? 1 : 0)]) }}
                                </div>
                                <h2 class="text-lg font-extrabold text-ink-900 leading-tight">
                                    {{ __('Team of the Season') }}</h2>
                                <p class="text-xs text-ink-500 mt-0.5">
                                    {{ __('Tap a slot on the pitch to pick a player — 4-3-3 formation.') }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2.5 py-1 tabular-nums"
                            :class="total === 11 ? 'bg-emerald-100 text-emerald-700' : 'bg-ink-100 text-ink-500'">
                            <span x-text="total"></span>/11
                        </span>
                    </header>

                    <div class="p-4 md:p-5">
                        {{--
                  Pitch redesign: slot tiles no longer stretch to fill
                  the pitch width — that made each tile as tall as a
                  banner. Now the grid is centered with a fixed per-tile
                  width (~72-80px), so all 11 tiles feel like actual
                  player badges, and the pitch is just the backdrop.
                --}}
                        <div
                            class="rounded-3xl bg-gradient-to-b from-emerald-700 to-emerald-900 px-3 py-5 md:py-6 relative overflow-hidden">
                            <div class="absolute inset-0 opacity-15"
                                style="background: repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);">
                            </div>
                            <div class="absolute inset-0 pointer-events-none">
                                <div class="absolute inset-x-0 top-1/2 border-t-2 border-white/20"></div>
                                <div
                                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border-2 border-white/20">
                                </div>
                            </div>

                            @foreach (['attack', 'midfield', 'defense', 'goalkeeper'] as $slot)
                                {{-- visual: attackers at the top of the pitch, GK at the back --}}
                                @php
                                    $count = App\Modules\Voting\Support\Formation::slots()[$slot];
                                    $meta = [
                                        'attack' => [
                                            'icon' => '⚡',
                                            'label' => __('Attack'),
                                            'color' => 'from-rose-500 to-rose-600',
                                        ],
                                        'midfield' => [
                                            'icon' => '⚙️',
                                            'label' => __('Midfield'),
                                            'color' => 'from-emerald-500 to-emerald-600',
                                        ],
                                        'defense' => [
                                            'icon' => '🛡',
                                            'label' => __('Defense'),
                                            'color' => 'from-blue-500 to-blue-600',
                                        ],
                                        'goalkeeper' => [
                                            'icon' => '🧤',
                                            'label' => __('Goalkeeper'),
                                            'color' => 'from-amber-500 to-amber-600',
                                        ],
                                    ][$slot];
                                @endphp
                                <div class="relative mb-4 last:mb-0">
                                    <div
                                        class="text-[10px] font-bold uppercase tracking-widest text-white/80 mb-2 flex items-center gap-1 justify-center">
                                        <span>{{ $meta['icon'] }}</span>
                                        <span>{{ $meta['label'] }}</span>
                                        <span class="text-white/50">— {{ $count }}</span>
                                    </div>
                                    {{-- Centered row; each tile is compact
                                 (72px wide, 84px tall) regardless of how
                                 wide the pitch gets. Wraps on narrow
                                 screens so 4 attackers never squish. --}}
                                    <div class="flex items-center justify-center gap-2 sm:gap-4 md:gap-5 flex-wrap">
                                        @for ($i = 0; $i < $count; $i++)
                                            {{-- Compact tile on narrow phones so a 4-wide
                                         attack row doesn't wrap to 3+1 orphan. --}}
                                            <button type="button"
                                                @click="openSlot('tos_{{ $slot }}', {{ $i }})"
                                                class="relative rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur border-2 border-dashed border-white/30 p-2 sm:p-2.5 text-center transition w-[72px] h-[96px] sm:w-[92px] sm:h-[108px] flex flex-col items-center justify-center text-white overflow-hidden group">
                                                <template x-if="picks['tos_{{ $slot }}'][{{ $i }}]">
                                                    <div
                                                        class="relative w-full h-full flex flex-col items-center justify-center">
                                                        <div
                                                            class="absolute inset-0 bg-gradient-to-br {{ $meta['color'] }} opacity-90 -m-2.5 rounded-2xl">
                                                        </div>
                                                        <div class="relative flex flex-col items-center">
                                                            <template
                                                                x-if="picks['tos_{{ $slot }}'][{{ $i }}]?.photo">
                                                                <img :src="picks['tos_{{ $slot }}'][{{ $i }}]
                                                                    ?.photo"
                                                                    class="w-11 h-11 rounded-full object-cover border-2 border-white shadow">
                                                            </template>
                                                            <template
                                                                x-if="!picks['tos_{{ $slot }}'][{{ $i }}]?.photo">
                                                                <div
                                                                    class="w-11 h-11 rounded-full bg-white/25 flex items-center justify-center text-base">
                                                                    👤</div>
                                                            </template>
                                                            <div class="font-bold text-[11px] truncate max-w-[80px] mt-1.5"
                                                                x-text="picks['tos_{{ $slot }}'][{{ $i }}]?.name">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="!picks['tos_{{ $slot }}'][{{ $i }}]">
                                                    <div
                                                        class="text-white/70 group-hover:text-white group-hover:scale-110 transition">
                                                        <div class="text-2xl leading-none">＋</div>
                                                        <div class="text-[10px] uppercase tracking-wider mt-1">
                                                            {{ __('Add') }}</div>
                                                    </div>
                                                </template>
                                            </button>
                                            <input type="hidden" name="lineup[{{ $slot }}][]"
                                                :value="picks['tos_{{ $slot }}'][{{ $i }}]?.player_id ?? ''">
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- ── Shared slot-picker popup (used by every award) ── --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open = false"></div>
                    <div
                        class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
                        <header class="p-5 md:p-6 text-white" :class="headerGradient">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div
                                        class="{{ $isAr ? 'text-xs font-semibold' : 'text-xs uppercase tracking-wider' }} text-white/70">
                                        {{ __('Pick for') }}</div>
                                    <h3 class="text-xl md:text-2xl font-extrabold truncate mt-0.5"
                                        x-text="currentSlotLabel"></h3>
                                    {{-- Live step crumb: Clubs · Players --}}
                                    <div class="flex items-center gap-2 mt-2 text-[11px] text-white/80">
                                        <span :class="!selectedClub ? 'font-bold text-white' : ''">①
                                            {{ __('Club') }}</span>
                                        <span class="opacity-50">→</span>
                                        <span :class="selectedClub ? 'font-bold text-white' : 'opacity-60'">②
                                            {{ __('Player') }}</span>
                                    </div>
                                </div>
                                <button type="button" @click="open = false" aria-label="{{ __('Close') }}"
                                    class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-xl leading-none flex items-center justify-center flex-shrink-0">&times;</button>
                            </div>
                            <div class="mt-4" x-show="!selectedClub">
                                <div class="relative">
                                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-white/70">🔍</span>
                                    <input type="text" x-model="clubQuery" placeholder="{{ __('Search club...') }}"
                                        class="w-full rounded-xl bg-white/10 border border-white/30 text-white placeholder:text-white/60 ps-10 pe-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/40">
                                </div>
                            </div>
                            <div class="mt-3" x-show="selectedClub">
                                <button type="button" @click="selectedClub = null; clubQuery = ''"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-lg bg-white/15 hover:bg-white/25 px-3 py-1.5">
                                    <span>{{ $isAr ? '→' : '←' }}</span>
                                    <span>{{ __('Change club') }}</span>
                                </button>
                            </div>
                        </header>

                        <div class="overflow-y-auto flex-1 p-4 md:p-5 bg-ink-50/40">
                            {{-- Step 1 — clubs list. Two-column tiles on md+,
                             each with a badge showing the nominees count. --}}
                            <div x-show="!selectedClub" class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                <template x-for="club in filteredClubs" :key="club.club_id">
                                    <button type="button" @click="selectedClub = club"
                                        class="flex items-center gap-3 rounded-2xl border-2 border-ink-200 bg-white hover:border-brand-400 hover:bg-brand-50 p-3.5 text-start transition group">
                                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-base font-extrabold flex-shrink-0 group-hover:bg-brand-100 transition"
                                            x-text="(club.club_name || '?').trim().charAt(0)"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-ink-900 truncate" x-text="club.club_name"></div>
                                            <div class="text-[11px] text-ink-500 mt-0.5">
                                                <span x-text="club.players.length"></span> {{ __('players') }}
                                            </div>
                                        </div>
                                        <span
                                            class="text-ink-400 group-hover:text-brand-600 transition">{{ $isAr ? '←' : '→' }}</span>
                                    </button>
                                </template>
                                <template x-if="filteredClubs.length === 0">
                                    <div class="col-span-full text-center text-ink-500 py-12">
                                        <div class="text-4xl mb-2 opacity-40">🔍</div>
                                        <div class="font-semibold">{{ __('No clubs match.') }}</div>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 2 — players from the chosen club.
                             Balanced row: photo · name+meta · action chip.
                             The meta row always shows jersey + position, so
                             empty cards don't feel sparse like before. --}}
                            <div x-show="selectedClub" class="space-y-2">
                                <template x-for="p in selectedClub?.players || []" :key="p.id">
                                    <button type="button" @click="choose(p)"
                                        class="w-full flex items-center gap-3 rounded-2xl border-2 border-ink-200 bg-white hover:border-brand-400 hover:bg-brand-50 p-3 text-start transition"
                                        :class="isAlreadyPicked(p.id) ? 'opacity-40 cursor-not-allowed' : ''"
                                        :disabled="isAlreadyPicked(p.id)">
                                        {{-- Avatar: photo or big initial badge --}}
                                        <template x-if="p.photo">
                                            <img :src="p.photo" :alt="p.name"
                                                class="w-12 h-12 rounded-xl object-cover border-2 border-ink-100 flex-shrink-0">
                                        </template>
                                        <template x-if="!p.photo">
                                            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-lg font-extrabold flex-shrink-0"
                                                x-text="(p.name || '?').trim().charAt(0)"></div>
                                        </template>

                                        {{-- Name + meta row --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-ink-900 truncate" x-text="p.name"></div>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <template x-if="p.position">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-brand-50 text-brand-700 px-2 py-0.5 text-[11px] font-semibold"
                                                        x-text="p.position"></span>
                                                </template>
                                                <template x-if="p.jersey">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-ink-100 text-ink-700 px-2 py-0.5 text-[11px] font-mono font-bold">
                                                        #<span x-text="p.jersey"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Right-side action chip --}}
                                        <span
                                            class="flex-shrink-0 text-[11px] font-bold rounded-full px-3 py-1.5 transition"
                                            :class="isAlreadyPicked(p.id) ?
                                                'bg-brand-100 text-brand-700' :
                                                'bg-ink-100 text-ink-600 group-hover:bg-brand-600 group-hover:text-white'">
                                            <span x-show="isAlreadyPicked(p.id)">✓ {{ __('picked') }}</span>
                                            <span x-show="!isAlreadyPicked(p.id)">{{ __('Choose') }}</span>
                                        </span>
                                    </button>
                                </template>
                                <template x-if="(selectedClub?.players || []).length === 0">
                                    <div class="text-center text-ink-500 py-12">
                                        <div class="text-4xl mb-2 opacity-40">👥</div>
                                        <div class="font-semibold">{{ __('No eligible players in this club.') }}</div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ── Sticky submit bar ───────────────────────────────
             iOS Safari: use env(safe-area-inset-bottom) so the home
             indicator doesn't eat the button. Tall the transparent
             gradient so the ballot above fades gracefully behind it. --}}
            <div class="sticky bottom-0 inset-x-0 z-20 pt-4 -mx-4 px-4 md:mx-0 md:px-0 bg-gradient-to-t from-white via-white/95 to-transparent"
                style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));">
                <div
                    class="rounded-2xl border-2 border-ink-200 bg-white shadow-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="text-sm">
                        <div class="font-bold text-ink-900"
                            x-text="isReady ? '{{ __('All set — ready to submit') }}' : '{{ __('Complete every question to submit') }}'">
                        </div>
                        <div class="text-xs text-ink-500 mt-1 flex items-center gap-3 flex-wrap">
                            @if ($showSaudi)
                                <span class="flex items-center gap-1">
                                    <span :class="picks.best_saudi[0] ? 'text-brand-700' : 'text-ink-400'"
                                        x-text="picks.best_saudi[0] ? '✓' : '○'"></span>
                                    <span>{{ __('Saudi') }}</span>
                                </span>
                            @endif
                            @if ($showForeign)
                                <span class="flex items-center gap-1">
                                    <span :class="picks.best_foreign[0] ? 'text-amber-600' : 'text-ink-400'"
                                        x-text="picks.best_foreign[0] ? '✓' : '○'"></span>
                                    <span>{{ __('Foreign') }}</span>
                                </span>
                            @endif
                            @if ($showTos)
                                <span class="flex items-center gap-1">
                                    <span :class="total === 11 ? 'text-emerald-700' : 'text-ink-400'"
                                        x-text="total === 11 ? '✓' : '○'"></span>
                                    <span>TOS (<span x-text="total"></span>/11)</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-6 py-3.5 font-bold shadow-lg transition"
                        :class="isReady
                            ?
                            'bg-brand-600 hover:bg-brand-700 text-white' :
                            'bg-ink-200 text-ink-500 cursor-not-allowed'"
                        :disabled="!isReady">
                        <span aria-hidden="true">✓</span>
                        <span>{{ __('Complete voting') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function clubBallot(data) {
                const SHOW_SAUDI = @json($showSaudi);
                const SHOW_FOREIGN = @json($showForeign);
                const SHOW_TOS = @json($showTos);
                const TOTAL_SLOTS = (SHOW_SAUDI ? 1 : 0) + (SHOW_FOREIGN ? 1 : 0) + (SHOW_TOS ? 11 : 0);

                // One picks bucket per slot-type. Each slot is an array because
                // TOS has multiple slots per position; the individual awards use
                // index 0 only.
                const picks = {
                    best_saudi: [],
                    best_foreign: [],
                    tos_goalkeeper: [],
                    tos_defense: [],
                    tos_midfield: [],
                    tos_attack: [],
                };

                return {
                    data,
                    picks,
                    open: false,
                    currentSlotType: null, // e.g. 'best_saudi', 'tos_attack'
                    currentIndex: 0,
                    selectedClub: null,
                    clubQuery: '',
                    slotLabels: {
                        best_saudi: '{{ __('Best Saudi Player') }}',
                        best_foreign: '{{ __('Best Foreign Player') }}',
                        tos_goalkeeper: '{{ __('Goalkeeper') }}',
                        tos_defense: '{{ __('Defense') }}',
                        tos_midfield: '{{ __('Midfield') }}',
                        tos_attack: '{{ __('Attack') }}',
                    },
                    gradients: {
                        best_saudi: 'bg-gradient-to-r from-brand-600 to-brand-800',
                        best_foreign: 'bg-gradient-to-r from-amber-500 to-amber-700',
                        tos_goalkeeper: 'bg-gradient-to-r from-amber-500 to-amber-700',
                        tos_defense: 'bg-gradient-to-r from-blue-500 to-blue-700',
                        tos_midfield: 'bg-gradient-to-r from-emerald-500 to-emerald-700',
                        tos_attack: 'bg-gradient-to-r from-rose-500 to-rose-700',
                    },
                    get currentSlotLabel() {
                        return this.slotLabels[this.currentSlotType] || '';
                    },
                    get headerGradient() {
                        return this.gradients[this.currentSlotType] || 'bg-gradient-to-r from-emerald-600 to-emerald-800';
                    },
                    get filteredClubs() {
                        const q = (this.clubQuery || '').toLowerCase().trim();
                        const list = this.data[this.currentSlotType] || [];
                        return q ? list.filter(c => (c.club_name || '').toLowerCase().includes(q)) : list;
                    },
                    get total() {
                        // TOS count only — 11 when the lineup is complete.
                        return ['tos_goalkeeper', 'tos_defense', 'tos_midfield', 'tos_attack']
                            .reduce((s, k) => s + (this.picks[k] || []).filter(Boolean).length, 0);
                    },
                    get filledCount() {
                        return this.total +
                            (this.picks.best_saudi[0] ? 1 : 0) +
                            (this.picks.best_foreign[0] ? 1 : 0);
                    },
                    get totalSlots() {
                        return TOTAL_SLOTS;
                    },
                    get isReady() {
                        if (SHOW_SAUDI && !this.picks.best_saudi[0]) return false;
                        if (SHOW_FOREIGN && !this.picks.best_foreign[0]) return false;
                        if (SHOW_TOS && this.total !== 11) return false;
                        return true;
                    },
                    openSlot(slotType, idx) {
                        this.currentSlotType = slotType;
                        this.currentIndex = idx;
                        this.selectedClub = null;
                        this.clubQuery = '';
                        this.open = true;
                    },
                    choose(p) {
                        if (this.isAlreadyPicked(p.id)) return;
                        if (!this.picks[this.currentSlotType]) this.picks[this.currentSlotType] = [];
                        this.picks[this.currentSlotType][this.currentIndex] = {
                            player_id: p.id,
                            name: p.name,
                            club_name: this.selectedClub.club_name,
                            photo: p.photo,
                        };
                        this.open = false;
                    },
                    isAlreadyPicked(id) {
                        // For TOS: no player can appear in two pitch slots.
                        // For individual awards: a player is never disabled by
                        // itself (the voter is allowed to pick anyone per-award).
                        if (this.currentSlotType && this.currentSlotType.startsWith('tos_')) {
                            return ['tos_goalkeeper', 'tos_defense', 'tos_midfield', 'tos_attack']
                                .some(k => (this.picks[k] || []).some(pick => pick && pick.player_id === id));
                        }
                        return false;
                    },
                    onSubmit(e) {
                        if (!this.isReady) {
                            e.preventDefault();
                            alert('{{ __('Please complete every question before submitting.') }}');
                        }
                    },
                };
            }
        </script>
    @endpush
@endsection
