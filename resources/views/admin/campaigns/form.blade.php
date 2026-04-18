@extends('layouts.admin')

@section('title', __('New Campaign'))
@section('page_title', __('New Campaign'))
@section('page_description', __('Set up a campaign, its questions, and answers'))

@section('content')
<form method="post" action="{{ route('admin.campaigns.store') }}" class="space-y-6" id="campaignForm">
    @csrf

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
        <h2 class="text-xl font-bold">{{ __('Campaign info') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Title') }} (AR)</label>
                <input name="title_ar" value="{{ old('title_ar') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Title') }} (EN)</label>
                <input name="title_en" value="{{ old('title_en') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none">
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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Type') }}</label>
                <select name="type" class="w-full rounded-2xl border border-gray-300 px-4 py-3" required>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(old('type') === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            @isset($leagues)
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('League') }} <span class="text-gray-400 text-xs">({{ __('optional') }})</span></label>
                <select name="league_id" id="leagueSelect" class="w-full rounded-2xl border border-gray-300 px-4 py-3">
                    <option value="">— {{ __('All clubs') }} —</option>
                    @foreach($leagues as $league)
                        <option value="{{ $league->id }}" @selected(old('league_id') == $league->id)>
                            {{ $league->localized('name') }} ({{ $league->sport?->localized('name') }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">{{ __('Select a league to restrict candidates to its clubs.') }}</p>
            </div>
            @endisset
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

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold">{{ __('Questions') }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Each question has its own list of answers (players).') }}</p>
            </div>
            <button type="button" id="addQuestionBtn"
                    class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 font-semibold">
                + {{ __('Add question') }}
            </button>
        </div>

        <div id="questionsContainer" class="space-y-4"></div>
    </div>

    <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between">
        <a href="{{ route('admin.campaigns.index') }}" class="rounded-2xl border px-5 py-3 hover:bg-gray-50">{{ __('Cancel') }}</a>
        <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold">
            {{ __('Create campaign') }}
        </button>
    </div>
</form>

<?php
    $playersJson = $players->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->localized('name'),
        'club'     => $p->club?->localized('name'),
        'club_id'  => $p->club_id,
        'position' => $p->position?->value,
    ])->values()->toJson(JSON_UNESCAPED_UNICODE);

    $leagueClubsMap = isset($leagues)
        ? $leagues->mapWithKeys(fn ($l) => [$l->id => $l->clubs->pluck('id')->all()])->toJson()
        : '{}';
?>
<script id="playersData" type="application/json">{!! $playersJson !!}</script>
<script id="leagueClubs" type="application/json">{!! $leagueClubsMap !!}</script>

<template id="questionTemplate">
    <div class="question-row rounded-2xl border-2 border-gray-200 p-5 bg-gray-50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">
                {{ __('Question') }} <span class="q-number text-emerald-600"></span>
            </h3>
            <button type="button" class="remove-q text-rose-600 hover:underline text-sm font-medium">
                ✕ {{ __('Remove question') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <input name="TITLE_AR" placeholder="{{ __('Question title (Arabic)') }}" required
                   class="rounded-xl border border-gray-300 px-3 py-2.5">
            <input name="TITLE_EN" placeholder="{{ __('Question title (English)') }}" required
                   class="rounded-xl border border-gray-300 px-3 py-2.5">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <select name="POSITION_SLOT" class="rounded-xl border border-gray-300 px-3 py-2.5">
                <option value="any">{{ __('Any position') }}</option>
                <option value="attack">{{ __('Attack') }}</option>
                <option value="midfield">{{ __('Midfield') }}</option>
                <option value="defense">{{ __('Defense') }}</option>
                <option value="goalkeeper">{{ __('Goalkeeper') }}</option>
            </select>
            <input type="number" name="REQUIRED_PICKS" min="1" max="11" value="1"
                   placeholder="{{ __('Required picks') }}" required
                   class="rounded-xl border border-gray-300 px-3 py-2.5">
            <div class="text-sm text-gray-500 self-center">{{ __('How many answers each voter picks') }}</div>
        </div>

        <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                <label class="block text-sm font-bold">{{ __('Answers (players)') }}</label>
                <div class="flex items-center gap-2 flex-wrap">
                    <input type="text" class="answer-search rounded-xl border border-gray-300 px-3 py-1.5 text-sm w-48"
                           placeholder="🔍 {{ __('Filter...') }}" autocomplete="off">
                    <button type="button" class="select-all-btn text-xs text-emerald-700 hover:underline font-medium">
                        {{ __('Select all') }}
                    </button>
                    <button type="button" class="clear-all-btn text-xs text-rose-600 hover:underline font-medium">
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>
            <div class="text-xs text-ink-500 mb-2">
                <span class="selected-count">0</span> {{ __('selected') }}
            </div>

            <div class="players-list rounded-xl border border-gray-200 bg-white p-2 max-h-80 overflow-y-auto">
                {{-- Rows are injected by JS, filtered by league + position --}}
            </div>

            <p class="text-xs text-gray-400 mt-2 no-match hidden">{{ __('No players match the current filters.') }}</p>
        </div>
    </div>
</template>

<script>
const allPlayers = JSON.parse(document.getElementById('playersData').textContent);
const leagueClubs = JSON.parse(document.getElementById('leagueClubs').textContent);
const tpl = document.getElementById('questionTemplate');
const container = document.getElementById('questionsContainer');
let qIndex = 0;

function filteredPlayers(position) {
    const sel = document.getElementById('leagueSelect');
    const leagueId = sel ? sel.value : '';
    let list = allPlayers;
    if (leagueId && leagueClubs[leagueId]) {
        const allowed = new Set(leagueClubs[leagueId]);
        list = list.filter(p => allowed.has(p.club_id));
    }
    if (position && position !== 'any') {
        list = list.filter(p => p.position === position);
    }
    return list;
}

function addQuestion() {
    const i = qIndex++;
    const clone = tpl.content.cloneNode(true);
    const row = clone.querySelector('.question-row');
    row.querySelector('.q-number').textContent = '#' + (i + 1);
    row.querySelectorAll('[name="TITLE_AR"]')      .forEach(e => e.name = `categories[${i}][title_ar]`);
    row.querySelectorAll('[name="TITLE_EN"]')      .forEach(e => e.name = `categories[${i}][title_en]`);
    row.querySelectorAll('[name="POSITION_SLOT"]') .forEach(e => e.name = `categories[${i}][position_slot]`);
    row.querySelectorAll('[name="REQUIRED_PICKS"]').forEach(e => e.name = `categories[${i}][required_picks]`);
    row.querySelector('.remove-q').addEventListener('click', () => row.remove());

    const search        = row.querySelector('.answer-search');
    const list          = row.querySelector('.players-list');
    const noMatch       = row.querySelector('.no-match');
    const countLabel    = row.querySelector('.selected-count');
    const selectAllBtn  = row.querySelector('.select-all-btn');
    const clearAllBtn   = row.querySelector('.clear-all-btn');
    const positionSel   = row.querySelector(`[name="categories[${i}][position_slot]"]`);
    const selected      = new Set();

    function render() {
        const pos   = positionSel?.value || 'any';
        const query = (search.value || '').trim().toLowerCase();
        const pool  = filteredPlayers(pos)
            .filter(p => !query || (p.name || '').toLowerCase().includes(query));

        list.innerHTML = '';
        if (pool.length === 0) {
            noMatch.classList.remove('hidden');
        } else {
            noMatch.classList.add('hidden');
            pool.forEach(p => {
                const label = document.createElement('label');
                label.className = 'flex items-center gap-3 rounded-lg p-2 cursor-pointer hover:bg-emerald-50 has-[:checked]:bg-emerald-100 has-[:checked]:border-emerald-400 border border-transparent transition';
                label.innerHTML = `
                    <input type="checkbox" name="categories[${i}][player_ids][]" value="${p.id}"
                           class="player-cb w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                           ${selected.has(p.id) ? 'checked' : ''}>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm truncate">${p.name}</div>
                        <div class="text-xs text-gray-500 truncate">${p.club || ''}</div>
                    </div>
                    <span class="text-xs rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 whitespace-nowrap">${p.position || ''}</span>`;
                const cb = label.querySelector('.player-cb');
                cb.addEventListener('change', () => {
                    if (cb.checked) selected.add(p.id);
                    else selected.delete(p.id);
                    countLabel.textContent = selected.size;
                });
                list.appendChild(label);
            });
        }
        countLabel.textContent = selected.size;
    }

    positionSel?.addEventListener('change', () => {
        const pos = positionSel.value;
        if (pos && pos !== 'any') {
            [...selected].forEach(pid => {
                const p = allPlayers.find(x => x.id === pid);
                if (p && p.position !== pos) selected.delete(pid);
            });
        }
        render();
    });
    search.addEventListener('input', render);
    selectAllBtn.addEventListener('click', () => {
        const pos   = positionSel?.value || 'any';
        const query = (search.value || '').trim().toLowerCase();
        filteredPlayers(pos)
            .filter(p => !query || (p.name || '').toLowerCase().includes(query))
            .forEach(p => selected.add(p.id));
        render();
    });
    clearAllBtn.addEventListener('click', () => { selected.clear(); render(); });

    container.appendChild(clone);
    render();
}

document.getElementById('addQuestionBtn').addEventListener('click', addQuestion);
addQuestion();

document.getElementById('campaignForm').addEventListener('submit', function (e) {
    const bad = [...document.querySelectorAll('.question-row')].filter(row =>
        row.querySelectorAll('input[name$="[player_ids][]"]').length === 0
    );
    if (bad.length) {
        e.preventDefault();
        alert('{{ __('Each question must have at least one answer (player).') }}');
    }
});
</script>
@endsection
