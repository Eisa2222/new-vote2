@extends('voting::club._layout')
@section('title', __('Already voted'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Brand-gradient hero confirms the vote was already recorded —
         friendly "all good, nothing to worry about" framing. --}}
    <div class="relative rounded-3xl overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-700 via-brand-600 to-brand-400"></div>
        <div class="absolute inset-0 opacity-15"
             style="background-image: radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative p-8 md:p-10 text-center text-white">
            <div class="inline-flex w-20 h-20 rounded-3xl bg-white/15 backdrop-blur items-center justify-center text-5xl mb-4">
                ✅
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold">{{ __('You have already voted') }}</h1>
            <p class="text-white/90 mt-3 leading-7 max-w-lg mx-auto">
                {{ __('Your ballot for :title was recorded. Each voter may only participate once per campaign.', ['title' => $campaign->localized('title')]) }}
            </p>

            <div class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-4 py-2 text-sm">
                <span class="text-xs uppercase tracking-widest text-white/70">{{ __('Club') }}:</span>
                <strong>{{ $club->localized('name') }}</strong>
            </div>
        </div>
    </div>

    {{-- Info stripe — explains what happens next in warm brand tones --}}
    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 text-sm text-brand-800 flex items-start gap-3">
        <span class="text-xl leading-none">📢</span>
        <div>
            <div class="font-bold mb-1">{{ __('What happens next?') }}</div>
            <p class="text-brand-700 leading-7">{{ __('Results will be announced by the committee once the campaign ends.') }}</p>
        </div>
    </div>

    {{-- Voters arrive here from a deep club link; the "campaigns
         list" fallback is unfamiliar so the primary CTA is just
         "Done" now. --}}
    <div class="flex items-center justify-center gap-2 flex-wrap">
        <button type="button"
                onclick="window.close(); setTimeout(() => { window.location.href='/'; }, 300);"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 font-semibold shadow-sm transition">
            <span aria-hidden="true">✓</span>
            <span>{{ __('Done') }}</span>
        </button>
    </div>
</div>
@endsection
