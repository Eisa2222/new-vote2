@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<?php
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation as F;
    $formation = F::fromCampaign($campaign) ?: F::default();
    $totalRequired = array_sum($formation);
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
        .candidate.selected { border-color: #115C42 !important; background: #ECF5EF; box-shadow: 0 0 0 2px #1F7A49; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<form method="post" action="{{ route('voting.submit', $campaign->public_token) }}" id="tosForm">
    @csrf
    <div id="hiddenInputs"></div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

        <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-emerald-800 text-white p-6 md:p-8 shadow-2xl">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="text-emerald-300 text-xs font-semibold uppercase tracking-wide">{{ __('Team of the Season') }}</div>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1.5 leading-tight">{{ $campaign->localized('title') }}</h1>
                    <div class="inline-flex items-center gap-2 mt-3 bg-white/10 rounded-full px-3 py-1 text-sm">
                        <span class="text-amber-300 font-bold">{{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}</span>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap items-center">
                    <div class="bg-white/10 rounded-xl px-4 py-2 text-center">
                        <div class="text-2xl font-bold"><span id="progressTop">0</span> / {{ $totalRequired }}</div>
                        <div class="text-xs text-emerald-200">{{ __('picks complete') }}</div>
                    </div>
                </div>
            </div>
        </section>

        @isset($voter)
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm flex items-center gap-2">
                <span>✓</span>
                <span>{{ __('Verified as') }} {{ $voter['method'] === 'national_id' ? __('National ID') : __('Mobile') }}: <strong>{{ $voter['masked'] }}</strong></span>
            </div>
        @endisset

        @if($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- TOP SUBMIT BUTTON — visible immediately --}}
        <div class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 transition shadow-lg">
            <button type="submit" id="submitBtnTop"
                    class="w-full text-white px-6 py-4 font-bold text-lg flex items-center justify-center gap-3">
                <span>✓ {{ __('Submit my Team of the Season') }}</span>
                <span class="text-sm bg-white/20 rounded-full px-3 py-0.5">
                    <span id="progressInline">0</span>/{{ $totalRequired }}
                </span>
            </button>
        </div>
        <div id="errorMsgTop" class="hidden rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-700 p-4 font-semibold"></div>

        {{-- Per-line picker — clean cards, scrollable if many candidates --}}
        @foreach($formation as $slot => $n)
            <?php $cat = $campaign->categories->firstWhere('position_slot', $slot); ?>
            @if($cat)
                <section class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 bg-emerald-50 border-b border-emerald-100">
                        <h2 class="font-bold text-gray-900">
                            {{ __(ucfirst($slot)) }}
                        </h2>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="bg-emerald-600 text-white rounded-full px-3 py-0.5 font-bold">
                                <span class="line-counter-{{ $slot }}">0</span>/{{ $n }}
                            </span>
                            <span class="text-gray-500 text-xs">({{ $cat->candidates->count() }} {{ __('candidates') }})</span>
                        </div>
                    </div>

                    <div class="p-3 max-h-[420px] overflow-y-auto">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2" data-slot="{{ $slot }}">
                            @foreach($cat->candidates as $cand)
                                <?php
                                    $p = $cand->player;
                                    $name = $p?->localized('name');
                                    $club = $p?->club?->localized('name');
                                    $photo = $p?->photo_path;
                                ?>
                                <label class="candidate flex items-center gap-2 rounded-xl bg-white p-2 border-2 border-gray-200">
                                    <input type="checkbox" class="hidden cand-input" data-slot="{{ $slot }}" value="{{ $cand->id }}">
                                    @if($photo)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0" alt="">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-lg flex-shrink-0">🧍</div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-xs text-gray-900 truncate">{{ $name }}</div>
                                        <div class="text-xs text-gray-500 truncate">{{ $club }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endforeach

        {{-- BOTTOM SUBMIT BUTTON — large, prominent --}}
        <div class="rounded-3xl bg-white border-2 border-emerald-500 p-6 shadow-xl">
            <div class="text-center mb-4">
                <div class="text-3xl font-bold text-emerald-700">
                    <span id="progressBottom">0</span> / {{ $totalRequired }}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                    {{ __('Pick :a attack, :m midfield, :d defense, 1 goalkeeper.', [
                        'a' => $formation['attack'], 'm' => $formation['midfield'], 'd' => $formation['defense'],
                    ]) }}
                </div>
            </div>
            <div id="errorMsgBottom" class="hidden mb-3 rounded-xl bg-rose-50 border border-rose-300 text-rose-700 p-3 text-sm font-semibold"></div>
            <button type="submit" id="submitBtnBottom"
                    class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 font-bold text-xl shadow-lg">
                ✓ {{ __('Submit my Team of the Season') }}
            </button>
        </div>
    </div>
</form>

<script>
    const REQUIRED = {!! json_encode($formation) !!};
    const TOTAL = {{ $totalRequired }};
    const selected = { attack: new Set(), midfield: new Set(), defense: new Set(), goalkeeper: new Set() };
    const order = { attack: [], midfield: [], defense: [], goalkeeper: [] };

    document.querySelectorAll('.candidate').forEach(label => {
        const input = label.querySelector('.cand-input');
        const slot = input.dataset.slot;
        label.addEventListener('click', (e) => {
            if (e.target === input) return;
            e.preventDefault();
            if (input.checked) {
                selected[slot].delete(input.value);
                order[slot] = order[slot].filter(v => v !== input.value);
                input.checked = false;
                label.classList.remove('selected');
            } else {
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
            document.querySelectorAll('.line-counter-'+slot).forEach(e => e.textContent = selected[slot].size);
            total += selected[slot].size;
        });
        document.getElementById('progressTop').textContent = total;
        document.getElementById('progressInline').textContent = total;
        document.getElementById('progressBottom').textContent = total;
    }

    document.getElementById('tosForm').addEventListener('submit', (e) => {
        const missing = [];
        ['attack','midfield','defense','goalkeeper'].forEach(slot => {
            const got = selected[slot].size;
            if (got !== REQUIRED[slot]) missing.push(`${slot} ${got}/${REQUIRED[slot]}`);
        });
        if (missing.length) {
            e.preventDefault();
            const msg = '⚠ {{ __('Selection incomplete') }}: ' + missing.join(' · ');
            ['errorMsgTop','errorMsgBottom'].forEach(id => {
                const el = document.getElementById(id);
                el.textContent = msg;
                el.classList.remove('hidden');
            });
            document.getElementById('errorMsgTop').scrollIntoView({behavior:'smooth', block:'center'});
            return;
        }
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        Object.entries(selected).forEach(([slot, ids]) => {
            ids.forEach(id => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = `${slot}[]`; i.value = id;
                container.appendChild(i);
            });
        });
        document.getElementById('submitBtnTop').textContent = '⏳ {{ __('Submitting...') }}';
        document.getElementById('submitBtnBottom').textContent = '⏳ {{ __('Submitting...') }}';
    });

    update();
</script>
</body>
</html>
