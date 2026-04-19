@props([
    'size'  => 'md',        // sm (28px) · md (44px) · lg (64px)
    'class' => '',
    'showName' => false,
])
@php
    use App\Modules\Shared\Support\Branding;
    $url      = Branding::logoUrl();
    $initials = Branding::initials();
    $dims = match($size) {
        'sm' => 'w-7 h-7 text-[11px]',
        'lg' => 'w-16 h-16 text-xl',
        default => 'w-11 h-11 text-base',
    };
@endphp

{{-- If a logo image is configured, use it; otherwise fall back to a
     branded square with the wordmark initials. Kept in one component
     so every header/layout renders identically. --}}
<div {{ $attributes->merge(['class' => "inline-flex items-center gap-3 $class"]) }}>
    @if($url)
        <img src="{{ $url }}" alt="{{ Branding::name() }}"
             class="{{ $dims }} rounded-xl object-contain bg-white/10 p-1">
    @else
        <div class="{{ $dims }} rounded-xl bg-brand-700 text-white flex items-center justify-center font-bold tracking-wide">
            {{ $initials }}
        </div>
    @endif
    @if($showName)
        <span class="font-bold text-sm leading-tight">{{ Branding::name() }}</span>
    @endif
</div>
