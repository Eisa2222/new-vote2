@php
    use App\Modules\Campaigns\Enums\CampaignType;
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;

    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';

    $typeMeta = [
        'individual_award' => [
            'icon' => '👤',
            'label' => __('Individual award'),
            'color' => 'from-brand-600 to-brand-800',
        ],
        'team_award' => ['icon' => '🛡️', 'label' => __('Team award'), 'color' => 'from-blue-600 to-brand-800'],
        'team_of_the_season' => [
            'icon' => '⚽',
            'label' => __('Team of the Season'),
            'color' => 'from-emerald-700 to-brand-800',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🗳️ {{ __('Open voting campaigns') }}</title>
    @include('partials.brand-head')
    <style>
        .hero-bg {
            background:
                radial-gradient(ellipse at top left, rgba(200, 163, 101, 0.22) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(17, 92, 66, 0.4) 0%, transparent 50%),
                linear-gradient(135deg, #0B3D2E 0%, #115C42 50%, #083024 100%);
        }
    </style>
</head>

<body class="bg-ink-50 min-h-screen">

    <div class="max-w-6xl mx-auto px-3 md:px-6 py-6 md:py-10 space-y-6">

        {{-- HERO --}}
        <section class="hero-bg text-white rounded-[2rem] p-6 md:p-10 shadow-brand relative overflow-hidden text-center">
            <div class="absolute inset-0 opacity-30 pointer-events-none"
                style="background-image: radial-gradient(circle at 25% 30%, #C8A365 1.5px, transparent 2.5px),
                                       radial-gradient(circle at 75% 65%, #DDB97A 2px, transparent 3px);
                    background-size: 110px 110px, 140px 140px;">
            </div>
            <div class="relative z-10">
                <div
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-500/20 border border-accent-400/40 backdrop-blur text-accent-400 text-xs font-bold tracking-[0.25em] uppercase">
                    🗳️ {{ __('Cast your vote') }}
                </div>
                <h1 class="mt-5 text-3xl md:text-5xl font-black leading-tight">
                    {{ __('Open voting campaigns') }}
                </h1>
                <p class="text-brand-100 mt-3 max-w-2xl mx-auto text-sm md:text-base">
                    {{ __('Pick a campaign, verify your identity, and cast your vote. Each player can vote once per campaign.') }}
                </p>
                <div class="mt-5 inline-flex flex-wrap items-center gap-3 text-xs">
                    <span class="rounded-full bg-white/10 border border-white/20 backdrop-blur px-3 py-1.5">
                        {{ __('Open now') }}: <strong class="text-accent-400">{{ $open->count() }}</strong>
                    </span>
                    <span class="rounded-full bg-white/10 border border-white/20 backdrop-blur px-3 py-1.5">
                        {{ __('Upcoming') }}: <strong class="text-accent-400">{{ $upcoming->count() }}</strong>
                    </span>
                    <a href="{{ route('public.results.index') }}"
                        class="rounded-full bg-accent-500 hover:bg-accent-600 text-white px-4 py-1.5 font-bold">
                        🏆 {{ __('View announced results') }}
                    </a>
                </div>
            </div>
        </section>

        {{-- OPEN NOW --}}
        @if ($open->isNotEmpty())
            <section>
                <h2 class="text-xl md:text-2xl font-extrabold text-ink-900 mb-4 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ __('Voting open now') }}
                    <span class="text-sm text-ink-500 font-normal">({{ $open->count() }})</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach ($open as $c)
                        @php
                            $meta = $typeMeta[$c->type->value] ?? [
                                'icon' => '🗳️',
                                'label' => '',
                                'color' => 'from-brand-600 to-brand-800',
                            ];
                            $pct = $c->max_voters ? min(100, round(($c->votes_count / $c->max_voters) * 100)) : null;
                        @endphp
                        <a href="{{ route('voting.show', $c->public_token) }}"
                            class="group block rounded-3xl overflow-hidden bg-white border border-ink-200 shadow-sm hover:shadow-brand hover:-translate-y-0.5 transition">
                            <div
                                class="relative p-5 md:p-6 bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} {{ $meta['color'] }} text-white">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-2xl flex-shrink-0">
                                        {{ $meta['icon'] }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[10px] uppercase tracking-[0.25em] text-accent-400 font-bold">
                                            {{ $meta['label'] }}
                                        </div>
                                        <h3 class="text-lg md:text-xl font-extrabold mt-1 leading-tight">
                                            {{ $c->localized('title') }}
                                        </h3>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/90 text-white text-[10px] font-bold px-2.5 py-1 flex-shrink-0">
                                        <span
                                            class="inline-block w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        {{ __('LIVE') }}
                                    </span>
                                </div>

                                @if ($c->localized('description'))
                                    <p class="text-brand-100/90 text-xs md:text-sm mt-3 leading-relaxed line-clamp-2">
                                        {{ \Illuminate\Support\Str::limit($c->localized('description'), 140) }}
                                    </p>
                                @endif
                            </div>

                            <div class="p-5 md:p-6">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl bg-brand-50 border border-brand-200 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-brand-600 font-bold">
                                            {{ __('Closes') }}</div>
                                        <div class="font-extrabold text-brand-900 mt-0.5 text-sm">
                                            {{ $c->end_at->diffForHumans(['parts' => 2, 'short' => true]) }}
                                        </div>
                                        <div class="text-[10px] text-ink-500 mt-0.5">
                                            {{ $c->end_at->translatedFormat('d M · H:i') }}</div>
                                    </div>
                                    <div class="rounded-xl bg-ink-50 border border-ink-200 p-3">
                                        <div class="text-[10px] uppercase tracking-wider text-ink-600 font-bold">
                                            {{ __('Votes') }}</div>
                                        <div class="font-extrabold text-ink-900 mt-0.5 text-sm">
                                            {{ number_format($c->votes_count) }}
                                            @if ($c->max_voters)
                                                <span class="text-ink-400 font-normal">/
                                                    {{ number_format($c->max_voters) }}</span>
                                            @endif
                                        </div>
                                        @if ($pct !== null)
                                            <div class="mt-1.5 h-1 rounded-full bg-ink-100 overflow-hidden">
                                                <div class="h-full bg-accent-500 rounded-full"
                                                    style="width: {{ $pct }}%"></div>
                                            </div>
                                        @else
                                            <div class="text-[10px] text-ink-500 mt-0.5">{{ __('No cap') }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="mt-4 inline-flex items-center justify-center w-full rounded-xl bg-brand-600 group-hover:bg-brand-700 text-white px-5 py-3 font-bold shadow-brand transition">
                                    🗳️ {{ __('Vote now') }}
                                    <span
                                        class="ms-2 transition group-hover:translate-x-1 rtl:group-hover:-translate-x-1">
                                        {{ $dir === 'rtl' ? '←' : '→' }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- UPCOMING --}}
        @if ($upcoming->isNotEmpty())
            <section>
                <h2 class="text-xl md:text-2xl font-extrabold text-ink-900 mb-4 flex items-center gap-2">
                    ⏳ {{ __('Upcoming') }}
                    <span class="text-sm text-ink-500 font-normal">({{ $upcoming->count() }})</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach ($upcoming as $c)
                        @php $meta = $typeMeta[$c->type->value] ?? ['icon' => '🗳️', 'label' => '']; @endphp
                        <div class="rounded-2xl bg-white border border-ink-200 p-4 opacity-90">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                                    {{ $meta['icon'] }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-ink-900 truncate">{{ $c->localized('title') }}</div>
                                    <div class="text-xs text-ink-500 mt-0.5">
                                        {{ __('Opens') }} {{ $c->start_at->diffForHumans() }}
                                    </div>
                                    <div class="text-[11px] text-ink-400 mt-0.5">
                                        {{ $c->start_at->translatedFormat('d M Y · H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- EMPTY STATE --}}
        @if ($open->isEmpty() && $upcoming->isEmpty())
            <div class="rounded-3xl bg-white border border-ink-200 p-10 text-center shadow-sm">
                <div class="text-6xl mb-4">🗳️</div>
                <h2 class="text-xl font-extrabold text-ink-900">{{ __('No open campaigns right now.') }}</h2>
                <p class="text-sm text-ink-500 mt-2 max-w-md mx-auto">
                    {{ __('Come back later — new campaigns are announced regularly.') }}
                </p>
                <a href="{{ route('public.results.index') }}"
                    class="inline-flex items-center gap-2 mt-6 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-3 font-semibold">
                    🏆 {{ __('Browse past results') }}
                </a>
            </div>
        @endif

        <footer class="text-center text-xs text-ink-500 py-4">
            © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Voting Platform') }}
        </footer>
    </div>

</body>

</html>
