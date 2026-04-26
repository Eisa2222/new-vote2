@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
    $isAr   = $locale === 'ar';

    // Only winners survive to the public announcement view. Everything
    // else (runners-up, vote counts, percentages) is deliberately
    // hidden per the spec: "only announce the winners' names and show
    // the winning Team-of-the-Season lineup on the pitch."
    $winners = $result->items->where('is_winner', true)->values();

    // Split: individual awards (Best Saudi / Best Foreign) vs TOS
    // lineup slots. We key off the category's award_type enum.
    $individualWinners = $winners->filter(fn ($w) => optional($w->category?->award_type)->value !== 'team_of_the_season')->values();
    $tosWinners        = $winners->filter(fn ($w) => optional($w->category?->award_type)->value === 'team_of_the_season');

    // Group TOS winners by position slot so the pitch renderer can
    // drop each winner onto its proper line. Within each position
    // we sort by rank ascending — rank 1 (most votes) appears
    // first — so the announcement mirrors how the voters ranked
    // them (the tally already applied the deterministic tie-breaker).
    $tosByPosition = $tosWinners
        ->sortBy([['rank', 'asc']])
        ->groupBy(fn ($w) => $w->category?->position_slot ?? 'any')
        ->map(fn ($group) => $group->sortBy('rank')->values());

    $slotMeta = [
        'attack'     => ['icon' => '⚡', 'label' => __('Attack'),     'color' => 'from-rose-500 to-rose-600'],
        'midfield'   => ['icon' => '⚙️', 'label' => __('Midfield'),   'color' => 'from-emerald-500 to-emerald-600'],
        'defense'    => ['icon' => '🛡', 'label' => __('Defense'),    'color' => 'from-blue-500 to-blue-600'],
        'goalkeeper' => ['icon' => '🧤', 'label' => __('Goalkeeper'), 'color' => 'from-amber-500 to-amber-600'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🏆 {{ __('Voting campaign results announcement') }} — {{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
    <style>
        .trophy-bg {
            background:
                radial-gradient(ellipse at top, rgba(200,163,101,0.28) 0%, transparent 55%),
                radial-gradient(ellipse at bottom right, rgba(17,92,66,0.35) 0%, transparent 45%),
                linear-gradient(135deg, #0B3D2E 0%, #115C42 50%, #083024 100%);
        }
        @keyframes shine {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .winner-badge::after {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(110deg, transparent 40%, rgba(255,255,255,0.4) 50%, transparent 60%);
            animation: shine 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-ink-50 min-h-screen">

<div class="max-w-5xl mx-auto px-3 md:px-6 py-6 md:py-10 space-y-6">

    {{-- HERO — campaign title + individual award winners ─────────── --}}
    <section class="trophy-bg text-white rounded-[2rem] p-6 md:p-12 shadow-brand relative overflow-hidden text-center">
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background-image: radial-gradient(circle at 20% 20%, #C8A365 2px, transparent 3px),
                                       radial-gradient(circle at 70% 60%, #DDB97A 1.5px, transparent 2.5px),
                                       radial-gradient(circle at 40% 80%, #C8A365 2px, transparent 3px);
                    background-size: 120px 120px, 90px 90px, 100px 100px;"></div>

        {{-- Locale switcher — top-end of the hero. Same chip style as
             every other public surface. --}}
        <div class="absolute top-4 end-4 z-20">
            <div class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 backdrop-blur overflow-hidden text-xs font-semibold">
                <a href="?locale=ar" class="px-3 py-1.5 transition {{ $locale === 'ar' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">AR</a>
                <a href="?locale=en" class="px-3 py-1.5 transition {{ $locale === 'en' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">EN</a>
            </div>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-500/20 border border-accent-400/40 backdrop-blur text-accent-400 text-xs font-bold
                        {{ $isAr ? '' : 'tracking-[0.25em] uppercase' }}">
                🏆 {{ __('Official announcement') }}
            </div>

            <h1 class="mt-5 text-3xl md:text-5xl font-black leading-tight">
                {{ $campaign->localized('title') }}
            </h1>
            <p class="text-brand-100 mt-3 text-sm md:text-base">
                {{ __('Results approved by the Voting Committee') }}
            </p>

            {{-- Individual award winner card(s) — name + club only;
                 vote counts and percentages removed by design. --}}
            @if($individualWinners->isNotEmpty())
                <div class="mt-8 grid @if($individualWinners->count() === 1) grid-cols-1 @else grid-cols-1 md:grid-cols-2 @endif gap-5 max-w-3xl mx-auto">
                    @foreach($individualWinners as $w)
                        @php
                            $p        = $w->candidate?->player;
                            $name     = $p?->localized('name') ?? $w->candidate?->club?->localized('name');
                            $club     = $p?->club?->localized('name');
                            $photo    = $p?->photo_path
                                ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                                : ($w->candidate?->club?->logo_path
                                    ? \Illuminate\Support\Facades\Storage::url($w->candidate->club->logo_path) : null);
                            $awardTitle = $w->category?->localized('title');
                        @endphp
                        <div class="relative rounded-3xl bg-white text-ink-900 p-6 md:p-8 shadow-2xl winner-badge overflow-hidden">
                            <div class="absolute -top-1 -right-1 w-20 h-20 bg-accent-500 rotate-45 translate-x-10 -translate-y-10"></div>
                            <div class="absolute top-2 right-3 text-white text-xl font-black -rotate-12 z-10">★</div>

                            <div class="mx-auto w-28 h-28 md:w-36 md:h-36 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 p-1 shadow-xl">
                                <div class="w-full h-full rounded-full overflow-hidden bg-white flex items-center justify-center">
                                    @if($photo)
                                        <img src="{{ $photo }}" alt="{{ $name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-4xl md:text-5xl font-black text-brand-700">{{ mb_strtoupper(mb_substr($name ?? '?', 0, 1)) }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($awardTitle)
                                <div class="mt-4 text-xs font-bold text-accent-600
                                            {{ $isAr ? '' : 'uppercase tracking-[0.2em]' }}">
                                    🏆 {{ $awardTitle }}
                                </div>
                            @else
                                <div class="mt-4 text-xs font-bold text-accent-600
                                            {{ $isAr ? '' : 'uppercase tracking-[0.25em]' }}">
                                    🏆 {{ __('Winner') }}
                                </div>
                            @endif
                            <div class="mt-1 text-xl md:text-2xl font-extrabold">{{ $name }}</div>
                            @if($club)
                                <div class="text-sm text-ink-500 mt-1">{{ $club }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Announcement date stays; vote totals removed. --}}
            @if($result->announced_at)
                <div class="mt-8 inline-flex items-center gap-2 text-xs text-brand-100">
                    <span>📅 {{ __('Announced') }}:</span>
                    <strong class="text-white tabular-nums">{{ $result->announced_at->translatedFormat('d M Y') }}</strong>
                </div>
            @endif
        </div>
    </section>

    {{-- ── TEAM OF THE SEASON PITCH — winners on the formation ── --}}
    @if($tosByPosition->isNotEmpty())
        <section class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 md:p-6">
            <header class="mb-4 flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-700 text-white flex items-center justify-center text-2xl shadow-sm">⚽</div>
                <div>
                    <h2 class="text-xl md:text-2xl font-extrabold text-ink-900">{{ __('Team of the Season') }}</h2>
                    <p class="text-xs text-ink-500 mt-0.5">{{ __('The winning lineup chosen by the voters.') }}</p>
                </div>
            </header>

            {{-- Pitch — identical palette + ornamentation to the
                 ballot pitch so the announcement reads as "the same
                 formation voters built", just filled with winners. --}}
            <div class="rounded-3xl bg-gradient-to-b from-emerald-700 to-emerald-900 px-3 py-5 md:py-6 relative overflow-hidden">
                <div class="absolute inset-0 opacity-15"
                     style="background: repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);"></div>
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute inset-x-0 top-1/2 border-t-2 border-white/20"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 rounded-full border-2 border-white/20"></div>
                </div>

                @foreach(['attack', 'midfield', 'defense', 'goalkeeper'] as $slot)
                    @php $slotWinners = $tosByPosition->get($slot, collect()); @endphp
                    @if($slotWinners->isEmpty()) @continue @endif
                    <div class="relative mb-4 last:mb-0">
                        <div class="mb-2 flex items-center justify-center gap-1
                                    {{ $isAr ? 'text-[11px] font-bold' : 'text-[10px] uppercase tracking-widest font-bold' }}
                                    text-white/85">
                            <span>{{ $slotMeta[$slot]['icon'] }}</span>
                            <span>{{ $slotMeta[$slot]['label'] }}</span>
                            <span class="text-white/55">— {{ $slotWinners->count() }}</span>
                        </div>
                        <div class="flex items-center justify-center gap-2 sm:gap-4 md:gap-5 flex-wrap">
                            @foreach($slotWinners as $w)
                                @php
                                    $p     = $w->candidate?->player;
                                    $name  = $p?->localized('name') ?? $w->candidate?->club?->localized('name');
                                    $club  = $p?->club?->localized('name');
                                    $photo = $p?->photo_path
                                        ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                                        : null;
                                @endphp
                                <div class="relative rounded-2xl p-2 sm:p-2.5 text-center w-[80px] h-[104px] sm:w-[100px] sm:h-[118px] flex flex-col items-center justify-center text-white overflow-hidden shadow-lg">
                                    <div class="absolute inset-0 bg-gradient-to-br {{ $slotMeta[$slot]['color'] }} opacity-95"></div>
                                    <div class="relative flex flex-col items-center">
                                        @if($photo)
                                            <img src="{{ $photo }}" alt="{{ $name }}"
                                                 class="w-12 h-12 rounded-full object-cover border-2 border-white shadow ring-2 ring-accent-400/50">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-white/25 flex items-center justify-center text-base font-extrabold">
                                                {{ mb_strtoupper(mb_substr($name ?? '?', 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="font-bold text-[11px] truncate max-w-[90px] mt-1.5">{{ $name }}</div>
                                        @if($club)
                                            <div class="text-[9px] text-white/85 truncate max-w-[90px]">{{ $club }}</div>
                                        @endif
                                    </div>
                                    {{-- Tiny trophy marker so visitors read the tile
                                         as "this is a winner", not just a filled slot. --}}
                                    <div class="absolute top-1 end-1 text-accent-300 text-xs">★</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <footer class="text-center text-xs text-ink-500 pb-6">
        © {{ date('Y') }} {{ \App\Modules\Shared\Support\Branding::name() }} — {{ __('Official winner announcement') }}
    </footer>
</div>

</body>
</html>
