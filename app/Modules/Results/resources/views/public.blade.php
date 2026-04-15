@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Results') }} — {{ $campaign->localized('title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: '{{ $locale === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-emerald-800 text-white p-8 md:p-10 shadow-2xl text-center">
        <div class="text-emerald-300 text-sm font-semibold">{{ __('Official Results') }}</div>
        <h1 class="text-3xl md:text-4xl font-bold mt-2">{{ $campaign->localized('title') }}</h1>
        <p class="text-slate-300 mt-3">
            {{ __('Total votes') }}: <strong>{{ number_format($result->total_votes) }}</strong> ·
            {{ __('Announced at') }} {{ $result->announced_at?->format('Y-m-d H:i') }}
        </p>
    </section>

    @foreach($result->items->groupBy('voting_category_id') as $categoryId => $items)
        <?php $category = $items->first()->category; ?>
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-bold mb-5">{{ $category->localized('title') }}</h2>

            <div class="space-y-3">
                @foreach($items->sortBy('rank') as $item)
                    <?php
                        $label = $item->candidate->player?->localized('name')
                              ?? $item->candidate->club?->localized('name');
                        $club  = $item->candidate->player?->club?->localized('name');
                    ?>
                    <div class="rounded-2xl border p-4 {{ $item->is_winner ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200' }}">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-lg font-bold text-slate-400 w-8">#{{ $item->rank }}</span>
                                <div class="min-w-0">
                                    <div class="font-semibold truncate">{{ $label }}</div>
                                    @if($club) <div class="text-xs text-gray-500 truncate">{{ $club }}</div> @endif
                                </div>
                                @if($item->is_winner)
                                    <span class="ms-2 px-2 py-0.5 rounded-full text-xs bg-emerald-600 text-white">★ {{ __('Winner') }}</span>
                                @endif
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <div class="font-bold">{{ $item->votes_count }}</div>
                                <div class="text-xs text-gray-500">{{ $item->vote_percentage }}%</div>
                            </div>
                        </div>
                        <div class="mt-2 w-full h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full {{ $item->is_winner ? 'bg-emerald-500' : 'bg-slate-400' }}"
                                 style="width: {{ $item->vote_percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
</body>
</html>
