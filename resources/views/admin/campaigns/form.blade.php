@extends('layouts.admin')

@section('title', __('New Campaign'))
@section('page_title', __('New Campaign'))
@section('page_description', __('Set up a campaign, its questions, and answers'))

@section('content')
<form method="post" action="/admin/campaigns" class="space-y-6" id="campaignForm">
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
        <a href="/admin/campaigns" class="rounded-2xl border px-5 py-3 hover:bg-gray-50">{{ __('Cancel') }}</a>
        <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold">
            {{ __('Create campaign') }}
        </button>
    </div>
</form>

<?php
    $playersJson = $players->map(fn($p) => [
        'id'   => $p->id,
        'name' => $p->localized('name'),
        'club' => $p->club?->localized('name'),
    ])->values()->toJson(JSON_UNESCAPED_UNICODE);
?>
<script id="playersData" type="application/json">{!! $playersJson !!}</script>

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
            <label class="block text-sm font-bold mb-2">{{ __('Answers (players)') }}</label>
            <div class="relative">
                <input type="text" class="answer-search w-full rounded-xl border border-gray-300 px-3 py-2.5"
                       placeholder="{{ __('Search player by name...') }}" autocomplete="off">
                <div class="answer-suggestions absolute z-10 w-full mt-1 bg-white border rounded-xl shadow-lg max-h-60 overflow-y-auto hidden"></div>
            </div>
            <div class="answer-chips mt-3 flex flex-wrap gap-2"></div>
            <p class="text-xs text-gray-400 mt-2 answer-empty">{{ __('No answers added yet.') }}</p>
        </div>
    </div>
</template>

<script>
const allPlayers = JSON.parse(document.getElementById('playersData').textContent);
const tpl = document.getElementById('questionTemplate');
const container = document.getElementById('questionsContainer');
let qIndex = 0;

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

    const search       = row.querySelector('.answer-search');
    const suggestions  = row.querySelector('.answer-suggestions');
    const chips        = row.querySelector('.answer-chips');
    const emptyHint    = row.querySelector('.answer-empty');
    const selected     = new Set();

    function render() {
        chips.innerHTML = '';
        selected.forEach(pid => {
            const p = allPlayers.find(x => x.id === pid);
            if (!p) return;
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-2 rounded-full bg-emerald-100 text-emerald-800 px-3 py-1.5 text-sm font-medium';
            chip.innerHTML = `<span>${p.name}${p.club ? ' — <span class="text-emerald-600">'+p.club+'</span>' : ''}</span>
                              <button type="button" class="text-emerald-700 hover:text-rose-600">✕</button>
                              <input type="hidden" name="categories[${i}][player_ids][]" value="${p.id}">`;
            chip.querySelector('button').addEventListener('click', () => { selected.delete(pid); render(); });
            chips.appendChild(chip);
        });
        emptyHint.style.display = selected.size ? 'none' : '';
    }

    function showSuggestions(q) {
        const query = q.trim().toLowerCase();
        if (!query) { suggestions.classList.add('hidden'); return; }
        const matches = allPlayers
            .filter(p => !selected.has(p.id) && (p.name || '').toLowerCase().includes(query))
            .slice(0, 10);
        if (matches.length === 0) { suggestions.classList.add('hidden'); return; }
        suggestions.innerHTML = '';
        matches.forEach(p => {
            const item = document.createElement('div');
            item.className = 'px-3 py-2 hover:bg-emerald-50 cursor-pointer flex justify-between items-center';
            item.innerHTML = `<span class="font-medium">${p.name}</span>
                              <span class="text-gray-500 text-sm">${p.club || ''}</span>`;
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selected.add(p.id);
                search.value = '';
                suggestions.classList.add('hidden');
                render();
            });
            suggestions.appendChild(item);
        });
        suggestions.classList.remove('hidden');
    }

    search.addEventListener('input',  () => showSuggestions(search.value));
    search.addEventListener('focus',  () => showSuggestions(search.value));
    search.addEventListener('blur',   () => setTimeout(() => suggestions.classList.add('hidden'), 150));

    container.appendChild(clone);
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
