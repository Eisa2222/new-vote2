@props([
    'slot',
    'label',
    'required',
    'candidates', // Collection<VotingCategoryCandidate>
])

<section class="rounded-3xl bg-white border border-ink-200 shadow-sm overflow-hidden"
         x-show="activePanel === '{{ $slot }}' || activePanel === 'all'"
         x-cloak>
    <header class="flex items-center justify-between gap-3 px-5 py-4 bg-brand-50 border-b border-brand-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                {{ mb_strtoupper(mb_substr($label, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-lg font-bold text-ink-900">{{ $label }}</h2>
                <div class="text-xs text-ink-500">
                    {{ __(':n candidates available', ['n' => $candidates->count()]) }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-bold"
                  :class="lineOk('{{ $slot }}') ? 'bg-emerald-600 text-white' : 'bg-ink-100 text-ink-700'">
                <span x-text="selected['{{ $slot }}'].length"></span>/{{ $required }}
            </span>
        </div>
    </header>

    <div class="p-3 md:p-4">
        @if($candidates->isEmpty())
            <div class="p-8 text-center text-ink-500 text-sm">
                {{ __('No candidates yet for this line.') }}
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 md:gap-3 max-h-[460px] overflow-y-auto">
                @foreach($candidates as $cand)
                    <x-team-of-season.player-card :slot="$slot" :candidate="$cand" />
                @endforeach
            </div>
        @endif
    </div>
</section>
