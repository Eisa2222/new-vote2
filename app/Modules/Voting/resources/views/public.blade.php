@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign->localized('title') }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: '{{ $locale === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif; }
        .candidate { cursor: pointer; }
        .candidate.selected { border-color: #059669 !important; background: #ecfdf5; box-shadow: 0 0 0 2px #10b981; }
        .candidate.selected .dot { border-color: #059669; background: #059669; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-slate-950 via-slate-900 to-emerald-800 text-white p-8 md:p-10 shadow-2xl">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <div>
                <div class="text-emerald-300 text-sm font-semibold">{{ __('Public voting campaign') }}</div>
                <h1 class="text-3xl md:text-5xl font-bold mt-3 leading-tight">{{ $campaign->localized('title') }}</h1>
                @if($campaign->localized('description'))
                    <p class="text-slate-200 mt-4 leading-8">{{ $campaign->localized('description') }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white/10 p-5">
                    <div class="text-sm text-slate-300">{{ __('Time remaining') }}</div>
                    <div class="text-2xl font-bold mt-2">{{ $campaign->end_at->diffForHumans(null, true) }}</div>
                </div>
                <div class="rounded-2xl bg-white/10 p-5">
                    <div class="text-sm text-slate-300">{{ __('Voters') }}</div>
                    <div class="text-2xl font-bold mt-2">
                        {{ number_format($campaign->votes()->count()) }}
                        @if($campaign->max_voters)<span class="text-sm text-slate-300">/ {{ number_format($campaign->max_voters) }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4">
            @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    @isset($voter)
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm flex items-center gap-2">
            <span>✓</span>
            <span>{{ __('Verified as') }} {{ $voter['method'] === 'national_id' ? __('National ID') : __('Mobile') }}: <strong>{{ $voter['masked'] }}</strong></span>
        </div>
    @endisset

    <form method="post" action="{{ route('voting.submit', $campaign->public_token) }}" id="voteForm" class="space-y-6">
        @csrf

        @foreach($campaign->categories as $ci => $category)
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
                     data-category-id="{{ $category->id }}"
                     data-required="{{ $category->required_picks }}">
                <div class="flex items-center justify-between gap-4 flex-wrap mb-5">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $category->localized('title') }}</h2>
                        <p class="text-gray-500 mt-1">
                            {{ __('Pick exactly :n', ['n' => $category->required_picks]) }}
                            @if($category->position_slot !== 'any')
                                · <span class="font-semibold">{{ __(ucfirst($category->position_slot)) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="category-progress">0</span> / {{ $category->required_picks }}
                    </div>
                </div>

                <input type="hidden" name="selections[{{ $ci }}][category_id]" value="{{ $category->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach($category->candidates as $candidate)
                        <?php
                            $name  = $candidate->player?->localized('name') ?? $candidate->club?->localized('name');
                            $sub   = $candidate->player?->club?->localized('name');
                            $photo = $candidate->player?->photo_path ?? $candidate->club?->logo_path;
                            $pos   = $candidate->player?->position?->label();
                        ?>
                        <label class="candidate rounded-3xl border border-gray-200 p-5 bg-white hover:border-emerald-400 hover:shadow-md transition block">
                            <input type="checkbox"
                                   name="selections[{{ $ci }}][candidate_ids][]"
                                   value="{{ $candidate->id }}"
                                   class="hidden candidate-input">
                            <div class="flex items-start gap-4">
                                @if($photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}"
                                         class="w-20 h-20 rounded-2xl object-cover" alt="">
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center text-3xl">
                                        {{ $candidate->player ? '🧍' : '🏟️' }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-lg font-bold truncate">{{ $name }}</h3>
                                            @if($sub)<p class="text-sm text-gray-500 mt-1">{{ $sub }}</p>@endif
                                        </div>
                                        <div class="dot w-6 h-6 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                                    </div>
                                    @if($pos)
                                        <div class="mt-3">
                                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">{{ $pos }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-4 rounded-t-3xl shadow-lg">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <div class="text-sm text-gray-500">
                    <span id="globalProgress">0</span> / {{ $campaign->categories->sum('required_picks') }} {{ __('picks complete') }}
                </div>
                <button type="submit" id="submitBtn" disabled
                        class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold disabled:bg-slate-300 disabled:cursor-not-allowed disabled:hover:bg-slate-300">
                    {{ __('Submit My Vote') }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    const sections = document.querySelectorAll('section[data-category-id]');
    const submitBtn = document.getElementById('submitBtn');
    const globalProgress = document.getElementById('globalProgress');

    sections.forEach(section => {
        const required = parseInt(section.dataset.required, 10);
        const progressEl = section.querySelector('.category-progress');

        section.querySelectorAll('.candidate').forEach(label => {
            const input = label.querySelector('.candidate-input');
            label.addEventListener('click', e => {
                if (e.target.tagName === 'INPUT') return;
                e.preventDefault();
                const chosen = section.querySelectorAll('.candidate-input:checked').length;
                if (!input.checked && chosen >= required) return;
                input.checked = !input.checked;
                label.classList.toggle('selected', input.checked);
                updateState();
            });
        });
    });

    function updateState() {
        let total = 0, complete = true;
        sections.forEach(s => {
            const required = parseInt(s.dataset.required, 10);
            const n = s.querySelectorAll('.candidate-input:checked').length;
            total += n;
            if (n !== required) complete = false;
            const pe = s.querySelector('.category-progress');
            if (pe) pe.textContent = n;
        });
        globalProgress.textContent = total;
        submitBtn.disabled = !complete;
    }
    updateState();
</script>
</body>
</html>
