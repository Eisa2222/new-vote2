@props(['input', 'player', 'accent' => 'brand'])

{{-- Single candidate pick card — photo, name, club, position.
     Accent color picks up from the parent section so Best Saudi
     (brand/green) and Best Foreign (amber) read as different awards
     without needing two separate templates. --}}
@php
    $accentClasses = [
        'brand' => [
            'check' => 'peer-checked:border-brand-600 peer-checked:bg-brand-50',
            'dot'   => 'peer-checked:bg-brand-600 peer-checked:border-brand-600',
            'badge' => 'bg-brand-100 text-brand-700',
        ],
        'amber' => [
            'check' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50',
            'dot'   => 'peer-checked:bg-amber-500 peer-checked:border-amber-500',
            'badge' => 'bg-amber-100 text-amber-700',
        ],
    ][$accent] ?? [];
    $photo = $player->photo_path
        ? \Illuminate\Support\Facades\Storage::url($player->photo_path)
        : null;
@endphp

<label class="relative flex items-center gap-3 rounded-2xl border-2 border-ink-200 bg-white p-3.5 cursor-pointer transition
              hover:border-{{ $accent }}-300 hover:shadow-md
              {{ $accentClasses['check'] ?? '' }}">
    <input type="{{ $input['type'] }}"
           name="{{ $input['name'] }}"
           value="{{ $input['value'] }}"
           required
           class="sr-only peer">

    {{-- Photo / avatar --}}
    @if($photo)
        <img src="{{ $photo }}" alt="{{ $player->localized('name') }}"
             class="w-14 h-14 rounded-full object-cover border-2 border-ink-200 flex-shrink-0">
    @else
        <div class="w-14 h-14 rounded-full bg-ink-100 flex items-center justify-center text-2xl flex-shrink-0">
            👤
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <div class="font-bold text-ink-900 truncate">{{ $player->localized('name') }}</div>
        <div class="text-xs text-ink-500 truncate mt-0.5">{{ $player->club?->localized('name') }}</div>
        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
            @if($player->position)
                <span class="inline-flex items-center rounded-full {{ $accentClasses['badge'] ?? 'bg-ink-100 text-ink-700' }} px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                    {{ $player->position->label() }}
                </span>
            @endif
            @if($player->jersey_number)
                <span class="inline-flex items-center rounded-full bg-ink-100 text-ink-700 px-2 py-0.5 text-[10px] font-semibold">
                    #{{ $player->jersey_number }}
                </span>
            @endif
        </div>
    </div>

    {{-- Selection indicator --}}
    <span class="w-6 h-6 rounded-full border-2 border-ink-300 flex-shrink-0 flex items-center justify-center transition
                 {{ $accentClasses['dot'] ?? '' }}
                 peer-checked:[&>svg]:opacity-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white opacity-0 transition" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
        </svg>
    </span>
</label>
