@props(['slot', 'count', 'label'])

{{--
  One line in the formation. Renders N placeholder/filled slot buttons, evenly spaced.
  Alpine state comes from the root x-data: `selected[slot]` is an ordered array of candidate IDs.
--}}

<div class="flex items-center justify-center gap-2 md:gap-4 flex-wrap">
    <template x-for="index in {{ $count }}" :key="'{{ $slot }}-'+index">
        <button type="button"
                class="group relative w-[72px] md:w-[96px] text-center focus:outline-none"
                @click="openPanel('{{ $slot }}')"
                :aria-label="(selected['{{ $slot }}'][index-1] ? candidateName(selected['{{ $slot }}'][index-1]) : '{{ __('Empty') }} {{ $label }} ' + index)">

            {{-- Filled slot --}}
            <template x-if="selected['{{ $slot }}'][index-1]">
                <span class="block">
                    <span class="relative mx-auto block w-[60px] h-[60px] md:w-[72px] md:h-[72px] rounded-full bg-white shadow-lg ring-4 ring-accent-400 overflow-hidden transition group-hover:scale-105">
                        <img :src="candidatePhoto(selected['{{ $slot }}'][index-1])"
                             :alt="candidateName(selected['{{ $slot }}'][index-1])"
                             class="w-full h-full object-cover">
                    </span>
                    <span class="block mt-1.5 text-white text-[11px] md:text-xs font-bold truncate max-w-[80px] md:max-w-[100px] mx-auto"
                          x-text="candidateName(selected['{{ $slot }}'][index-1])"></span>
                    <span class="block text-[10px] md:text-[11px] text-brand-100/80 truncate max-w-[80px] md:max-w-[100px] mx-auto"
                          x-text="candidateClub(selected['{{ $slot }}'][index-1])"></span>
                </span>
            </template>

            {{-- Empty slot --}}
            <template x-if="!selected['{{ $slot }}'][index-1]">
                <span class="block">
                    <span class="mx-auto flex items-center justify-center w-[60px] h-[60px] md:w-[72px] md:h-[72px] rounded-full border-2 border-dashed border-white/50 bg-white/5 backdrop-blur-sm text-white/70 group-hover:border-accent-400 group-hover:text-accent-400 transition">
                        <span class="text-lg md:text-xl font-bold">+</span>
                    </span>
                    <span class="block mt-1.5 text-white/80 text-[10px] md:text-[11px] font-semibold">{{ $label }}</span>
                    <span class="block text-[10px] text-brand-100/60" x-text="index + '/{{ $count }}'"></span>
                </span>
            </template>
        </button>
    </template>
</div>
