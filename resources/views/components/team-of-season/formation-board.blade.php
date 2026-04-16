@props([
    'formation',        // ['goalkeeper'=>1,'defense'=>4,'midfield'=>3,'attack'=>3]
    'candidatesBySlot', // ['attack' => Collection<VotingCategoryCandidate>, ...]
])

{{--
  A stylised pitch. Rows (from top to bottom in LTR): attack → midfield → defense → goalkeeper.
  Each slot is an Alpine-bound button: empty shows a numbered placeholder; filled shows the player.
  Tapping a slot opens the matching position panel via an Alpine store event.
--}}

<section class="rounded-3xl overflow-hidden border border-brand-900/20 shadow-brand">
    <div class="relative bg-gradient-to-b from-brand-600 via-brand-700 to-brand-800 px-4 md:px-10 py-6 md:py-10">

        {{-- Pitch lines --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute inset-x-0 top-1/2 h-px bg-white/20"></div>
            <div class="absolute left-1/2 top-1/2 w-24 h-24 md:w-36 md:h-36 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/20"></div>
            <div class="absolute inset-x-6 top-3 h-10 border-2 border-b-0 border-white/15 rounded-t-xl"></div>
            <div class="absolute inset-x-6 bottom-3 h-10 border-2 border-t-0 border-white/15 rounded-b-xl"></div>
        </div>

        <div class="relative space-y-5 md:space-y-8">
            {{-- Attack line --}}
            <x-team-of-season.formation-line
                slot="attack"
                :count="$formation['attack']"
                :label="__('Attack')" />

            {{-- Midfield line --}}
            <x-team-of-season.formation-line
                slot="midfield"
                :count="$formation['midfield']"
                :label="__('Midfield')" />

            {{-- Defense line --}}
            <x-team-of-season.formation-line
                slot="defense"
                :count="$formation['defense']"
                :label="__('Defense')" />

            {{-- Goalkeeper line --}}
            <x-team-of-season.formation-line
                slot="goalkeeper"
                :count="$formation['goalkeeper']"
                :label="__('Goalkeeper')" />
        </div>
    </div>
</section>
