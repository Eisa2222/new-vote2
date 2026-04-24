@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';

    // Pull the single winner(s) out — they get the hero treatment;
    // everyone else is shown below as the ranking table.
    $winners = $result->items->where('is_winner', true)->sortBy('rank')->values();
    $byCategory = $result->items->groupBy('voting_category_id');
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

    {{-- HERO — winner spotlight --}}
    <section class="trophy-bg text-white rounded-[2rem] p-6 md:p-12 shadow-brand relative overflow-hidden text-center">
        {{-- Decorative confetti dots --}}
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background-image: radial-gradient(circle at 20% 20%, #C8A365 2px, transparent 3px),
                                       radial-gradient(circle at 70% 60%, #DDB97A 1.5px, transparent 2.5px),
                                       radial-gradient(circle at 40% 80%, #C8A365 2px, transparent 3px);
                    background-size: 120px 120px, 90px 90px, 100px 100px;"></div>

        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-500/20 border border-accent-400/40 backdrop-blur text-accent-400 text-xs font-bold tracking-[0.25em] uppercase">
                🏆 {{ __('Official announcement') }}
            </div>

            <h1 class="mt-5 text-3xl md:text-5xl font-black leading-tight">
                {{ $campaign->localized('title') }}
            </h1>
            <p class="text-brand-100 mt-3 text-sm md:text-base">
                {{ __('Results approved by the Voting Committee') }}
            </p>

            {{-- Winner card(s) --}}
            @if($winners->isNotEmpty())
                <div class="mt-8 grid @if($winners->count() === 1) grid-cols-1 @else grid-cols-1 md:grid-cols-2 @endif gap-5 max-w-3xl mx-auto">
                    @foreach($winners as $w)
                        @php
                            $p     = $w->candidate->player;
                            $name  = $p?->localized('name') ?? $w->candidate->club?->localized('name');
                            $club  = $p?->club?->localized('name');
                            $photo = $p?->photo_path
                                ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                                : ($w->candidate->club?->logo_path
                                    ? \Illuminate\Support\Facades\Storage::url($w->candidate->club->logo_path) : null);
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

                            <div class="mt-4 text-xs uppercase tracking-[0.25em] text-accent-600 font-bold">
                                🏆 {{ __('Winner') }}
                            </div>
                            <div class="mt-1 text-xl md:text-2xl font-extrabold">{{ $name }}</div>
                            @if($club)
                                <div class="text-sm text-ink-500 mt-0.5">{{ $club }}</div>
                            @endif

                            <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-brand-50 border border-brand-200 px-4 py-1.5 text-brand-700 text-sm font-bold">
                                {{ number_format($w->votes_count) }} {{ __('votes') }}
                                <span class="text-ink-400">·</span>
                                {{ $w->vote_percentage }}%
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 inline-flex items-center gap-4 text-xs text-brand-100">
                <span>{{ __('Total votes') }}: <strong class="text-white">{{ number_format($result->total_votes) }}</strong></span>
                @if($result->announced_at)
                    <span>·</span>
                    <span>{{ __('Announced') }}: <strong class="text-white">{{ $result->announced_at->translatedFormat('d M Y') }}</strong></span>
                @endif
            </div>
        </div>
    </section>

    {{-- Full ranking per category --}}
    @foreach($byCategory as $categoryId => $items)
        @php $category = $items->first()->category; @endphp
        <section class="rounded-3xl border border-ink-200 bg-white p-6 shadow-sm">
            <header class="mb-5 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-xl md:text-2xl font-extrabold text-ink-900">{{ $category->localized('title') }}</h2>
                    <div class="text-xs text-ink-500 mt-0.5">
                        {{ __(':n candidates', ['n' => $items->count()]) }}
                    </div>
                </div>
                <div class="text-xs text-ink-500">
                    {{ __('Ranked by total votes') }}
                </div>
            </header>

            <div class="space-y-2">
                @foreach($items->sortBy('rank') as $item)
                    @php
                        $ip    = $item->candidate->player;
                        $label = $ip?->localized('name') ?? $item->candidate->club?->localized('name');
                        $club  = $ip?->club?->localized('name');
                        $img   = $ip?->photo_path
                            ? \Illuminate\Support\Facades\Storage::url($ip->photo_path)
                            : ($item->candidate->club?->logo_path
                                ? \Illuminate\Support\Facades\Storage::url($item->candidate->club->logo_path) : null);
                    @endphp
                    @php $gradDir = $dir === 'rtl' ? 'l' : 'r'; @endphp
                    <div class="rounded-2xl border-2 p-3 md:p-4 transition {{ $item->is_winner ? 'border-accent-400 bg-gradient-to-'.$gradDir.' from-accent-500/10 to-white shadow' : 'border-ink-200 bg-white' }}">
                        <div class="flex items-center gap-3 md:gap-4">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm
                                        {{ $item->is_winner ? 'bg-accent-500 text-white' : ($item->rank <= 3 ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-500') }}">
                                @if($item->rank === 1)🥇
                                @elseif($item->rank === 2)🥈
                                @elseif($item->rank === 3)🥉
                                @else #{{ $item->rank }}
                                @endif
                            </div>

                            @if($img)
                                <img src="{{ $img }}" alt="{{ $label }}" class="w-11 h-11 md:w-12 md:h-12 rounded-full object-cover border-2 {{ $item->is_winner ? 'border-accent-400' : 'border-ink-200' }}">
                            @else
                                <div class="w-11 h-11 md:w-12 md:h-12 rounded-full bg-brand-100 text-brand-700 font-extrabold flex items-center justify-center border-2 {{ $item->is_winner ? 'border-accent-400' : 'border-ink-200' }}">
                                    {{ mb_strtoupper(mb_substr($label ?? '?', 0, 1)) }}
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-ink-900 truncate">
                                    {{ $label }}
                                    @if($item->is_winner)
                                        <span class="inline-block ms-1 px-2 py-0.5 rounded-full text-[10px] bg-accent-500 text-white font-black align-middle">★ {{ __('Winner') }}</span>
                                    @endif
                                </div>
                                @if($club)<div class="text-xs text-ink-500 truncate">{{ $club }}</div>@endif
                            </div>

                            <div class="text-end whitespace-nowrap">
                                <div class="font-extrabold text-ink-900 text-lg">{{ number_format($item->votes_count) }}</div>
                                <div class="text-xs text-ink-500">{{ $item->vote_percentage }}%</div>
                            </div>
                        </div>
                        <div class="mt-2 w-full h-1.5 rounded-full bg-ink-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $item->is_winner ? 'bg-gradient-to-r from-accent-400 to-accent-600' : 'bg-brand-400' }}"
                                 style="width: {{ $item->vote_percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <footer class="text-center text-xs text-ink-500 pb-6">
        © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Official winner announcement') }}
    </footer>
</div>

</body>
</html>
