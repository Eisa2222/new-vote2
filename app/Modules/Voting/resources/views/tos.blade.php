@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<?php
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation as F;
    $formation = F::fromCampaign($campaign) ?: F::default();
?>
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign->localized('title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: '{{ $locale === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif; }
        .candidate { cursor: pointer; transition: all .15s; }
        .candidate.selected { border-color: #115C42 !important; background: #ECF5EF; box-shadow: 0 0 0 2px #1F7A49; transform: scale(1.02); }
        .candidate.disabled { opacity: .35; cursor: not-allowed; }
        .pitch { background: linear-gradient(to bottom, #115C42, #0B3D2E); position: relative; }
        .pitch::before { content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(to right, rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 40px 40px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-brand-700 text-white p-8 md:p-10 shadow-2xl">
        <div class="grid md:grid-cols-2 gap-6 items-center">
            <div>
                <div class="text-brand-300 text-sm font-semibold">{{ __('Team of the Season') }}</div>
                <h1 class="text-3xl md:text-4xl font-bold mt-2 leading-tight">{{ $campaign->localized('title') }}</h1>
                @if($campaign->localized('description'))
                    <p class="text-slate-200 mt-3 leading-7">{{ $campaign->localized('description') }}</p>
                @endif
                <div class="inline-flex items-center gap-2 mt-4 bg-white/10 rounded-full px-4 py-1.5 text-sm">
                    <span>{{ __('Formation') }}:</span>
                    <strong class="text-amber-300 text-lg">{{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}</strong>
                </div>
            </div>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-attack">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Attack') }} / {{ $formation['attack'] }}</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-midfield">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Midfield') }} / {{ $formation['midfield'] }}</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-defense">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Defense') }} / {{ $formation['defense'] }}</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-goalkeeper">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Goalkeeper') }} / {{ $formation['goalkeeper'] }}</div>
                </div>
            </div>
        </div>
    </section>

    @isset($voter)
        <div class="rounded-2xl bg-brand-50 border border-brand-200 text-brand-700 px-4 py-3 text-sm flex items-center gap-2">
            <span>✓</span>
            <span>{{ __('Verified as') }} {{ $voter['method'] === 'national_id' ? __('National ID') : __('Mobile') }}: <strong>{{ $voter['masked'] }}</strong></span>
        </div>
    @endisset

    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <section class="pitch rounded-3xl overflow-hidden shadow-2xl p-6 md:p-10">
        <div class="relative z-10 space-y-8">
            @foreach($formation as $slot => $n)
                <?php $cat = $campaign->categories->firstWhere('position_slot', $slot); ?>
                @if($cat)
                    <div>
                        <div class="text-center text-white mb-4 font-semibold">
                            {{ __(ucfirst($slot)) }} — {{ $n }}
                        </div>
                        <div class="flex flex-wrap justify-center gap-3 md:gap-4" data-slot="{{ $slot }}" data-required="{{ $n }}">
                            @foreach($cat->candidates as $cand)
                                <?php
                                    $p = $cand->player;
                                    $name = $p?->localized('name');
                                    $club = $p?->club?->localized('name');
                                    $photo = $p?->photo_path;
                                ?>
                                <label class="candidate block w-36 rounded-2xl bg-white p-3 text-center border-2 border-transparent">
                                    <input type="checkbox" class="hidden cand-input"
                                           data-slot="{{ $slot }}"
                                           name="{{ $slot }}[]"
                                           value="{{ $cand->id }}">
                                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 overflow-hidden mb-2 flex items-center justify-center text-2xl">
                                        @if($photo)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            🧍
                                        @endif
                                    </div>
                                    <div class="font-semibold text-sm text-gray-900 truncate">{{ $name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $club }}</div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- The actual submit form — always visible, prominent submit button --}}
    <form method="post" action="{{ route('voting.submit', $campaign->public_token) }}" id="tosForm"
          class="rounded-3xl bg-white border-2 border-brand-300 p-6 shadow-brand">
        @csrf
        <div id="hiddenInputs"></div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-center md:text-start">
                <div class="text-2xl font-bold text-brand-700">
                    <span id="progressCount">0</span> / {{ array_sum($formation) }}
                </div>
                <div class="text-sm text-ink-500 mt-1">
                    {{ __('Pick :a attack, :m midfield, :d defense, 1 goalkeeper.', [
                        'a' => $formation['attack'], 'm' => $formation['midfield'], 'd' => $formation['defense'],
                    ]) }}
                </div>
                <div id="errorMsg" class="hidden mt-2 text-sm text-danger-600 font-semibold"></div>
            </div>
            <button type="submit" id="submitBtn"
                    class="w-full md:w-auto rounded-2xl bg-brand-600 hover:bg-brand-700 text-white px-10 py-4 font-bold text-lg shadow-brand">
                ✓ {{ __('Submit my Team of the Season') }}
            </button>
        </div>
    </form>
</div>

<script>
    const REQUIRED = {!! json_encode($formation) !!};
    const selected = { attack: new Set(), midfield: new Set(), defense: new Set(), goalkeeper: new Set() };

    // Track insertion order per slot so we can kick out the oldest pick
    // when the voter exceeds the line limit (swap semantics).
    const order = { attack: [], midfield: [], defense: [], goalkeeper: [] };

    document.querySelectorAll('.candidate').forEach(label => {
        const input = label.querySelector('.cand-input');
        const slot = input.dataset.slot;
        label.addEventListener('click', (e) => {
            if (e.target === input) return;
            e.preventDefault();

            if (input.checked) {
                // Deselect
                selected[slot].delete(input.value);
                order[slot] = order[slot].filter(v => v !== input.value);
                input.checked = false;
                label.classList.remove('selected');
            } else {
                // Select; if line is full, swap out the oldest pick.
                if (selected[slot].size >= REQUIRED[slot]) {
                    const oldest = order[slot].shift();
                    if (oldest) {
                        selected[slot].delete(oldest);
                        const oldInput = document.querySelector(`.cand-input[data-slot="${slot}"][value="${oldest}"]`);
                        if (oldInput) {
                            oldInput.checked = false;
                            oldInput.closest('.candidate')?.classList.remove('selected');
                        }
                    }
                }
                selected[slot].add(input.value);
                order[slot].push(input.value);
                input.checked = true;
                label.classList.add('selected');
            }
            update();
        });
    });

    function update() {
        let total = 0;
        ['attack','midfield','defense','goalkeeper'].forEach(slot => {
            document.getElementById('count-'+slot).textContent = selected[slot].size;
            total += selected[slot].size;
        });
        const pc = document.getElementById('progressCount');
        if (pc) pc.textContent = total;
    }

    // Form-submit handler: validates picks, builds hidden inputs, submits.
    document.getElementById('tosForm').addEventListener('submit', (e) => {
        const errorEl = document.getElementById('errorMsg');
        const missing = [];
        ['attack','midfield','defense','goalkeeper'].forEach(slot => {
            const got = selected[slot].size;
            if (got !== REQUIRED[slot]) {
                missing.push(`${slot}: ${got}/${REQUIRED[slot]}`);
            }
        });
        if (missing.length) {
            e.preventDefault();
            errorEl.textContent = '⚠ ' + '{{ __('Selection incomplete') }}: ' + missing.join(' · ');
            errorEl.classList.remove('hidden');
            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Build hidden inputs from selections
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        Object.entries(selected).forEach(([slot, ids]) => {
            ids.forEach(id => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = `${slot}[]`; i.value = id;
                container.appendChild(i);
            });
        });
        // Form submits naturally; disable button to prevent double-submit
        document.getElementById('submitBtn').textContent = '⏳ {{ __('Submitting...') }}';
        document.getElementById('submitBtn').disabled = true;
    });

    update();
</script>
</body>
</html>
