@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
    $isAr   = $locale === 'ar';

    // Countdown target for the hero:
    //   • voting_open → count down to end_at
    //   • awaiting_announcement → no countdown, just the "under review" banner
    $target = $phase === 'voting_open' ? $campaign->end_at->toIso8601String() : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>⏳ {{ __('Results coming soon') }} — {{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        .hero-bg {
            background:
                radial-gradient(ellipse at top left, rgba(200,163,101,0.22) 0%, transparent 55%),
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
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-ink-50 min-h-screen flex flex-col">

<main class="flex-1 flex items-center justify-center px-4 py-8 md:py-12">
    <div class="w-full max-w-3xl">
        <section class="hero-bg text-white rounded-[2rem] relative overflow-hidden shadow-brand dots"
                 x-data="pendingResults({
                    target: @js($target),
                    labels: {
                        days: @js(__('days')), hours: @js(__('hours')),
                        mins: @js(__('mins')), secs: @js(__('secs')),
                    }
                 })"
                 x-init="tick(); setInterval(() => tick(), 1000);">
            {{-- Locale switcher — top-end of the hero (absolute). --}}
            <div class="absolute top-2 end-3 z-20">
                <div class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 backdrop-blur overflow-hidden text-xs font-semibold">
                    <a href="?locale=ar" class="px-3 py-1.5 transition {{ $locale === 'ar' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">AR</a>
                    <a href="?locale=en" class="px-3 py-1.5 transition {{ $locale === 'en' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">EN</a>
                </div>
            </div>

            <div class="relative p-6 md:p-10 text-center">

                {{-- Phase pill --}}
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur text-white/85 text-[11px] font-semibold
                            {{ $isAr ? '' : 'uppercase tracking-[0.2em]' }}">
                    @if($phase === 'voting_open')
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        <span>{{ __('Voting is still live') }}</span>
                    @else
                        <span>🧐</span>
                        <span>{{ __('Awaiting committee review') }}</span>
                    @endif
                </div>

                {{-- Giant hourglass emoji as the visual anchor --}}
                <div class="mt-6 mx-auto w-24 h-24 md:w-28 md:h-28 rounded-3xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-6xl md:text-7xl">
                    ⏳
                </div>

                <h1 class="mt-6 text-2xl md:text-4xl font-black leading-tight">
                    @if($phase === 'voting_open')
                        {{ __('Voting has not ended yet') }}
                    @else
                        {{ __('Results under committee review') }}
                    @endif
                </h1>

                <p class="mt-4 text-white/85 leading-7 max-w-xl mx-auto text-sm md:text-base">
                    @if($phase === 'voting_open')
                        {{ __('Results will be published the moment voting closes and the committee announces them.') }}
                    @else
                        {{ __('Voting has ended. The committee is reviewing the tally — results will appear here as soon as the announcement is made.') }}
                    @endif
                </p>

                {{-- Countdown only while voting is open --}}
                @if($phase === 'voting_open')
                    <div x-show="target" x-cloak class="mt-8 inline-flex items-baseline gap-3 tabular-nums text-white bg-white/10 backdrop-blur border border-white/20 rounded-2xl px-5 py-4">
                        <div class="text-[10px] font-bold text-white/70 self-center
                                    {{ $isAr ? '' : 'uppercase tracking-[0.2em]' }}">
                            {{ __('Voting ends in') }}
                        </div>
                        <template x-if="days > 0">
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl md:text-4xl font-black" x-text="days"></span>
                                <span class="text-xs text-white/70" x-text="labels.days"></span>
                            </div>
                        </template>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl md:text-4xl font-black" x-text="pad(hours)"></span>
                            <span class="text-xs text-white/70" x-text="labels.hours"></span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl md:text-4xl font-black" x-text="pad(minutes)"></span>
                            <span class="text-xs text-white/70" x-text="labels.mins"></span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl md:text-4xl font-black text-accent-400" x-text="pad(seconds)"></span>
                            <span class="text-xs text-white/70" x-text="labels.secs"></span>
                        </div>
                    </div>
                @endif

                {{-- Campaign identity --}}
                <div class="mt-6 pt-6 border-t border-white/10">
                    <div class="text-[11px] font-semibold text-white/60
                                {{ $isAr ? '' : 'uppercase tracking-widest' }}">
                        {{ __('Campaign') }}
                    </div>
                    <div class="text-lg md:text-xl font-bold mt-1">{{ $campaign->localized('title') }}</div>
                    <div class="text-xs text-white/70 mt-1 tabular-nums">
                        🗓 {{ $campaign->start_at->format('Y-m-d') }}
                        <span class="opacity-60 mx-1">→</span>
                        {{ $campaign->end_at->format('Y-m-d') }}
                    </div>
                </div>

                {{-- CTA back to live stats --}}
                <div class="mt-6 flex items-center justify-center gap-2 flex-wrap">
                    <a href="{{ route('public.campaigns.stats', $campaign->public_token) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-white px-4 py-2.5 text-sm font-semibold transition">
                        📊 {{ __('See live campaign stats') }}
                    </a>
                    <a href="{{ route('public.results.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/20 hover:bg-white/10 text-white/90 px-4 py-2.5 text-sm font-medium transition">
                        🗂 {{ __('Browse announced results') }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

<footer class="text-center text-xs text-ink-500 py-5">
    © {{ date('Y') }} {{ \App\Modules\Shared\Support\Branding::name() }} — {{ __('Official Platform') }}
</footer>

<script>
function pendingResults({ target, labels }) {
    return {
        target, labels,
        days: 0, hours: 0, minutes: 0, seconds: 0,
        pad(n) { return String(n).padStart(2, '0'); },
        tick() {
            if (!this.target) return;
            const now  = new Date();
            const end  = new Date(this.target);
            let   diff = Math.max(0, end - now);
            if (diff <= 0) {
                // Voting just ended — bounce to the same URL so the
                // controller re-evaluates the phase (likely flips to
                // "awaiting_announcement").
                this.target = null;
                setTimeout(() => window.location.reload(), 500);
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
