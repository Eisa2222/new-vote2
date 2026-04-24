@props([
    // Semantic status key. Supported out of the box:
    //   active · inactive · pending · published · announced ·
    //   rejected · draft · closed · archived · full · disabled
    'status' => 'inactive',
    // Optional explicit label; defaults to humanised $status.
    'label' => null,
    // Show a coloured dot before the label (used on users + clubs
    // indexes). Defaults off so other call sites stay compact.
    'dot'   => false,
])

@php
    // Central enum → (class, default label, dot colour) map. This is
    // the SINGLE SOURCE OF TRUTH for status chips — previously
    // dashboard, categories and results each had their own drifting
    // copies. Adding a status anywhere in the app? Add it here.
    $map = [
        'active'     => ['chip chip-active',     __('Active'),     'bg-green-500'],
        'inactive'   => ['chip chip-inactive',   __('Inactive'),   'bg-ink-400'],
        'disabled'   => ['chip chip-inactive',   __('Disabled'),   'bg-ink-400'],
        'archived'   => ['chip chip-inactive',   __('Archived'),   'bg-ink-400'],
        'closed'     => ['chip chip-inactive',   __('Closed'),     'bg-ink-400'],
        'draft'      => ['chip chip-pending',    __('Draft'),      'bg-amber-500'],
        'pending'    => ['chip chip-pending',    __('Pending'),    'bg-amber-500'],
        'full'       => ['chip chip-pending',    __('Full'),       'bg-amber-500'],
        'rejected'   => ['chip chip-rejected',   __('Rejected'),   'bg-rose-500'],
        'published'  => ['chip chip-published',  __('Published'),  'bg-blue-500'],
        'announced'  => ['chip chip-announced',  __('Announced'),  'bg-emerald-500'],
    ];

    [$class, $defaultLabel, $dotClass] = $map[$status] ?? $map['inactive'];
    $finalLabel = $label ?? $defaultLabel;
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    @if($dot)
        <span class="chip-dot {{ $dotClass }}"></span>
    @endif
    <span>{{ $finalLabel }}</span>
</span>
