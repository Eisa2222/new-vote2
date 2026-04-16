@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<?php
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation as F;
    use App\Modules\Campaigns\Enums\CampaignType;

    $isTos = $campaign->type === CampaignType::TeamOfTheSeason;
    $formation = $isTos ? (F::fromCampaign($campaign) ?: F::default()) : null;
?>
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Thank you') }} — {{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
</head>
<body class="bg-gradient-to-br from-brand-50 via-white to-brand-100 min-h-screen">

<div class="max-w-4xl mx-auto px-4 py-10 md:py-16 space-y-6">

    <div class="bg-white rounded-3xl shadow-brand p-8 md:p-10 text-center border border-ink-200">
        <div class="w-20 h-20 mx-auto rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-5xl mb-6">&#10003;</div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-ink-900">{{ __('Thank you for voting!') }}</h1>
        <p class="mt-3 text-ink-700 text-lg">{{ $campaign->localized('title') }}</p>
        <p class="mt-5 text-sm text-ink-500 leading-7 max-w-xl mx-auto">
            {{ __('Results will be announced once approved by the committee.') }}
        </p>
    </div>

    @if($isTos && !empty($picks))
        <div>
            <div class="text-center mb-4">
                <h2 class="text-xl md:text-2xl font-bold text-ink-900">{{ __('Your Team of the Season') }}</h2>
                <p class="text-sm text-ink-500 mt-1">
                    {{ __('Formation') }}: <strong>{{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}</strong>
                </p>
            </div>

            <x-team-of-season.submission-summary
                :campaign="$campaign"
                :formation="$formation"
                :picks="$picks" />
        </div>
    @endif

    <div class="text-center text-xs text-ink-500">
        © {{ date('Y') }} {{ __('Saudi Football Players Association') }}
    </div>
</div>

</body>
</html>
