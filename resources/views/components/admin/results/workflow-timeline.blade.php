@props(['step' => 0])

{{-- Three-step progress strip: Calculate → Approve → Announce.
     `step` is 0 (nothing done) through 3 (announced). --}}
<div class="rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm mb-5">
    <h2 class="text-sm font-bold text-ink-700 uppercase tracking-wide mb-5">
        {{ __('Results workflow') }}
    </h2>

    @php
        $steps = [
            ['label' => __('Calculate'), 'desc' => __('Tally votes'),        'done' => $step >= 1],
            ['label' => __('Approve'),   'desc' => __('Committee sign-off'), 'done' => $step >= 2],
            ['label' => __('Announce'),  'desc' => __('Publish publicly'),   'done' => $step >= 3],
        ];
    @endphp

    <div class="flex items-center gap-2 md:gap-4">
        @foreach($steps as $i => $item)
            <div class="flex-1">
                <div class="flex items-center gap-2 md:gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold flex-shrink-0
                                {{ $item['done'] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                        {{ $item['done'] ? '✓' : $i + 1 }}
                    </div>
                    @if($i < count($steps) - 1)
                        <div class="flex-1 h-1 rounded-full {{ ($item['done'] && $step > $i + 1) ? 'bg-emerald-500' : 'bg-slate-100' }}"></div>
                    @endif
                </div>
                <div class="mt-2">
                    <div class="font-semibold text-sm md:text-base {{ $item['done'] ? 'text-emerald-700' : 'text-ink-500' }}">
                        {{ $item['label'] }}
                    </div>
                    <div class="text-xs text-ink-500">{{ $item['desc'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
