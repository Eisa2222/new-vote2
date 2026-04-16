@props([
    'campaign',
    'formation',
    'totalRequired',
    'voter' => null,
])

<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 text-white shadow-brand">
    {{-- Pitch texture backdrop --}}
    <div class="absolute inset-0 opacity-[0.08] pointer-events-none"
         style="background-image: radial-gradient(circle at 20% 30%, #ffffff 1px, transparent 2px),
                                  radial-gradient(circle at 80% 70%, #ffffff 1px, transparent 2px);
                background-size: 40px 40px;"></div>

    <div class="relative p-6 md:p-10">
        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-3 py-1 text-xs uppercase tracking-[0.25em] text-accent-400 font-semibold">
            {{ __('Team of the Season') }}
        </div>

        <h1 class="mt-4 text-2xl md:text-4xl font-extrabold leading-tight">
            {{ $campaign->localized('title') }}
        </h1>

        @if($campaign->localized('description'))
            <p class="mt-3 text-brand-100/90 max-w-2xl text-sm md:text-base leading-relaxed">
                {{ \Illuminate\Support\Str::limit($campaign->localized('description'), 180) }}
            </p>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3 min-w-[120px]">
                <div class="text-xs text-brand-100 uppercase tracking-wider">{{ __('Formation') }}</div>
                <div class="mt-1 text-2xl font-extrabold text-accent-400">
                    {{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}
                </div>
            </div>

            <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3 min-w-[120px]">
                <div class="text-xs text-brand-100 uppercase tracking-wider">{{ __('Picks required') }}</div>
                <div class="mt-1 text-2xl font-extrabold">
                    <span x-text="totalSelected()"></span> <span class="text-brand-200">/ {{ $totalRequired }}</span>
                </div>
            </div>

            @if($campaign->end_at)
                <div class="rounded-2xl bg-white/10 backdrop-blur px-4 py-3 min-w-[140px]">
                    <div class="text-xs text-brand-100 uppercase tracking-wider">{{ __('Closes') }}</div>
                    <div class="mt-1 text-sm font-bold">
                        {{ $campaign->end_at->translatedFormat('d M Y · H:i') }}
                    </div>
                </div>
            @endif
        </div>

        @if($voter)
            <div class="mt-5 inline-flex items-center gap-2 rounded-full bg-emerald-500/20 border border-emerald-300/40 px-3 py-1.5 text-xs font-semibold text-emerald-100">
                <span>&#10003;</span>
                <span>
                    {{ __('Verified as') }}
                    {{ $voter['method'] === 'national_id' ? __('National ID') : __('Mobile') }}:
                    <strong>{{ $voter['masked'] }}</strong>
                </span>
            </div>
        @endif
    </div>
</section>
