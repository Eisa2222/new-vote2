@props(['slot', 'candidate'])

@php
    $p      = $candidate->player;
    $name   = $p?->localized('name') ?? '—';
    $club   = $p?->club?->localized('name') ?? '';
    $photo  = $p?->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null;
    $jersey = $p?->jersey_number;
@endphp

<button type="button"
        @click="toggle('{{ $slot }}', {{ $candidate->id }})"
        :class="isSelected('{{ $slot }}', {{ $candidate->id }})
                ? 'border-brand-600 bg-brand-50 ring-2 ring-brand-500'
                : 'border-ink-200 bg-white hover:border-brand-400 hover:shadow-sm'"
        class="group relative flex items-center gap-3 rounded-2xl p-2.5 text-start border-2 transition focus:outline-none focus:ring-2 focus:ring-brand-500">
    {{-- Selected checkmark --}}
    <span x-show="isSelected('{{ $slot }}', {{ $candidate->id }})"
          class="absolute top-1.5 end-1.5 w-5 h-5 rounded-full bg-brand-600 text-white text-[10px] font-bold flex items-center justify-center shadow">
        &#10003;
    </span>

    {{-- Avatar --}}
    @if($photo)
        <img src="{{ $photo }}" alt="{{ $name }}"
             class="w-11 h-11 md:w-12 md:h-12 rounded-full object-cover flex-shrink-0 border border-ink-200">
    @else
        <div class="w-11 h-11 md:w-12 md:h-12 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-lg flex-shrink-0 border border-brand-200">
            {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <div class="font-semibold text-sm text-ink-900 truncate">{{ $name }}</div>
        <div class="text-xs text-ink-500 truncate">
            @if($club) {{ $club }} @endif
            @if($jersey)
                <span class="inline-block ms-1 rounded bg-ink-100 text-ink-600 px-1.5 py-0.5 text-[10px] font-bold">#{{ $jersey }}</span>
            @endif
        </div>
    </div>
</button>
