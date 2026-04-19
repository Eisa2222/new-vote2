@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $campaign->localized('title') }}</title>
    {{-- brand-head loads Tailwind CDN + the custom brand/ink/accent palette.
         Without it all brand-* / ink-* utility classes silently no-op and
         the page renders white. --}}
    @include('partials.brand-head')
    <style>
        .candidate { cursor: pointer; }
        .candidate.selected { border-color: #115C42 !important; background: #ECF5EF; box-shadow: 0 0 0 2px #1F7A49; }
        .candidate.selected .dot { border-color: #115C42; background: #115C42; }
    </style>
</head>
<body class="bg-ink-50 text-ink-900 min-h-screen">
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">

    <section class="rounded-3xl bg-gradient-to-{{ $dir === 'rtl' ? 'l' : 'r' }} from-ink-950 via-ink-900 to-brand-800 text-white p-8 md:p-10 shadow-2xl">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <div>
                <div class="text-brand-300 text-sm font-semibold">{{ __('Public voting campaign') }}</div>
                <h1 class="text-3xl md:text-5xl font-bold mt-3 leading-tight">{{ $campaign->localized('title') }}</h1>
                @if($campaign->localized('description'))
                    <p class="text-ink-200 mt-4 leading-8">{{ $campaign->localized('description') }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white/10 p-5">
                    <div class="text-sm text-ink-300">{{ __('Time remaining') }}</div>
                    <div class="text-2xl font-bold mt-2">{{ $campaign->end_at->diffForHumans(null, true) }}</div>
                </div>
                <div class="rounded-2xl bg-white/10 p-5">
                    <div class="text-sm text-ink-300">{{ __('Voters') }}</div>
                    <div class="text-2xl font-bold mt-2">
                        {{ number_format($campaign->votes()->count()) }}
                        @if($campaign->max_voters)<span class="text-sm text-ink-300">/ {{ number_format($campaign->max_voters) }}</span>@endif
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
        <div class="rounded-2xl bg-brand-50 border border-brand-200 text-brand-800 p-4 flex items-center gap-3">
            @if(!empty($voter['photo']))
                <img src="{{ $voter['photo'] }}" alt="{{ $voter['name'] }}"
                     class="w-12 h-12 rounded-full object-cover border-2 border-brand-500 flex-shrink-0">
            @else
                <div class="w-12 h-12 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-lg flex-shrink-0">
                    {{ mb_strtoupper(mb_substr($voter['name'] ?? '?', 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-600">✓</span>
                    <span class="text-xs text-brand-700 font-semibold">{{ __('Verified voter') }}</span>
                </div>
                <div class="font-bold text-brand-900 truncate">
                    {{ $voter['name'] ?? __('Player') }}
                    @if(!empty($voter['jersey']))
                        <span class="ms-1 inline-block rounded bg-brand-100 text-brand-700 px-1.5 py-0.5 text-[10px] font-bold">#{{ $voter['jersey'] }}</span>
                    @endif
                </div>
                @if(!empty($voter['club']))
                    <div class="text-xs text-brand-700/80 truncate">{{ $voter['club'] }}</div>
                @endif
            </div>
            <div class="text-end text-xs text-brand-700/80 whitespace-nowrap">
                {{ $voter['method'] === 'national_id' ? __('National ID') : __('Mobile') }}
                <div class="font-mono font-bold text-brand-900">{{ $voter['masked'] }}</div>
            </div>
        </div>
    @endisset

    {{-- Voter "exit" — ends the verified session and returns to the
         public campaigns list. Posted via its own form so it lives
         outside the main ballot form and doesn't collide with it. --}}
    <form method="post" action="{{ route('voting.exit', $campaign->public_token) }}"
          onsubmit="return confirm('{{ __('Exit voting? Your unsaved picks will be lost.') }}')"
          class="flex justify-end">
        @csrf
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:border-rose-400 hover:text-rose-600 bg-white text-ink-700 px-4 py-2 text-sm font-medium transition">
            <span aria-hidden="true">↩</span>
            <span>{{ __('Exit voting') }}</span>
        </button>
    </form>

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
                        <label class="candidate rounded-3xl border border-gray-200 p-5 bg-white hover:border-brand-400 hover:shadow-md transition block">
                            <input type="checkbox"
                                   name="selections[{{ $ci }}][candidate_ids][]"
                                   value="{{ $candidate->id }}"
                                   class="hidden candidate-input">
                            <div class="flex items-start gap-4">
                                @if($photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}"
                                         class="w-20 h-20 rounded-2xl object-cover" alt="">
                                @else
                                    <div class="w-20 h-20 rounded-2xl bg-ink-100 flex items-center justify-center text-3xl">
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

        <div class="sticky bottom-0 inset-x-0 z-20 pt-4 pb-4 bg-gradient-to-t from-white via-white/95 to-transparent">
            <div class="rounded-2xl border border-ink-200 bg-white shadow-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="text-sm font-semibold">
                    <span id="globalProgress" class="text-brand-700 text-lg">0</span>
                    <span class="text-ink-500">/ {{ $campaign->categories->sum('required_picks') }} {{ __('picks complete') }}</span>
                </div>
                <button type="submit" id="submitBtn" disabled
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 font-bold shadow-brand disabled:bg-ink-300 disabled:cursor-not-allowed disabled:hover:bg-ink-300 disabled:shadow-none">
                    ✓ {{ __('Submit My Vote') }}
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
