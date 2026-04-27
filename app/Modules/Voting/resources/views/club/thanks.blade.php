@extends('voting::club._layout')
@section('title', __('Thank you'))

@section('content')
    <div class="max-w-xl mx-auto">
        {{-- Single hero card. Two-state copy:
              • savedDetails=true  → "your details are saved + thanks"
              • savedDetails=false → plain "thanks for voting"
             Same visual frame either way for consistency. --}}
        <div class="relative rounded-3xl overflow-hidden shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800"></div>
            <div class="absolute inset-0 opacity-15"
                 style="background-image: radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>

            <div class="relative p-8 md:p-12 text-center text-white">
                <div class="mx-auto w-24 h-24 md:w-28 md:h-28 rounded-full bg-white/15 backdrop-blur border-2 border-white/30 flex items-center justify-center mb-5 shadow-xl">
                    <span class="text-6xl md:text-7xl leading-none" aria-hidden="true">
                        @if($savedDetails) ✅ @else 🎉 @endif
                    </span>
                </div>

                <h1 class="text-2xl md:text-3xl font-extrabold leading-tight">
                    @if($savedDetails)
                        {{ __('Your details have been saved') }}
                    @else
                        {{ __('Thank you for voting') }}
                    @endif
                </h1>

                <p class="text-white/90 mt-4 leading-7 text-sm md:text-base max-w-md mx-auto">
                    @if($savedDetails)
                        {{ __('We will reach out the moment the committee announces the winners. Thank you for taking part in :title.', ['title' => $campaign->localized('title')]) }}
                    @else
                        {{ __('Your vote in :title has been recorded. Results will be announced once the committee approves them.', ['title' => $campaign->localized('title')]) }}
                    @endif
                </p>

                {{-- Footer chip: campaign + announcement date if known --}}
                <div class="mt-7 inline-flex items-center gap-2 rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-4 py-2 text-xs">
                    <span class="text-white/70">{{ __('Campaign') }}:</span>
                    <strong>{{ $campaign->localized('title') }}</strong>
                    <span class="opacity-50 mx-1">·</span>
                    <strong>{{ $club->localized('name') }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
