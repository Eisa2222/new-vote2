@props(['counts'])

@php
    $labels = [
        'draft'            => __('Draft'),
        'pending_approval' => __('Pending'),
        'published'        => __('Published'),
        'active'           => __('Active'),
        'closed'           => __('Closed'),
    ];
@endphp

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 w-full lg:w-auto">
    @foreach($labels as $key => $label)
        <div class="rounded-2xl bg-white border border-gray-200 p-4">
            <div class="text-xs text-gray-500">{{ $label }}</div>
            <div class="text-2xl font-bold mt-1 {{ $key === 'pending_approval' && ($counts[$key] ?? 0) > 0 ? 'text-amber-700' : '' }}">
                {{ $counts[$key] ?? 0 }}
            </div>
        </div>
    @endforeach
</div>
