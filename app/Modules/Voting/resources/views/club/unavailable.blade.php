@extends('voting::club._layout')
@section('title', __('Voting unavailable'))

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Hero panel — keeps the same voting-flow visual language so
         the page feels "part of the product", not a blank fallback. --}}
    <div class="relative rounded-3xl overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700"></div>
        <div class="absolute inset-0 opacity-15"
             style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative p-8 md:p-10 text-center text-white">
            <div class="inline-flex w-20 h-20 rounded-3xl bg-white/15 backdrop-blur items-center justify-center text-5xl mb-4">
                ⏳
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold">{{ __('Voting is not available right now') }}</h1>
            <p class="text-white/90 mt-3 leading-7 max-w-lg mx-auto">{{ $reason }}</p>

            @if($campaign)
                <div class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-white/10 backdrop-blur border border-white/20 px-4 py-2 text-sm">
                    <span class="text-xs uppercase tracking-widest text-white/70">{{ __('Campaign') }}:</span>
                    <strong>{{ $campaign->localized('title') }}</strong>
                    @if($club)
                        <span class="text-white/70">·</span>
                        <span>{{ $club->localized('name') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Voter identity card — rendered only when we know who the voter
         is (i.e. they were signed in when the gate check kicked in).
         Re-uses the shared _partials.voter-card partial so the same
         profile chip appears on ballot / success / unavailable. --}}
    @isset($voter)
        @if($voter)
            @include('voting::club._partials.voter-card', ['voter' => $voter, 'club' => $club])
        @endif
    @endisset

    {{-- Next step --}}
    <div class="card text-center space-y-3">
        <p class="text-sm text-ink-500 leading-7">
            {{ __('Results will be announced by the committee once the campaign is set up and ready. Check back soon or contact the organisers.') }}
        </p>
        <div class="flex items-center justify-center gap-2 flex-wrap">
            <a href="{{ route('public.campaigns') }}" class="btn-save">
                <span aria-hidden="true">🏠</span>
                <span>{{ __('Back to campaigns') }}</span>
            </a>
            <a href="mailto:contact@sfpa.sa" class="btn-ghost">
                <span aria-hidden="true">✉️</span>
                <span>{{ __('Contact organisers') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection
