@props(['counts'])

@php
    $labels = [
        'draft' => __('Draft'),
        'pending_approval' => __('Pending'),
        'published' => __('Published'),
        'active' => __('Active'),
        'closed' => __('Closed'),
    ];
@endphp

{{-- الإحصائيات — صف كامل --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 w-full">
    @foreach ($labels as $key => $label)
        <div class="rounded-xl bg-white border border-ink-200 px-4 py-3 text-center">
            <div class="text-[11px] text-ink-500">{{ $label }}</div>
            <div
                class="text-xl font-semibold mt-0.5 {{ $key === 'pending_approval' && ($counts[$key] ?? 0) > 0 ? 'text-amber-600' : 'text-ink-900' }}">
                {{ $counts[$key] ?? 0 }}
            </div>
        </div>
    @endforeach
</div>

{{-- الزر — صف منفصل --}}
