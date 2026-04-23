@props(['voter', 'club' => null, 'variant' => 'solid'])

{{-- Voter identity card.

     Design-pass notes (2026-04):
       • Arabic no longer gets `uppercase` + wide letter-spacing — those
         are Latin typographic conventions and make small Arabic labels
         look broken ("المصوّت" rendered as stretched glyphs).
       • Flag chip replaced by a coloured badge with a Saudi / globe
         glyph. Windows often renders 🇸🇦 as the fallback "SA" regional
         indicators; this avoids the ugly fallback entirely.
       • Tighter three-row hierarchy: role label · name · meta-pills.
--}}
@php
    $photo    = $voter?->photo_path ? \Illuminate\Support\Facades\Storage::url($voter->photo_path) : null;
    $clubName = $club?->localized('name') ?? $voter?->club?->localized('name');
    $isGlass  = $variant === 'glass';
    $isAr     = app()->getLocale() === 'ar';
    // Latin-only typographic tweak — disabled in Arabic.
    $eyebrow  = $isAr
        ? 'text-[11px] font-semibold'
        : 'text-[10px] uppercase tracking-[0.2em] font-semibold';
    $pillBase = $isAr
        ? 'text-[11px] font-bold'
        : 'text-[10px] uppercase tracking-wider font-bold';
@endphp

<div class="relative rounded-2xl overflow-hidden border
            {{ $isGlass
                ? 'bg-white/10 border-white/20 backdrop-blur text-white'
                : 'bg-white border-ink-200 shadow-sm text-ink-900' }}">
    <div class="p-4 md:p-5 flex items-center gap-4">
        {{-- Avatar --}}
        @if($photo)
            <img src="{{ $photo }}" alt="{{ $voter->localized('name') }}"
                 class="w-16 h-16 rounded-2xl object-cover border-2
                        {{ $isGlass ? 'border-white/40' : 'border-ink-200' }} flex-shrink-0">
        @else
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-extrabold flex-shrink-0
                        {{ $isGlass ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-700' }}">
                {{ mb_strtoupper(mb_substr($voter?->localized('name') ?? '?', 0, 1)) }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <div class="{{ $eyebrow }} {{ $isGlass ? 'text-white/70' : 'text-ink-500' }}">
                {{ __('Voter') }}
            </div>
            <div class="font-extrabold text-lg md:text-xl leading-tight truncate mt-0.5">
                {{ $voter?->localized('name') }}
            </div>
            <div class="text-sm mt-1 truncate {{ $isGlass ? 'text-white/80' : 'text-ink-500' }}">
                {{ $clubName }}
                @if($voter?->jersey_number)
                    <span class="mx-1 opacity-60">·</span>
                    <span class="font-mono font-bold">#{{ $voter->jersey_number }}</span>
                @endif
            </div>

            {{-- Meta pills — position + nationality + captain star. Each
                 pill is self-contained and wraps on narrow screens. --}}
            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                @if($voter?->position)
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 {{ $pillBase }}
                                 {{ $isGlass ? 'bg-white/20 text-white' : 'bg-ink-100 text-ink-700' }}">
                        {{ $voter->position->label() }}
                    </span>
                @endif
                @if($voter?->nationality)
                    @php
                        $isSaudi = $voter->nationality->value === 'saudi';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full ps-1.5 pe-2.5 py-0.5 {{ $pillBase }}
                                 {{ $isGlass
                                    ? ($isSaudi ? 'bg-emerald-500/30 text-white' : 'bg-amber-500/30 text-white')
                                    : ($isSaudi ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') }}">
                        {{-- Own flag/globe chip — avoids Windows' ugly
                             "SA" regional-indicator fallback. --}}
                        <span class="inline-flex w-3.5 h-3.5 rounded-full items-center justify-center text-[9px]
                                     {{ $isSaudi ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white' }}">
                            {{ $isSaudi ? '★' : '✈' }}
                        </span>
                        <span>{{ $voter->nationality->label() }}</span>
                    </span>
                @endif
                @if($voter?->is_captain)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 {{ $pillBase }}
                                 {{ $isGlass ? 'bg-yellow-400/30 text-white' : 'bg-amber-100 text-amber-800' }}">
                        <span aria-hidden="true">⭐</span>
                        <span>{{ __('Captain') }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Verified check on the end — reinforces "you are signed in" --}}
        <div class="hidden sm:flex w-10 h-10 rounded-full items-center justify-center flex-shrink-0
                    {{ $isGlass ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-700' }}"
             title="{{ __('Verified voter') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</div>
