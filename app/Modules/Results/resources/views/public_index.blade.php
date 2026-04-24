@php
    use App\Modules\Campaigns\Enums\CampaignType;
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;

    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🏆 {{ __('Official announcements') }}</title>
    @include('partials.brand-head')
    <style>
        .trophy-bg {
            background:
                radial-gradient(ellipse at top, rgba(200,163,101,0.25) 0%, transparent 55%),
                radial-gradient(ellipse at bottom left, rgba(17,92,66,0.3) 0%, transparent 45%),
                linear-gradient(135deg, #0B3D2E 0%, #115C42 50%, #083024 100%);
        }
    </style>
</head>
<body class="bg-ink-50 min-h-screen">

<div class="max-w-6xl mx-auto px-3 md:px-6 py-6 md:py-10 space-y-6">

    {{-- HERO --}}
    <section class="trophy-bg text-white rounded-[2rem] p-6 md:p-10 shadow-brand relative overflow-hidden text-center">
        <div class="absolute inset-0 opacity-30 pointer-events-none"
             style="background-image: radial-gradient(circle at 20% 30%, #C8A365 1.5px, transparent 2.5px),
                                       radial-gradient(circle at 80% 70%, #DDB97A 2px, transparent 3px);
                    background-size: 110px 110px, 140px 140px;"></div>

        {{-- Language switcher — the index page was missing one, so
             non-Arabic visitors had no way to flip the UI. --}}
        <div class="absolute top-4 end-4 z-20">
            <div class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 backdrop-blur overflow-hidden text-xs font-semibold">
                <a href="?locale=ar" class="px-3 py-1.5 transition {{ $locale === 'ar' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">AR</a>
                <a href="?locale=en" class="px-3 py-1.5 transition {{ $locale === 'en' ? 'bg-white text-brand-800' : 'text-white/80 hover:text-white hover:bg-white/10' }}">EN</a>
            </div>
        </div>

        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-500/20 border border-accent-400/40 backdrop-blur text-accent-400 text-xs font-bold tracking-[0.25em] uppercase">
                🏆 {{ __('Official announcements') }}
            </div>
            <h1 class="mt-5 text-3xl md:text-5xl font-black leading-tight">
                {{ __('Saudi Football Players Association') }}
            </h1>
            <p class="text-brand-100 mt-3 max-w-2xl mx-auto text-sm md:text-base">
                {{ __('Browse every announced voting campaign and its winners.') }}
            </p>
            <div class="mt-5 inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 backdrop-blur px-4 py-1.5 text-xs">
                {{ __('Campaigns announced') }}: <strong class="text-accent-400 text-sm">{{ $results->count() }}</strong>
            </div>
        </div>
    </section>

    @if($results->isEmpty())
        <div class="rounded-3xl bg-white border border-ink-200 p-10 text-center shadow-sm">
            <div class="text-6xl mb-4">📭</div>
            <h2 class="text-xl font-extrabold text-ink-900">{{ __('No announcements yet.') }}</h2>
            <p class="text-sm text-ink-500 mt-2 max-w-md mx-auto">
                {{ __('Once the committee announces a campaign, it will appear here.') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($results as $r)
                @php
                    $c        = $r->campaign;
                    $winners  = $r->items->where('is_winner', true)->sortBy('rank');

                    // Split winners the same way the detail page does:
                    //   • individualWinners → Best Saudi + Best Foreign
                    //     (shown as side-by-side avatar cards on the
                    //     index tile so the lineup screenshot doesn't
                    //     end up headline-only with a single face)
                    //   • tosWinners        → Team of the Season
                    //     (summarised as a stacked-avatars formation
                    //     preview — already existed below)
                    $individualWinners = $winners->filter(fn ($w) =>
                        optional($w->category?->award_type)->value !== 'team_of_the_season'
                    )->values();
                    $tosWinners = $winners->filter(fn ($w) =>
                        optional($w->category?->award_type)->value === 'team_of_the_season'
                    );

                    // The card header badge still cares about the
                    // campaign-type label; TOS stays TOS even if the
                    // campaign also carries individual awards.
                    $hasTos = $tosWinners->isNotEmpty();
                    $formation = $hasTos ? TeamOfSeasonFormation::fromCampaign($c) : null;
                @endphp

                <a href="{{ route('public.results', $c->public_token) }}"
                   class="group block rounded-3xl overflow-hidden bg-white border border-ink-200 shadow-sm hover:shadow-brand hover:-translate-y-0.5 transition">
                    {{-- Card header --}}
                    <div class="relative p-5 md:p-6 trophy-bg text-white">
                        <div class="absolute top-3 end-3 text-accent-400 text-xs font-bold
                                    {{ $isAr ?? (app()->getLocale()==='ar') ? '' : 'tracking-[0.2em]' }}">
                            @if($hasTos && $individualWinners->isEmpty()) ⚽ {{ __('Team of the Season') }}
                            @elseif($hasTos) 🏆 {{ __('Awards') }}
                            @else 👤 {{ __('Individual award') }}
                            @endif
                        </div>
                        <div class="text-accent-400 font-bold text-[10px]
                                    {{ app()->getLocale() === 'ar' ? '' : 'uppercase tracking-[0.25em]' }}">
                            🏆 {{ __('Winner') }}
                        </div>
                        <h2 class="text-xl md:text-2xl font-extrabold mt-1 leading-tight pr-16">
                            {{ $c->localized('title') }}
                        </h2>
                        @if($r->announced_at)
                            <div class="mt-2 text-xs text-brand-100/80">
                                {{ __('Announced') }}: {{ $r->announced_at->translatedFormat('d M Y') }}
                            </div>
                        @endif
                    </div>

                    {{-- Card body: individual winners side-by-side, then
                         TOS formation strip below if this campaign also
                         ran a Team of the Season ballot. --}}
                    @if($individualWinners->isNotEmpty())
                        <div class="p-4 md:p-5 grid @if($individualWinners->count() === 1) grid-cols-1 @else grid-cols-2 @endif gap-3 border-b border-ink-100">
                            @foreach($individualWinners as $w)
                                @php
                                    $wp     = $w->candidate?->player;
                                    $wName  = $wp?->localized('name') ?? $w->candidate?->club?->localized('name');
                                    $wClub  = $wp?->club?->localized('name');
                                    $wImg   = $wp?->photo_path
                                        ? \Illuminate\Support\Facades\Storage::url($wp->photo_path)
                                        : ($w->candidate?->club?->logo_path
                                            ? \Illuminate\Support\Facades\Storage::url($w->candidate->club->logo_path) : null);
                                    $wTitle = $w->category?->localized('title');
                                @endphp
                                <div class="flex items-center gap-3 rounded-2xl bg-gradient-to-br from-brand-50/50 to-white border border-ink-100 p-3">
                                    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 p-0.5 shadow">
                                        <div class="w-full h-full rounded-full overflow-hidden bg-white flex items-center justify-center">
                                            @if($wImg)
                                                <img src="{{ $wImg }}" alt="{{ $wName }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-lg font-black text-brand-700">{{ mb_strtoupper(mb_substr($wName ?? '?', 0, 1)) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        @if($wTitle)
                                            <div class="text-[10px] font-bold text-accent-600 truncate
                                                        {{ app()->getLocale() === 'ar' ? '' : 'uppercase tracking-wider' }}">
                                                🏆 {{ $wTitle }}
                                            </div>
                                        @endif
                                        <div class="font-bold text-ink-900 truncate text-sm">{{ $wName ?: '—' }}</div>
                                        @if($wClub)
                                            <div class="text-[11px] text-ink-500 truncate">{{ $wClub }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($hasTos)
                        <div class="p-4 md:p-5 bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-brand-50 to-white">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-bold text-brand-800">
                                    ⚽ {{ __('Team of the Season') }}
                                    @if($formation)
                                        <span class="text-accent-600 font-black ms-1">
                                            {{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-ink-500">{{ $tosWinners->count() }} {{ __('Winner') }}</div>
                            </div>

                            <div class="flex -space-x-3 rtl:space-x-reverse overflow-hidden flex-wrap">
                                @foreach($tosWinners->take(7) as $w)
                                    @php
                                        $wp = $w->candidate?->player;
                                        $wi = $wp?->photo_path ? \Illuminate\Support\Facades\Storage::url($wp->photo_path) : null;
                                        $wn = $wp?->localized('name') ?? '?';
                                    @endphp
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 p-0.5 shadow">
                                        <div class="w-full h-full rounded-full overflow-hidden bg-white flex items-center justify-center">
                                            @if($wi)
                                                <img src="{{ $wi }}" alt="{{ $wn }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-[10px] font-black text-brand-700">{{ mb_strtoupper(mb_substr($wn, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                @if($tosWinners->count() > 7)
                                    <div class="w-9 h-9 rounded-full bg-ink-100 text-ink-600 text-[10px] font-bold flex items-center justify-center border-2 border-white">
                                        +{{ $tosWinners->count() - 7 }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="px-5 md:px-6 py-3 bg-white border-t border-ink-100 flex items-center justify-between text-sm font-bold text-brand-700 group-hover:bg-brand-50 transition">
                        <span>{{ __('View full results') }}</span>
                        <span class="group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition">{{ $dir === 'rtl' ? '←' : '→' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <footer class="text-center text-xs text-ink-500 py-4">
        © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Voting Platform') }}
    </footer>
</div>

</body>
</html>
