@php
    use App\Modules\Campaigns\Services\CampaignAvailabilityService as Avail;

    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';

    $copy = match ($reason) {
        Avail::MAX_VOTERS_REACHED => [
            'emoji' => '🏁',
            'title' => __('Voter limit reached'),
            'body'  => __('Thank you — this vote has reached the maximum number of voters allowed. The campaign is now closed to new submissions.'),
            'tone'  => 'from-amber-500 to-amber-600',
        ],
        Avail::ENDED => [
            'emoji' => '⏱️',
            'title' => __('Voting has ended'),
            'body'  => __('The voting window for this campaign closed on :date.', ['date' => $campaign->end_at->translatedFormat('d M Y · H:i')]),
            'tone'  => 'from-rose-500 to-rose-600',
        ],
        Avail::NOT_STARTED => [
            'emoji' => '⏳',
            'title' => __('Voting has not started'),
            'body'  => __('Come back on :date when this campaign opens.', ['date' => $campaign->start_at->translatedFormat('d M Y · H:i')]),
            'tone'  => 'from-blue-500 to-blue-600',
        ],
        Avail::CLOSED => [
            'emoji' => '🔒',
            'title' => __('Campaign closed'),
            'body'  => __('This campaign was closed by the administrator.'),
            'tone'  => 'from-ink-500 to-ink-700',
        ],
        Avail::NOT_PUBLISHED => [
            'emoji' => '📝',
            'title' => __('Not open yet'),
            'body'  => __('This campaign is still a draft or has been archived.'),
            'tone'  => 'from-ink-500 to-ink-700',
        ],
        default => [
            'emoji' => 'ℹ️',
            'title' => __('Not available'),
            'body'  => __('This campaign is not available right now.'),
            'tone'  => 'from-ink-500 to-ink-700',
        ],
    };

    $totalVoters = $campaign->votes()->count();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $copy['title'] }} — {{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
</head>
<body class="bg-gradient-to-br from-brand-50 via-white to-brand-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl">
    <div class="bg-white rounded-3xl shadow-brand overflow-hidden border border-ink-200">
        <div class="bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} {{ $copy['tone'] }} text-white p-8 md:p-10 text-center">
            <div class="text-6xl md:text-7xl mb-4">{{ $copy['emoji'] }}</div>
            <h1 class="text-2xl md:text-3xl font-extrabold">{{ $copy['title'] }}</h1>
            <p class="text-white/90 text-sm mt-2">{{ $campaign->localized('title') }}</p>
        </div>

        <div class="p-6 md:p-8">
            <p class="text-ink-700 text-center leading-7">
                {{ $copy['body'] }}
            </p>

            @if($reason === Avail::MAX_VOTERS_REACHED && $campaign->max_voters)
                <div class="mt-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-center">
                    <div class="text-xs text-amber-700 uppercase tracking-wider font-semibold">{{ __('Voter count') }}</div>
                    <div class="mt-1 text-2xl font-extrabold text-amber-900">
                        {{ number_format($totalVoters) }} / {{ number_format($campaign->max_voters) }}
                    </div>
                </div>
            @endif

            <div class="mt-8 text-center">
                <div class="text-xs text-ink-500">
                    {{ __('Results will be announced once approved by the committee.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-6 text-xs text-ink-500">
        © {{ date('Y') }} {{ __('Saudi Football Players Association') }}
    </div>
</div>

</body>
</html>
