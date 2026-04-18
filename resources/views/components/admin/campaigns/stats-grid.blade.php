@props(['campaign'])

@php
    $progress = $campaign->max_voters
        ? min(100, (int) round(($campaign->votes_count / $campaign->max_voters) * 100))
        : null;
@endphp

<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow p-4">
        <div class="text-slate-500 text-sm">{{ __('Votes') }}</div>
        <div data-live-votes class="text-2xl font-bold text-emerald-600">{{ $campaign->votes_count }}</div>
        @if($campaign->max_voters)
            <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                <div data-live-bar class="h-full bg-emerald-500 rounded-full"
                     style="width: {{ $progress }}%"></div>
            </div>
        @endif
    </div>
    <div class="bg-white rounded-2xl shadow p-4">
        <div class="text-slate-500 text-sm">{{ __('Max voters') }}</div>
        <div class="text-2xl font-bold">{{ $campaign->max_voters ?? '∞' }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow p-4 col-span-2">
        <div class="text-slate-500 text-sm">{{ __('Public voting link') }}</div>
        <div class="mt-1 flex items-center gap-2">
            <input data-public-link type="text" readonly value="{{ url('/vote/'.$campaign->public_token) }}"
                   class="flex-1 border rounded px-2 py-1 text-sm">
            <button type="button"
                    onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerText='✓'"
                    class="btn-ghost text-sm">{{ __('Copy') }}</button>
        </div>
    </div>
</div>
