@props(['formation', 'totalRequired'])

{{-- A compact, always-visible summary. Turns green per-line when satisfied. --}}
<div class="rounded-2xl bg-white border border-ink-200 shadow-sm overflow-hidden">
    <div class="grid grid-cols-2 md:grid-cols-5 divide-x divide-ink-200 rtl:divide-x-reverse">
        @foreach($formation as $slot => $n)
            @php
                $label = __(ucfirst($slot));
            @endphp
            <div class="p-3 md:p-4 text-center transition"
                 :class="lineOk('{{ $slot }}') ? 'bg-emerald-50 text-emerald-800' : 'bg-white text-ink-800'">
                <div class="text-[10px] md:text-xs font-semibold uppercase tracking-wide">{{ $label }}</div>
                <div class="mt-1 text-lg md:text-xl font-extrabold">
                    <span x-text="selected['{{ $slot }}'].length"></span>
                    <span class="text-ink-500">/{{ $n }}</span>
                </div>
            </div>
        @endforeach
        <div class="p-3 md:p-4 text-center bg-brand-600 text-white md:col-span-1 col-span-2">
            <div class="text-[10px] md:text-xs font-semibold uppercase tracking-wide">{{ __('Total') }}</div>
            <div class="mt-1 text-lg md:text-xl font-extrabold">
                <span x-text="totalSelected()"></span>
                <span class="text-brand-200">/{{ $totalRequired }}</span>
            </div>
        </div>
    </div>
</div>
