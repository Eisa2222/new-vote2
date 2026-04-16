@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<?php
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation as F;

    $formation = F::fromCampaign($campaign) ?: F::default();
    $totalRequired = array_sum($formation);

    // Build a per-slot candidate collection keyed by slot, and a JS-ready index
    // with the data Alpine needs to render filled slots on the pitch.
    $candidatesBySlot = [];
    $lookup = [];
    foreach ($campaign->categories as $cat) {
        if (! array_key_exists($cat->position_slot, $formation)) continue;
        $candidatesBySlot[$cat->position_slot] = $cat->candidates;
        foreach ($cat->candidates as $cand) {
            $p = $cand->player;
            $lookup[$cand->id] = [
                'name'  => $p?->localized('name') ?? '—',
                'club'  => $p?->club?->localized('name') ?? '',
                'photo' => $p?->photo_path
                    ? \Illuminate\Support\Facades\Storage::url($p->photo_path)
                    : 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 72 72"><rect width="72" height="72" fill="#ECF5EF"/><text x="50%" y="55%" text-anchor="middle" font-size="32" fill="#115C42" font-family="sans-serif">'.htmlspecialchars(mb_substr($p?->localized('name') ?? '?', 0, 1), ENT_QUOTES).'</text></svg>'),
            ];
        }
    }
?>
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign->localized('title') }}</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style> [x-cloak] { display: none !important; } </style>
</head>
<body class="bg-ink-50 text-ink-900 min-h-screen">

<div x-data="tosBoard({
        formation: {{ json_encode($formation) }},
        totalRequired: {{ $totalRequired }},
        candidates: {{ json_encode($lookup) }}
    })"
     class="max-w-6xl mx-auto px-3 md:px-6 py-6 space-y-6 pb-32">

    {{-- Hero --}}
    <x-team-of-season.campaign-header
        :campaign="$campaign"
        :formation="$formation"
        :totalRequired="$totalRequired"
        :voter="$voter ?? null" />

    {{-- Server-side validation errors --}}
    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    {{-- Formation board --}}
    <x-team-of-season.formation-board
        :formation="$formation"
        :candidatesBySlot="$candidatesBySlot" />

    {{-- Live selection counter --}}
    <x-team-of-season.selection-counter
        :formation="$formation"
        :totalRequired="$totalRequired" />

    {{-- Panel tabs - let voter filter which panel to show --}}
    <div class="flex flex-wrap gap-2">
        <button type="button" @click="activePanel = 'all'"
                :class="activePanel === 'all' ? 'bg-brand-600 text-white' : 'bg-white text-ink-700 border border-ink-200 hover:bg-ink-50'"
                class="rounded-full px-4 py-2 text-sm font-semibold transition">
            {{ __('All lines') }}
        </button>
        @foreach(array_keys($formation) as $slot)
            <button type="button" @click="activePanel = '{{ $slot }}'"
                    :class="activePanel === '{{ $slot }}' ? 'bg-brand-600 text-white' : 'bg-white text-ink-700 border border-ink-200 hover:bg-ink-50'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">
                {{ __(ucfirst($slot)) }}
                <span class="ms-1 rounded-full bg-white/30 px-2 py-0.5 text-xs"
                      :class="activePanel === '{{ $slot }}' ? 'bg-white/30' : 'bg-ink-100'">
                    <span x-text="selected['{{ $slot }}'].length"></span>/{{ $formation[$slot] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Submit form wraps the panels; hidden inputs are injected on submit --}}
    <form method="post" action="{{ route('voting.submit', $campaign->public_token) }}" id="tosForm"
          @submit.prevent="submitVote($event)">
        @csrf
        <div id="hiddenInputs"></div>

        <div class="space-y-5">
            @foreach($formation as $slot => $n)
                <x-team-of-season.position-panel
                    :slot="$slot"
                    :label="__(ucfirst($slot))"
                    :required="$n"
                    :candidates="$candidatesBySlot[$slot] ?? collect()" />
            @endforeach
        </div>

        <x-team-of-season.submit-bar :totalRequired="$totalRequired" />
    </form>
</div>

<script>
function tosBoard({ formation, totalRequired, candidates }) {
    return {
        formation,
        totalRequired,
        candidates,
        selected: { attack: [], midfield: [], defense: [], goalkeeper: [] },
        activePanel: 'all',
        submitting: false,

        /** Open the matching panel when a formation slot is clicked. */
        openPanel(slot) {
            this.activePanel = slot;
            this.$nextTick(() => {
                const el = document.querySelector(`[data-slot-panel="${slot}"]`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },

        isSelected(slot, id) { return this.selected[slot].includes(id); },

        /** Toggle with LRU-swap when line is full. */
        toggle(slot, id) {
            const arr = this.selected[slot];
            const idx = arr.indexOf(id);
            if (idx !== -1) {
                arr.splice(idx, 1);
                return;
            }
            if (arr.length >= this.formation[slot]) arr.shift();
            arr.push(id);
        },

        candidateName(id)  { return this.candidates[id]?.name  || ''; },
        candidateClub(id)  { return this.candidates[id]?.club  || ''; },
        candidatePhoto(id) { return this.candidates[id]?.photo || ''; },

        totalSelected() {
            return Object.values(this.selected).reduce((a, ids) => a + ids.length, 0);
        },
        lineOk(slot) { return this.selected[slot].length === this.formation[slot]; },
        canSubmit()  { return this.totalSelected() === this.totalRequired; },

        missingSummary() {
            const missing = [];
            for (const [slot, n] of Object.entries(this.formation)) {
                const got = this.selected[slot].length;
                if (got !== n) {
                    missing.push(`${this.labelFor(slot)}: ${got}/${n}`);
                }
            }
            return missing.length ? '{{ __("Incomplete") }} — ' + missing.join(' · ') : '';
        },

        labelFor(slot) {
            return ({
                attack:     '{{ __("Attack") }}',
                midfield:   '{{ __("Midfield") }}',
                defense:    '{{ __("Defense") }}',
                goalkeeper: '{{ __("Goalkeeper") }}',
            })[slot] || slot;
        },

        submitVote(e) {
            if (!this.canSubmit() || this.submitting) return;
            this.submitting = true;
            const form = e.target;
            const holder = form.querySelector('#hiddenInputs');
            holder.innerHTML = '';
            for (const [slot, ids] of Object.entries(this.selected)) {
                ids.forEach(v => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${slot}[]`;
                    input.value = v;
                    holder.appendChild(input);
                });
            }
            form.submit();
        },
    };
}
</script>
</body>
</html>
