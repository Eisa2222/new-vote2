@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
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
        .candidate.selected {
            border-color: #059669 !important;
            background: #ecfdf5;
            box-shadow: 0 0 0 2px #10b981;
            transform: scale(1.02);
        }
        .candidate.disabled { opacity: .35; cursor: not-allowed; }
        .pitch { background: linear-gradient(to bottom, #047857, #065f46); position: relative; }
        .pitch::before { content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(to right, rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-emerald-800 text-white p-8 md:p-10 shadow-2xl">
        <div class="grid md:grid-cols-2 gap-6 items-center">
            <div>
                <div class="text-emerald-300 text-sm font-semibold">{{ __('Team of the Season') }}</div>
                <h1 class="text-3xl md:text-4xl font-bold mt-2 leading-tight">{{ $campaign->localized('title') }}</h1>
                @if($campaign->localized('description'))
                    <p class="text-slate-200 mt-3 leading-7">{{ $campaign->localized('description') }}</p>
                @endif
            </div>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-attack">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Attack') }} / 3</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-midfield">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Midfield') }} / 3</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-defense">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Defense') }} / 4</div>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <div class="text-2xl font-bold" id="count-goalkeeper">0</div>
                    <div class="mt-1 text-slate-300">{{ __('Goalkeeper') }} / 1</div>
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

    {{-- Football pitch visualization --}}
    <section class="pitch rounded-3xl overflow-hidden shadow-2xl p-6 md:p-10">
        <div class="relative z-10 space-y-10">
            @foreach(['attack' => 3, 'midfield' => 3, 'defense' => 4, 'goalkeeper' => 1] as $slot => $n)
                <?php $cat = $campaign->categories->firstWhere('position_slot', $slot); ?>
                @if($cat)
                    <div>
                        <div class="text-center text-white mb-4 font-semibold">
                            {{ __(ucfirst($slot)) }} — {{ $n }}
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-{{ max($n, 2) }} gap-3 md:gap-4" data-slot="{{ $slot }}" data-required="{{ $n }}">
                            @foreach($cat->candidates as $cand)
                                <?php
                                    $p = $cand->player;
                                    $name = $p?->localized('name');
                                    $club = $p?->club?->localized('name');
                                    $photo = $p?->photo_path;
                                ?>
                                <label class="candidate block rounded-2xl bg-white p-3 text-center border-2 border-transparent">
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

    <form method="post" id="tosForm" action="{{ route('voting.submit', $campaign->public_token) }}" class="hidden">
        @csrf
    </form>

    <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between gap-4">
        <div id="summary" class="text-sm text-gray-600">{{ __('Pick 3 attack, 3 midfield, 4 defense, 1 goalkeeper.') }}</div>
        <button type="button" id="submitBtn" disabled
                class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold disabled:bg-slate-300 disabled:cursor-not-allowed disabled:hover:bg-slate-300">
            {{ __('Submit my Team of the Season') }}
        </button>
    </div>
</div>

<script>
    const REQUIRED = { attack: 3, midfield: 3, defense: 4, goalkeeper: 1 };
    const selected = { attack: new Set(), midfield: new Set(), defense: new Set(), goalkeeper: new Set() };

    document.querySelectorAll('.candidate').forEach(label => {
        const input = label.querySelector('.cand-input');
        const slot = input.dataset.slot;
        label.addEventListener('click', (e) => {
            if (e.target === input) return;
            e.preventDefault();
            if (label.classList.contains('disabled') && !input.checked) return;
            if (input.checked) { selected[slot].delete(input.value); input.checked = false; }
            else if (selected[slot].size < REQUIRED[slot]) { selected[slot].add(input.value); input.checked = true; }
            else return; // line full
            label.classList.toggle('selected', input.checked);
            update();
        });
    });

    function update() {
        ['attack','midfield','defense','goalkeeper'].forEach(slot => {
            document.getElementById('count-'+slot).textContent = selected[slot].size;
            document.querySelectorAll(`[data-slot="${slot}"] .candidate`).forEach(l => {
                const input = l.querySelector('.cand-input');
                const isFull = selected[slot].size >= REQUIRED[slot];
                l.classList.toggle('disabled', isFull && !input.checked);
            });
        });
        const complete = ['attack','midfield','defense','goalkeeper']
            .every(s => selected[s].size === REQUIRED[s]);
        document.getElementById('submitBtn').disabled = !complete;
    }

    document.getElementById('submitBtn').addEventListener('click', () => {
        const form = document.getElementById('tosForm');
        // Clear any previous hidden inputs
        [...form.querySelectorAll('input[type="hidden"]:not([name="_token"])')].forEach(e => e.remove());
        Object.entries(selected).forEach(([slot, ids]) => {
            ids.forEach(id => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = `${slot}[]`; i.value = id;
                form.appendChild(i);
            });
        });
        form.submit();
    });

    update();
</script>
</body>
</html>
