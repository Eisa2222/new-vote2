@extends('voting::club._layout')
@section('title', __('Thank you'))

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- ── Headline ─────────────────────────────────────────── --}}
        <div class="card text-center space-y-3">
            <div class="text-6xl">🎉</div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900">{{ __('Your vote has been recorded') }}</h1>
            <p class="text-ink-500">
                {{ __('Thank you for participating in :title.', ['title' => $campaign->localized('title')]) }}
            </p>
        </div>

        {{-- Results announced? Show a big, friendly link so the voter
         can tap straight to the public winners page. Previously
         the success screen had no way back to the announcement. --}}
        @if (optional($campaign->results_visibility)->value === 'announced')
            <a href="{{ route('public.results', $campaign->public_token) }}"
                class="block rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-800 text-white p-5 md:p-6 shadow-lg hover:shadow-xl transition group">
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-3xl flex-shrink-0">
                        🏆
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-white/70">
                            {{ __('Results announced') }}</div>
                        <div class="text-lg md:text-xl font-extrabold mt-0.5">{{ __('See the winners') }}</div>
                    </div>
                    <span
                        class="opacity-70 group-hover:opacity-100 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition text-xl">
                        →
                    </span>
                </div>
            </a>
        @endif

        @isset($picks)
            {{-- ── Picks recap — shown once, the first time the user
             lands on success after submit. `with(...)` puts it in the
             flash bag so a refresh won't re-show it. ────────────── --}}

            {{-- Individual awards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $awardCards = [
                        ['title' => __('Best Saudi Player'), 'icon' => '🏆', 'player' => $picks['saudi']],
                        ['title' => __('Best Foreign Player'), 'icon' => '🌍', 'player' => $picks['foreign']],
                    ];
                @endphp
                @foreach ($awardCards as $c)
                    @if ($c['player'])
                        <div class="card !p-5">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="text-3xl">{{ $c['icon'] }}</div>
                                <div>
                                    <div class="text-[11px] uppercase tracking-wider text-ink-500">{{ $c['title'] }}</div>
                                    <div class="text-lg font-bold text-ink-900 leading-tight">
                                        {{ $c['player']->localized('name') }}</div>
                                    <div class="text-xs text-ink-500">
                                        {{ $c['player']->club?->localized('name') }}
                                        @if ($c['player']->jersey_number)
                                            · #{{ $c['player']->jersey_number }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Team of the Season pitch recap.
                 Mirrors the ballot's pitch exactly: emerald gradient
                 background, horizontal field lines, half-line + center
                 circle, compact 72x96 / 92x108 tiles with per-position
                 gradients, photo bubble, club name under player name.
                 Kept read-only (no empty/+ state) since this is the
                 "confirmation recap". --}}
            @if (!empty($picks['lineup']))
                @php $isAr = app()->getLocale() === 'ar'; @endphp
                <div class="card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">⚽</span>
                        <h2 class="text-lg font-bold">{{ __('Your Team of the Season') }}</h2>
                    </div>

                    <div class="rounded-3xl bg-gradient-to-b from-emerald-700 to-emerald-900 px-3 py-5 md:py-6 relative overflow-hidden">
                        {{-- Field stripes (repeating horizontal lines) --}}
                        <div class="absolute inset-0 opacity-15"
                             style="background: repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);"></div>
                        {{-- Half-line + center circle --}}
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-x-0 top-1/2 border-t-2 border-white/20"></div>
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border-2 border-white/20"></div>
                        </div>

                        @php
                            $slotMeta = [
                                'attack'     => ['icon' => '⚡', 'label' => __('Attack'),     'color' => 'from-rose-500 to-rose-600'],
                                'midfield'   => ['icon' => '⚙️', 'label' => __('Midfield'),   'color' => 'from-emerald-500 to-emerald-600'],
                                'defense'    => ['icon' => '🛡', 'label' => __('Defense'),    'color' => 'from-blue-500 to-blue-600'],
                                'goalkeeper' => ['icon' => '🧤', 'label' => __('Goalkeeper'), 'color' => 'from-amber-500 to-amber-600'],
                            ];
                        @endphp

                        @foreach (['attack', 'midfield', 'defense', 'goalkeeper'] as $slot)
                            @php $players = $picks['lineup'][$slot] ?? collect(); @endphp
                            @if($players->isEmpty()) @continue @endif
                            <div class="relative mb-4 last:mb-0">
                                <div class="mb-2 flex items-center justify-center gap-1
                                            {{ $isAr ? 'text-[11px] font-bold' : 'text-[10px] uppercase tracking-widest font-bold' }}
                                            text-white/80">
                                    <span>{{ $slotMeta[$slot]['icon'] }}</span>
                                    <span>{{ $slotMeta[$slot]['label'] }}</span>
                                    <span class="text-white/50">— {{ count($players) }}</span>
                                </div>

                                <div class="flex items-center justify-center gap-2 sm:gap-4 md:gap-5 flex-wrap">
                                    @foreach ($players as $p)
                                        @php
                                            $photo = $p->photo_path
                                                ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                                                : null;
                                        @endphp
                                        <div class="relative rounded-2xl p-2 sm:p-2.5 text-center w-[72px] h-[96px] sm:w-[92px] sm:h-[108px] flex flex-col items-center justify-center text-white overflow-hidden shadow-md">
                                            {{-- Per-position gradient fill (matches ballot). --}}
                                            <div class="absolute inset-0 bg-gradient-to-br {{ $slotMeta[$slot]['color'] }} opacity-90"></div>
                                            <div class="relative flex flex-col items-center">
                                                @if($photo)
                                                    <img src="{{ $photo }}" alt="{{ $p->localized('name') }}"
                                                         class="w-11 h-11 rounded-full object-cover border-2 border-white shadow">
                                                @else
                                                    <div class="w-11 h-11 rounded-full bg-white/25 flex items-center justify-center text-base font-extrabold">
                                                        {{ mb_strtoupper(mb_substr($p->localized('name') ?? '?', 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="font-bold text-[11px] truncate max-w-[80px] mt-1.5">{{ $p->localized('name') }}</div>
                                                <div class="text-[9px] text-white/85 truncate max-w-[80px]">{{ $p->club?->localized('name') }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endisset

        {{-- ── CTA → optional profile capture ─────────────────────── --}}
        <div class="card text-start space-y-4">
            <div class="rounded-2xl bg-brand-50 border border-brand-200 p-5 text-sm text-brand-800">
                <div class="font-bold mb-1">📬 {{ __('Be the first to know when results are announced') }}</div>
                <div class="text-brand-700">
                    {{ __('Share your contact details below (optional) and we will let you know the moment the committee announces the winners.') }}
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('voting.club.profile', $row->voting_link_token) }}" class="btn-save">
                    <span aria-hidden="true">✉️</span>
                    <span>{{ __('Add my contact details') }}</span>
                </a>
                <a href="{{ route('public.campaigns') }}" class="btn-ghost">
                    <span>{{ __('Finish') }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection
