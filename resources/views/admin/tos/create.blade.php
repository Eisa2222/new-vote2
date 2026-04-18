@extends('layouts.admin')

@section('title', __('Team of the Season'))
@section('page_title', __('New Team of the Season campaign'))
@section('page_description', __('Create a TOTS campaign — 4 line categories (3-3-4-1) are seeded automatically'))

@section('content')
<form method="post" action="/admin/tos" class="space-y-6">
    @csrf

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
        <h2 class="text-xl font-bold">{{ __('Campaign info') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Title') }} (AR)</label>
                <input name="title_ar" value="{{ old('title_ar') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Title') }} (EN)</label>
                <input name="title_en" value="{{ old('title_en') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Description') }} (AR)</label>
                <textarea name="description_ar" rows="2" class="w-full rounded-2xl border border-gray-300 px-4 py-3">{{ old('description_ar') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Description') }} (EN)</label>
                <textarea name="description_en" rows="2" class="w-full rounded-2xl border border-gray-300 px-4 py-3">{{ old('description_en') }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Start at') }}</label>
                <input type="datetime-local" name="start_at" value="{{ old('start_at') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('End at') }}</label>
                <input type="datetime-local" name="end_at" value="{{ old('end_at') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Max voters') }}</label>
                <input type="number" name="max_voters" value="{{ old('max_voters') }}" min="1"
                       placeholder="{{ __('Optional') }}"
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
        </div>
    </div>

    {{-- LEAGUE + AUTO-POPULATE --}}
    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="text-2xl">⚽</div>
            <div class="flex-1">
                <h3 class="font-bold text-amber-900">{{ __('League & candidates') }}</h3>
                <p class="text-xs text-amber-800 mt-0.5">
                    {{ __('Pick a league to scope the candidates, and optionally include all its players automatically.') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-amber-900">{{ __('League') }}</label>
                <select name="league_id" id="leagueSelect"
                        class="w-full rounded-2xl border border-amber-300 bg-white px-4 py-3">
                    <option value="">— {{ __('Not linked to a league') }} —</option>
                    @foreach($leagues as $league)
                        <option value="{{ $league->id }}" @selected(old('league_id') == $league->id)>
                            {{ $league->localized('name') }}
                            @if($league->sport) — {{ $league->sport->localized('name') }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <label class="flex items-start gap-3 cursor-pointer select-none w-full rounded-2xl bg-white border border-amber-300 p-3 hover:border-amber-500 has-[:checked]:border-amber-600 has-[:checked]:bg-amber-100 transition">
                    <input type="checkbox" name="auto_populate" value="1" id="autoPopulate"
                           {{ old('auto_populate') ? 'checked' : '' }}
                           class="mt-1 w-5 h-5 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                    <span>
                        <span class="block font-semibold text-amber-900">
                            {{ __('Auto-attach all players from this league') }}
                        </span>
                        <span class="block text-xs text-amber-800 mt-0.5">
                            {{ __('Active players will be attached to their matching line (goalkeeper / defense / midfield / attack).') }}
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-3xl bg-emerald-50 border border-emerald-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-emerald-900">{{ __('Formation') }}</h3>
            <div class="text-sm text-emerald-700">
                {{ __('Outfield total must equal') }} <strong>{{ $outfield }}</strong>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <label class="text-sm text-gray-600 block mb-2">{{ __('Attack') }}</label>
                <input type="number" name="attack" id="fAttack"
                       value="{{ old('attack', $default['attack']) }}"
                       min="{{ $minLine }}" max="{{ $maxLine }}" required
                       class="w-full text-center text-3xl font-bold text-emerald-600 rounded-xl border border-emerald-200 px-2 py-2">
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <label class="text-sm text-gray-600 block mb-2">{{ __('Midfield') }}</label>
                <input type="number" name="midfield" id="fMidfield"
                       value="{{ old('midfield', $default['midfield']) }}"
                       min="{{ $minLine }}" max="{{ $maxLine }}" required
                       class="w-full text-center text-3xl font-bold text-emerald-600 rounded-xl border border-emerald-200 px-2 py-2">
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <label class="text-sm text-gray-600 block mb-2">{{ __('Defense') }}</label>
                <input type="number" name="defense" id="fDefense"
                       value="{{ old('defense', $default['defense']) }}"
                       min="{{ $minLine }}" max="{{ $maxLine }}" required
                       class="w-full text-center text-3xl font-bold text-emerald-600 rounded-xl border border-emerald-200 px-2 py-2">
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm opacity-60">
                <label class="text-sm text-gray-600 block mb-2">{{ __('Goalkeeper') }}</label>
                <div class="text-3xl font-bold text-emerald-600 py-2">1</div>
                <div class="text-xs text-gray-400">{{ __('Fixed') }}</div>
            </div>
        </div>

        <div id="formationSum" class="mt-4 text-sm text-emerald-800 text-center">
            {{ __('Sum') }}: <span id="sumValue">—</span> / {{ $outfield }}
        </div>

        <div class="flex flex-wrap gap-2 mt-4 text-xs">
            <span class="text-gray-600">{{ __('Presets') }}:</span>
            <button type="button" data-f="4,3,3" class="preset rounded-full border px-3 py-1 hover:bg-white">4-3-3</button>
            <button type="button" data-f="3,4,3" class="preset rounded-full border px-3 py-1 hover:bg-white">3-4-3</button>
            <button type="button" data-f="4,4,2" class="preset rounded-full border px-3 py-1 hover:bg-white">4-4-2</button>
            <button type="button" data-f="3,5,2" class="preset rounded-full border px-3 py-1 hover:bg-white">3-5-2</button>
            <button type="button" data-f="5,3,2" class="preset rounded-full border px-3 py-1 hover:bg-white">5-3-2</button>
            <button type="button" data-f="4,5,1" class="preset rounded-full border px-3 py-1 hover:bg-white">4-5-1</button>
        </div>

        <p class="text-sm text-emerald-800 mt-4">
            {{ __('The 4 line categories will be created automatically. Next step: attach eligible players to each line.') }}
        </p>
    </div>

    <script>
        const inAtt = document.getElementById('fAttack');
        const inMid = document.getElementById('fMidfield');
        const inDef = document.getElementById('fDefense');
        const sumEl = document.getElementById('sumValue');
        const target = {{ $outfield }};

        function update() {
            const s = (+inAtt.value || 0) + (+inMid.value || 0) + (+inDef.value || 0);
            sumEl.textContent = s;
            sumEl.className = s === target ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
        }
        [inAtt, inMid, inDef].forEach(e => e.addEventListener('input', update));
        // presets (defense-midfield-attack as commonly written)
        document.querySelectorAll('.preset').forEach(btn => {
            btn.addEventListener('click', () => {
                const [d, m, a] = btn.dataset.f.split(',').map(Number);
                inDef.value = d; inMid.value = m; inAtt.value = a;
                update();
            });
        });
        update();
    </script>

    {{-- Sticky submit bar — sits above the page footer with extra bottom
         padding so the layout footer never overlaps the primary action. --}}
    <div class="sticky bottom-0 z-20 bg-white border-t border-gray-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between mt-6 mb-6">
        <a href="{{ route('admin.campaigns.index') }}" class="rounded-2xl border px-5 py-3 hover:bg-gray-50">{{ __('Cancel') }}</a>
        <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold">
            {{ __('Create & add candidates') }}
        </button>
    </div>
</form>
@endsection
