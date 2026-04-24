@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
    $isAr   = $locale === 'ar';

    // Countdown target for the hero ring — same pre/live/ended phases
    // as the admin stats card so the visitor sees the actual time left.
    $now   = now();
    $phase = 'idle';
    $target = null;
    if ($campaign->start_at > $now) {
        $phase  = 'pre';
        $target = $campaign->start_at->toIso8601String();
    } elseif ($campaign->end_at >= $now) {
        $phase  = 'live';
        $target = $campaign->end_at->toIso8601String();
    } else {
        $phase = 'ended';
    }

    $resultsAnnounced = optional($campaign->results_visibility)->value === 'announced';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>📊 {{ __('Campaign statistics') }} — {{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        .hero-bg {
            background:
                radial-gradient(ellipse at top left, rgba(200,163,101,0.18) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(63,146,97,0.35) 0%, transparent 55%),
                linear-gradient(135deg, #0B3D2E 0%, #115C42 55%, #083024 100%);
        }
        .dots::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1.5px);
            background-size: 28px 28px;
            opacity: 0.15;
            pointer-events: none;
        }
        @keyframes countup-pulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.04); } }
        .pulse-num { animation: countup-pulse 2.5s ease-in-out infinite; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-ink-50 min-h-screen">

<div x-data="statsPage({
        phase: @js($phase),
        target: @js($target),
        labels: {
            days: @js(__('days')), hours: @js(__('hours')),
            mins: @js(__('mins')), secs: @js(__('secs')),
            ended: @js(__('Already ended')),
            startsIn: @js(__('Starts in')),
            endsIn: @js(__('Ends in')),
        }
     })"
     x-init="tick(); setInterval(tick, 1000);">

