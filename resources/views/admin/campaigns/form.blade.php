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
                <input type="datetime-local" name="start_at" id="startAtInput" value="{{ old('start_at') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('End at') }}</label>
                <input type="datetime-local" name="end_at" id="endAtInput" value="{{ old('end_at') }}" required
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
                <p id="dateError" class="hidden text-xs text-rose-600 mt-1 font-medium">
                    {{ __('End date must be after the start date.') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Max voters') }}</label>
                <input type="number" name="max_voters" value="{{ old('max_voters') }}" min="1"
                       placeholder="{{ __('Optional') }}"
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3">
            </div>
        </div>

        {{-- Club-scoped voting rules.
             Redesigned as toggle-switch "cards" — one card per rule,
             each with its own icon, title, help-text, and an iOS-style
             switch that reflects its on/off state. Much clearer than
             a tight checkbox list, especially on RTL. --}}
        @php
            $rules = [
                [
                    'name'    => 'allow_self_vote',
                    'icon'    => '🙋',
                    'title'   => __('Allow voting for self'),
                    'help'    => __('If off, a voter cannot pick their own name in any award.'),
                    'checked' => (bool) old('allow_self_vote', true),
                ],
                [
                    'name'    => 'allow_teammate_vote',
                    'icon'    => '🤝',
                    'title'   => __('Allow voting for teammates'),
                    'help'    => __('If off, a voter cannot pick any player from their own club.'),
                    'checked' => (bool) old('allow_teammate_vote', true),
                ],
            ];
        @endphp

        <div class="mt-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">⚙️</span>
                <h3 class="text-base font-bold text-ink-900">{{ __('Voting rules') }}</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($rules as $rule)
                    {{-- The hidden "0" input ensures an unchecked switch
                         still posts a value (default POST behaviour of
                         checkbox is to send NOTHING — we want explicit
                         "false"). The visible checkbox shares the same
                         name and overrides when ticked. --}}
                    <label class="relative flex items-start gap-3 rounded-2xl border-2 border-ink-200 bg-white p-4 cursor-pointer transition
                                  hover:border-brand-300
                                  has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40"
                           x-data="{ on: @js($rule['checked']) }">
                        <input type="hidden" name="{{ $rule['name'] }}" value="0">
                        <input type="checkbox" name="{{ $rule['name'] }}" value="1"
                               class="sr-only peer"
                               x-model="on"
                               @checked($rule['checked'])>

                        {{-- Icon bubble --}}
                        <div class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-xl flex-shrink-0"
                             :class="on ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-500'">
                            {{ $rule['icon'] }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-ink-900 leading-tight">{{ $rule['title'] }}</div>
                            <div class="text-xs text-ink-500 mt-1 leading-5">{{ $rule['help'] }}</div>
                        </div>

                        {{-- iOS-style toggle.
                             Pure Tailwind (no component library) so the
                             look is consistent whether JS is on or off. --}}
                        <span class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition
                                     border border-ink-200"
                              :class="on ? 'bg-brand-600 border-brand-600' : 'bg-ink-200'">
                            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition transform
                                         {{ app()->getLocale() === 'ar' ? '-translate-x-0.5' : 'translate-x-0.5' }}
                                         mt-[1px]"
                                  :class="on
                                      ? '{{ app()->getLocale() === 'ar' ? '-translate-x-[22px]' : 'translate-x-[22px]' }}'
                                      : ''"></span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold">{{ __('Questions') }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('Each question has its own list of answers (players). At least one question is required.') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="autoSavedHint"
                      class="text-xs text-emerald-700 opacity-0 transition-opacity duration-300"
                      aria-live="polite">
                    ✓ {{ __('Draft auto-saved') }}
                </span>
                <button type="button" id="addQuestionBtn" class="btn-save">
                    <span>+</span>
                    <span>{{ __('Add question') }}</span>
                </button>
            </div>
        </div>

        <div id="questionsContainer" class="space-y-4"></div>
    </div>

    <div class="sticky bottom-0 bg-white border-t border-ink-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between gap-2">
        <a href="{{ route('admin.campaigns.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
        <button class="btn-save">
            <span aria-hidden="true">💾</span>
            <span>{{ __('Create campaign') }}</span>
        </button>
    </div>
</form>

@php
    // Pre-encode the reference data + any resubmitted (old()) categories
    // so the JS can rehydrate the form on validation failure.
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

    // `old('categories')` is whatever the admin last typed; we feed it back
    // into the JS renderer so nothing is lost on a failed submit (TC014).
    $oldCategoriesJson = json_encode(old('categories', []), JSON_UNESCAPED_UNICODE);
@endphp

<script id="playersData" type="application/json">{!! $playersJson !!}</script>
<script id="leagueClubs" type="application/json">{!! $leagueClubsMap !!}</script>
<script id="oldCategories" type="application/json">{!! $oldCategoriesJson !!}</script>

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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <select name="POSITION_SLOT" class="rounded-xl border border-gray-300 px-3 py-2.5"
                    title="{{ __('Position slot') }}">
                <option value="any">{{ __('Any position') }}</option>
                <option value="attack">{{ __('Attack') }}</option>
                <option value="midfield">{{ __('Midfield') }}</option>
                <option value="defense">{{ __('Defense') }}</option>
                <option value="goalkeeper">{{ __('Goalkeeper') }}</option>
            </select>

            {{-- NEW: award selector.
                 Tells the new club-scoped ballot to use this question's
                 candidates as the shortlist for the chosen award. When
                 left blank, the campaign falls back to the default
                 "all Saudi / all Foreign / all by position" behaviour. --}}
            <select name="AWARD_TYPE" class="rounded-xl border border-gray-300 px-3 py-2.5"
                    title="{{ __('Feed this list into which award?') }}">
                <option value="">— {{ __('No award link') }} —</option>
                <option value="best_saudi">🏆 {{ __('Best Saudi Player') }}</option>
                <option value="best_foreign">🌍 {{ __('Best Foreign Player') }}</option>
                <option value="team_of_the_season">⚽ {{ __('Team of the Season') }}</option>
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
const allPlayers  = JSON.parse(document.getElementById('playersData').textContent);
const leagueClubs = JSON.parse(document.getElementById('leagueClubs').textContent);
const oldCats     = JSON.parse(document.getElementById('oldCategories').textContent) || [];
const tpl         = document.getElementById('questionTemplate');
const container   = document.getElementById('questionsContainer');
let qIndex        = 0;

/* ── TC012 + TC013 — end_at must be strictly after start_at ───────
   Client-side guard: update the min attribute live and surface a
   red helper text before the user ever submits.                    */
(function () {
    const startEl = document.getElementById('startAtInput');
    const endEl   = document.getElementById('endAtInput');
    const errEl   = document.getElementById('dateError');
    if (!startEl || !endEl) return;

    function syncMin() {
        const startValue = startEl.value;
        if (startValue) endEl.min = startValue;
        if (endEl.value && endEl.value <= startValue) {
            errEl.classList.remove('hidden');
            endEl.setCustomValidity('{{ __("End date must be after the start date.") }}');
        } else {
            errEl.classList.add('hidden');
            endEl.setCustomValidity('');
        }
    }
    startEl.addEventListener('change', syncMin);
    endEl.addEventListener('change', syncMin);
    syncMin();
})();

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

/**
 * Builds one question row. Accepts an optional `prefill` object with
 * values the user already submitted (title_ar / title_en /
 * position_slot / required_picks / player_ids[]) so we can restore
 * their work on a failed submit (TC014).
 */
function addQuestion(prefill = null) {
    const i = qIndex++;
    const clone = tpl.content.cloneNode(true);
    const row = clone.querySelector('.question-row');
    row.querySelector('.q-number').textContent = '#' + (i + 1);

    // The mapping below rewrites the `<template>` placeholder names
    // (TITLE_AR, etc.) to the real `categories[i][...]` names when a
    // new question is cloned. AWARD_TYPE is the new hook that links
    // a question to one of the three fixed awards.
    const fieldMap = {
        TITLE_AR:       `categories[${i}][title_ar]`,
        TITLE_EN:       `categories[${i}][title_en]`,
        POSITION_SLOT:  `categories[${i}][position_slot]`,
        AWARD_TYPE:     `categories[${i}][award_type]`,
        REQUIRED_PICKS: `categories[${i}][required_picks]`,
    };
    Object.entries(fieldMap).forEach(([tplName, realName]) => {
        row.querySelectorAll(`[name="${tplName}"]`).forEach(el => el.name = realName);
    });

    // Restore fields if this is a replay of a failed submit.
    if (prefill) {
        row.querySelector(`[name="${fieldMap.TITLE_AR}"]`).value       = prefill.title_ar       ?? '';
        row.querySelector(`[name="${fieldMap.TITLE_EN}"]`).value       = prefill.title_en       ?? '';
        row.querySelector(`[name="${fieldMap.POSITION_SLOT}"]`).value  = prefill.position_slot  ?? 'any';
        row.querySelector(`[name="${fieldMap.AWARD_TYPE}"]`).value     = prefill.award_type     ?? '';
        row.querySelector(`[name="${fieldMap.REQUIRED_PICKS}"]`).value = prefill.required_picks ?? 1;
    }

    // TC015 — removing is allowed only if at least one other question remains.
    row.querySelector('.remove-q').addEventListener('click', () => {
        const rows = document.querySelectorAll('.question-row');
        if (rows.length <= 1) {
            alert('{{ __("At least one question is required.") }}');
            return;
        }
        row.remove();
    });

    const search        = row.querySelector('.answer-search');
    const list          = row.querySelector('.players-list');
    const noMatch       = row.querySelector('.no-match');
    const countLabel    = row.querySelector('.selected-count');
    const selectAllBtn  = row.querySelector('.select-all-btn');
    const clearAllBtn   = row.querySelector('.clear-all-btn');
    const positionSel   = row.querySelector(`[name="${fieldMap.POSITION_SLOT}"]`);
    const selected      = new Set((prefill?.player_ids || []).map(Number));

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

document.getElementById('addQuestionBtn').addEventListener('click', () => addQuestion());

/* ── Bug 7 — Draft autosave ─────────────────────────────────────────
   Admins lose work when they navigate away before finishing a campaign
   (click the sidebar, hit Back, etc.). We persist the whole form to
   localStorage on every input change and rehydrate it on next open.
   Priority: `old()` (fresh validation failure) > localStorage draft >
   empty form. Draft is cleared on successful submit.
--------------------------------------------------------------------*/
const DRAFT_KEY = 'sfpa:campaignForm:v1';

function serializeCurrent() {
    const form = document.getElementById('campaignForm');
    const fd   = new FormData(form);
    const top  = {};
    const cats = [];
    for (const [k, v] of fd.entries()) {
        if (k === '_token') continue;
        const m = k.match(/^categories\[(\d+)\]\[([^\]]+)\](\[\])?$/);
        if (!m) { top[k] = v; continue; }
        const i = +m[1], field = m[2], isArr = !!m[3];
        cats[i] = cats[i] || {};
        if (isArr) {
            cats[i][field] = cats[i][field] || [];
            cats[i][field].push(v);
        } else {
            cats[i][field] = v;
        }
    }
    return { top, categories: cats.filter(Boolean) };
}

function restoreDraft() {
    let draft = null;
    try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (_) {}
    if (!draft || typeof draft !== 'object') return null;
    // Rehydrate top-level inputs (title, dates, type, league, max_voters).
    Object.entries(draft.top || {}).forEach(([name, value]) => {
        const el = document.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!el || el.type === 'file') return;
        el.value = value;
    });
    return Array.isArray(draft.categories) ? draft.categories : [];
}

function saveDraft() {
    try { localStorage.setItem(DRAFT_KEY, JSON.stringify(serializeCurrent())); } catch (_) {}
    const hint = document.getElementById('autoSavedHint');
    if (!hint) return;
    hint.classList.remove('opacity-0');
    clearTimeout(window.__savedTimer);
    window.__savedTimer = setTimeout(() => hint.classList.add('opacity-0'), 1600);
}

/* Decide initial state: old() (server validation bounce) wins, then
   localStorage draft, then a single empty question. */
let initialCats = null;
if (Array.isArray(oldCats) && oldCats.length > 0) {
    initialCats = oldCats;
} else {
    initialCats = restoreDraft();
}
if (initialCats && initialCats.length > 0) {
    initialCats.forEach(cat => addQuestion(cat));
} else {
    addQuestion();
}

/* Debounced autosave on any input change in the whole form. */
document.getElementById('campaignForm').addEventListener('input', () => {
    clearTimeout(window.__draftDebounce);
    window.__draftDebounce = setTimeout(saveDraft, 500);
});

document.getElementById('campaignForm').addEventListener('submit', function (e) {
    const bad = [...document.querySelectorAll('.question-row')].filter(row =>
        row.querySelectorAll('input[name$="[player_ids][]"]:checked').length === 0
    );
    if (bad.length) {
        e.preventDefault();
        alert('{{ __('Each question must have at least one answer (player).') }}');
        return;
    }
    // Form is being submitted for real — clear the draft. If the server
    // rejects with validation errors, the reloaded page will see old()
    // and rebuild the state from there; subsequent edits will re-create
    // a fresh draft immediately.
    try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
});
</script>
@endsection
