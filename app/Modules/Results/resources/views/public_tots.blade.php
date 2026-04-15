@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<?php
    $winnersBySlot = $result->items->where('is_winner', true)->groupBy('position');
    $formation = \App\Modules\Campaigns\Domain\TeamOfSeasonFormation::fromCampaign($campaign);
?>
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Results') }} — {{ $campaign->localized('title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: '{{ $locale === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif; }
        .pitch { background: linear-gradient(to bottom, #047857, #065f46); position: relative; }
        .pitch::before { content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(to right, rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-emerald-800 text-white p-8 md:p-10 shadow-2xl text-center">
        <div class="text-emerald-300 text-sm font-semibold">{{ __('Team of the Season') }} — {{ __('Official Results') }}</div>
        <h1 class="text-3xl md:text-4xl font-bold mt-2">{{ $campaign->localized('title') }}</h1>
        <p class="text-slate-300 mt-3">
            {{ __('Total votes') }}: <strong>{{ number_format($result->total_votes) }}</strong> ·
            {{ $result->announced_at?->format('Y-m-d') }}
        </p>
    </section>

    <section class="pitch rounded-3xl overflow-hidden shadow-2xl p-6 md:p-10">
        <div class="relative z-10 space-y-8">
            @foreach($formation as $slot => $n)
                <div>
                    <div class="text-center text-white mb-4 font-semibold">
                        {{ __(ucfirst($slot)) }} ({{ $n }})
                    </div>
                    <div class="flex flex-wrap justify-center gap-3 md:gap-5">
                        @foreach($winnersBySlot->get($slot, collect())->sortBy('rank') as $item)
                            <?php
                                $p = $item->candidate->player;
                                $name = $p?->localized('name');
                                $club = $p?->club?->localized('name');
                                $photo = $p?->photo_path;
                            ?>
                            <div class="w-40 rounded-2xl bg-white p-3 text-center shadow-lg">
                                <div class="w-20 h-20 mx-auto rounded-full bg-slate-100 overflow-hidden mb-2 flex items-center justify-center text-3xl">
                                    @if($photo)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        🧍
                                    @endif
                                </div>
                                <div class="font-bold text-sm text-gray-900 truncate">{{ $name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ $club }}</div>
                                <div class="mt-2 text-xs text-emerald-700 font-semibold">
                                    {{ $item->votes_count }} {{ __('votes') }} · {{ $item->vote_percentage }}%
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
</body>
</html>