<div class="max-w-6xl mx-auto px-3 md:px-6 py-6 md:py-10 space-y-6">

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <section class="hero-bg text-white rounded-[2rem] relative overflow-hidden shadow-brand dots">
        <div class="relative p-6 md:p-10">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                <div class="flex-1 min-w-0">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur text-white/85 text-[11px] font-semibold
                                {{ $isAr ? '' : 'uppercase tracking-[0.2em]' }}">
                        <span>📊</span>
                        <span>{{ __('Campaign statistics') }}</span>
                    </div>

                    <h1 class="mt-4 text-3xl md:text-5xl font-black leading-tight break-words">
                        {{ $campaign->localized('title') }}
                    </h1>
                    @if($campaign->localized('description'))
                        <p class="text-brand-100 mt-3 max-w-xl leading-7 text-sm md:text-base">
                            {{ $campaign->localized('description') }}
                        </p>
                    @endif

                    {{-- Phase pill (live / pre / ended) --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @if($phase === 'live')
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/20 border border-emerald-400/40 px-3 py-1.5 text-emerald-200 text-xs font-bold">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                                </span>
                                {{ __('Live now') }}
                            </span>
                        @elseif($phase === 'pre')
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-500/20 border border-amber-400/40 px-3 py-1.5 text-amber-200 text-xs font-bold">
                                ⏳ {{ __('Starting soon') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-ink-500/30 border border-white/15 px-3 py-1.5 text-white/85 text-xs font-bold">
                                ⛔ {{ __('Already ended') }}
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-white/85 text-xs font-semibold tabular-nums">
                            🗓 {{ $campaign->start_at->format('Y-m-d') }}
                            <span class="opacity-60">→</span>
                            {{ $campaign->end_at->format('Y-m-d') }}
                        </span>
                    </div>
                </div>

                {{-- Big countdown block — the visual star of the hero.
                     Flips to "Ended" or "Starts in" based on phase. --}}
                <div class="bg-white/10 border border-white/20 backdrop-blur rounded-3xl p-5 md:p-6 w-full md:w-auto self-start">
                    <div class="text-[10px] font-bold text-white/70 mb-2
                                {{ $isAr ? '' : 'uppercase tracking-[0.2em]' }}"
                         x-text="phase === 'ended' ? labels.ended : (phase === 'pre' ? labels.startsIn : labels.endsIn)">
                    </div>
                    <div x-show="phase !== 'ended'" x-cloak class="flex items-baseline gap-3 tabular-nums text-white">
                        <template x-if="days > 0">
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl md:text-5xl font-black" x-text="days"></span>
                                <span class="text-xs text-white/70" x-text="labels.days"></span>
                            </div>
                        </template>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl md:text-5xl font-black" x-text="pad(hours)"></span>
                            <span class="text-xs text-white/70" x-text="labels.hours"></span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl md:text-5xl font-black" x-text="pad(minutes)"></span>
                            <span class="text-xs text-white/70" x-text="labels.mins"></span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl md:text-5xl font-black text-accent-400" x-text="pad(seconds)"></span>
                            <span class="text-xs text-white/70" x-text="labels.secs"></span>
                        </div>
                    </div>
                    <div x-show="phase === 'ended'" class="text-2xl font-black text-white/90">
                        {{ __('Already ended') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── KEY NUMBERS — 4-up grid ───────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total votes — with subtle pulse when live --}}
        <div class="relative rounded-2xl bg-white border border-ink-200 shadow-sm p-5 overflow-hidden">
            <div class="absolute -end-4 -top-4 text-7xl opacity-5">🗳</div>
            <div class="relative">
                <div class="text-[11px] font-semibold text-ink-500
                            {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                    {{ __('Total votes received') }}
                </div>
                <div class="text-4xl font-black tabular-nums text-ink-900 mt-1 {{ $phase === 'live' ? 'pulse-num' : '' }}">
                    {{ number_format($totalVotes) }}
                </div>
                @if($eligiblePlayers > 0)
                    <div class="mt-2 text-xs text-ink-500 tabular-nums">
                        {{ number_format($totalVotes) }} / {{ number_format($eligiblePlayers) }} {{ __('players') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Turnout — ring gauge --}}
        <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 text-white shadow-lg p-5 relative overflow-hidden">
            <div class="flex items-center gap-4">
                <div class="relative w-20 h-20 flex-shrink-0">
                    <svg viewBox="0 0 44 44" class="w-20 h-20 -rotate-90">
                        <circle cx="22" cy="22" r="18" fill="none" stroke="currentColor" stroke-width="4" class="text-white/20"/>
                        <circle cx="22" cy="22" r="18" fill="none" stroke="currentColor" stroke-width="4"
                                stroke-linecap="round" class="text-accent-400 transition-all"
                                pathLength="100"
                                stroke-dasharray="100"
                                stroke-dashoffset="{{ 100 - $turnoutPct }}"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center text-lg font-black tabular-nums">
                        {{ $turnoutPct }}%
                    </div>
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-semibold text-white/70
                                {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                        {{ __('Turnout') }}
                    </div>
                    <div class="text-sm text-white/90 mt-1 leading-5">
                        {{ __('Of the eligible roster') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Clubs — active of attached --}}
        <div class="rounded-2xl bg-white border border-ink-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold text-ink-500
                            {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                    {{ __('Clubs') }}
                </div>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg">🏟</div>
            </div>
            <div class="text-3xl font-black tabular-nums text-ink-900 mt-2">
                {{ $activeClubs }}<span class="text-ink-400 text-lg">/{{ $attachedClubs }}</span>
            </div>
            <div class="text-xs text-ink-500 mt-1.5">{{ __('active of attached') }}</div>
        </div>

        {{-- Eligible voters + quota --}}
        <div class="rounded-2xl bg-white border border-ink-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="text-[11px] font-semibold text-ink-500
                            {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                    {{ __('Eligible voters') }}
                </div>
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-lg">👥</div>
            </div>
            <div class="text-3xl font-black tabular-nums text-ink-900 mt-2">
                {{ number_format($eligiblePlayers) }}
            </div>
            <div class="text-xs text-ink-500 mt-1.5">
                {{ __('Max voters') }}:
                <strong class="text-ink-700 tabular-nums">
                    {{ $totalCap !== null ? number_format($totalCap) : '∞' }}
                </strong>
            </div>
        </div>
    </div>

    {{-- ── PER-CLUB LEADERBOARD ──────────────────────────────── --}}
    @if($perClub->isNotEmpty())
        @php $maxClubVotes = max(1, (int) $perClub->max('votes')); @endphp
        <section class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 md:p-6">
            <header class="mb-4 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-xl font-extrabold text-ink-900">{{ __('Participation by club') }}</h2>
                    <p class="text-xs text-ink-500 mt-0.5">{{ __('Sorted by votes received — updates live during voting.') }}</p>
                </div>
                <div class="text-[11px] font-semibold text-ink-500
                            {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                    {{ $perClub->count() }} {{ __('clubs') }}
                </div>
            </header>

            <div class="space-y-2.5">
                @foreach($perClub as $i => $c)
                    @php
                        $pct = (int) round(($c['votes'] / $maxClubVotes) * 100);
                        $rank = $i + 1;
                    @endphp
                    <div class="rounded-2xl border {{ $rank === 1 && $c['votes'] > 0 ? 'border-accent-300 bg-accent-50/30' : 'border-ink-200 bg-white' }} p-3 md:p-4">
                        <div class="flex items-center gap-3">
                            {{-- Rank medal / number --}}
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm
                                        {{ $rank === 1 && $c['votes'] > 0 ? 'bg-accent-500 text-white' : ($rank <= 3 ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-500') }}">
                                @if($rank === 1 && $c['votes'] > 0)🥇
                                @elseif($rank === 2 && $c['votes'] > 0)🥈
                                @elseif($rank === 3 && $c['votes'] > 0)🥉
                                @else #{{ $rank }}
                                @endif
                            </div>

                            @if($c['logo'])
                                <img src="{{ $c['logo'] }}" alt="" class="w-10 h-10 rounded-full object-cover border border-ink-200">
                            @else
                                <div class="w-10 h-10 rounded-full bg-brand-100 text-brand-700 font-extrabold flex items-center justify-center">
                                    {{ mb_strtoupper(mb_substr($c['club_name'], 0, 1)) }}
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-ink-900 truncate">{{ $c['club_name'] }}</div>
                                <div class="text-[11px] text-ink-500 tabular-nums">
                                    {{ number_format($c['votes']) }} {{ __('votes') }}
                                    @if($c['max'])
                                        <span class="opacity-60 mx-1">·</span>
                                        {{ __('cap') }} {{ number_format($c['max']) }}
                                    @endif
                                </div>
                            </div>

                            <div class="text-end whitespace-nowrap">
                                <div class="font-extrabold text-ink-900 tabular-nums text-lg">{{ number_format($c['votes']) }}</div>
                            </div>
                        </div>
                        <div class="mt-2 w-full h-1.5 rounded-full bg-ink-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $rank === 1 && $c['votes'] > 0 ? 'bg-gradient-to-r from-accent-400 to-accent-600' : 'bg-brand-500' }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── CTA ROW: results + share ──────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($resultsAnnounced)
            <a href="{{ route('public.results', $campaign->public_token) }}"
               class="block rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white p-5 shadow-lg hover:shadow-xl transition group">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-3xl">🏆</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-semibold text-white/70
                                    {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                            {{ __('Results announced') }}
                        </div>
                        <div class="text-lg md:text-xl font-extrabold mt-0.5">{{ __('See the winners') }}</div>
                    </div>
                    <span class="opacity-70 group-hover:opacity-100 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition text-xl">→</span>
                </div>
            </a>
        @else
            <div class="rounded-3xl bg-brand-50 border border-brand-200 p-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-brand-600 text-white flex items-center justify-center text-3xl">📢</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-semibold text-brand-700
                                    {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                            {{ __('Results') }}
                        </div>
                        <div class="text-lg font-bold text-brand-800 mt-0.5 leading-tight">
                            {{ __('Results will appear here once the committee announces them.') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Share this page --}}
        <div class="rounded-3xl bg-white border border-ink-200 shadow-sm p-5 flex items-center gap-3">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-3xl flex-shrink-0">🔗</div>
            <div class="flex-1 min-w-0">
                <div class="text-[11px] font-semibold text-ink-500
                            {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                    {{ __('Share this page') }}
                </div>
                <input type="text" readonly value="{{ url()->current() }}"
                       onclick="this.select()"
                       class="w-full font-mono text-xs text-ink-800 bg-transparent border-0 p-0 mt-0.5 focus:outline-none">
            </div>
            <button type="button"
                    onclick="navigator.clipboard.writeText('{{ url()->current() }}'); this.innerText='✓ {{ __('Copied') }}'; setTimeout(()=>this.innerText='📋 {{ __('Copy') }}', 1500);"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 hover:bg-ink-50 text-ink-700 px-3 py-1.5 text-xs font-semibold transition flex-shrink-0">
                📋 {{ __('Copy') }}
            </button>
        </div>
    </div>

    <footer class="text-center text-xs text-ink-500 pt-2 pb-6">
        © {{ date('Y') }} {{ \App\Modules\Shared\Support\Branding::name() }} — {{ __('Official Platform') }}
    </footer>

</div>

</div>

<script>
function statsPage({ phase, target, labels }) {
    return {
        phase, target, labels,
        days: 0, hours: 0, minutes: 0, seconds: 0,
        pad(n) { return String(n).padStart(2, '0'); },
        tick() {
            if (this.phase === 'ended' || !this.target) { this.phase = 'ended'; return; }
            const now  = new Date();
            const end  = new Date(this.target);
            let   diff = Math.max(0, end - now);
            if (diff <= 0) {
                this.phase = 'ended';
                this.days = this.hours = this.minutes = this.seconds = 0;
                return;
            }
            const d = Math.floor(diff / 86400000); diff -= d * 86400000;
            const h = Math.floor(diff / 3600000);  diff -= h * 3600000;
            const m = Math.floor(diff / 60000);    diff -= m * 60000;
            const s = Math.floor(diff / 1000);
            this.days = d; this.hours = h; this.minutes = m; this.seconds = s;
        },
    };
}
</script>

</body>
</html>
