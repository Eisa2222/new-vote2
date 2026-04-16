@props([
    'campaign',
    'formation',
    'picks', // [ 'attack' => Player[], 'midfield' => Player[], 'defense' => Player[], 'goalkeeper' => Player[] ]
])

<section class="rounded-3xl overflow-hidden border border-brand-900/20 shadow-brand">
    <div class="relative bg-gradient-to-b from-brand-600 via-brand-700 to-brand-800 px-4 md:px-10 py-6 md:py-10">
        {{-- Pitch markings --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute inset-x-0 top-1/2 h-px bg-white/20"></div>
            <div class="absolute left-1/2 top-1/2 w-24 h-24 md:w-36 md:h-36 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/20"></div>
        </div>

        <div class="relative space-y-5 md:space-y-8">
            @foreach(['attack','midfield','defense','goalkeeper'] as $slot)
                @php $players = $picks[$slot] ?? []; @endphp
                <div class="flex items-center justify-center gap-3 md:gap-5 flex-wrap">
                    @foreach($players as $p)
                        @php
                            $name  = $p?->localized('name') ?? '—';
                            $club  = $p?->club?->localized('name') ?? '';
                            $photo = $p?->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null;
                        @endphp
                        <div class="text-center w-[80px] md:w-[100px]">
                            <div class="relative mx-auto w-[60px] h-[60px] md:w-[72px] md:h-[72px] rounded-full bg-white shadow-lg ring-4 ring-accent-400 overflow-hidden">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="{{ $name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xl font-bold text-brand-700">
                                        {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="mt-1.5 text-white text-[11px] md:text-xs font-bold truncate">{{ $name }}</div>
                            <div class="text-[10px] md:text-[11px] text-brand-100/80 truncate">{{ $club }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
