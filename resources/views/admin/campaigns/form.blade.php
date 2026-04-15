@extends('layouts.admin')

@section('title', __('New Campaign'))
@section('page_title', __('New Campaign'))
@section('page_description', __('Set up a campaign, its categories, and candidates'))

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
            <h2 class="text-xl font-bold">{{ __('Categories') }}</h2>
            <button type="button" id="addCategoryBtn"
                    class="rounded-2xl border border-emerald-500 text-emerald-700 hover:bg-emerald-50 px-4 py-2 font-medium">
                + {{ __('Add category') }}
            </button>
        </div>

        <div id="categoriesContainer" class="space-y-4">
            {{-- Categories are injected by JS. One default row added on load. --}}
        </div>
    </div>

    <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between">
        <a href="/admin/campaigns" class="rounded-2xl border px-5 py-3 hover:bg-gray-50">{{ __('Cancel') }}</a>
        <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold">
            {{ __('Create campaign') }}
        </button>
    </div>
</form>

<template id="categoryTemplate">
    <div class="category-row rounded-2xl border border-gray-200 p-5 bg-gray-50/50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">{{ __('Category') }} <span class="cat-label"></span></h3>
            <button type="button" class="remove-cat text-rose-600 hover:underline text-sm">{{ __('Remove') }}</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <input name="TITLE_AR" placeholder="{{ __('Title AR') }}" required
                   class="md:col-span-1 rounded-xl border border-gray-300 px-3 py-2">
            <input name="TITLE_EN" placeholder="{{ __('Title EN') }}" required
                   class="md:col-span-1 rounded-xl border border-gray-300 px-3 py-2">
            <select name="POSITION_SLOT" class="rounded-xl border border-gray-300 px-3 py-2">
                <option value="any">{{ __('Any') }}</option>
                <option value="attack">{{ __('Attack') }}</option>
                <option value="midfield">{{ __('Midfield') }}</option>
                <option value="defense">{{ __('Defense') }}</option>
                <option value="goalkeeper">{{ __('Goalkeeper') }}</option>
            </select>
            <input type="number" name="REQUIRED_PICKS" min="1" max="11" value="1" placeholder="{{ __('Required picks') }}" required
                   class="rounded-xl border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __('Candidates (players)') }}</label>
            <select name="PLAYER_IDS" multiple size="6"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
                @foreach($players as $p)
                    <option value="{{ $p->id }}">{{ $p->localized('name') }} — {{ $p->club?->localized('name') }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ __('Hold Ctrl / Cmd to select multiple') }}</p>
        </div>
    </div>
</template>

<script>
    let catIndex = 0;
    const tpl = document.getElementById('categoryTemplate');
    const container = document.getElementById('categoriesContainer');

    function addCategory() {
        const i = catIndex++;
        const clone = tpl.content.cloneNode(true);
        const row = clone.querySelector('.category-row');
        row.querySelector('.cat-label').textContent = '#' + (i + 1);
        row.querySelectorAll('[name="TITLE_AR"]')      .forEach(e => e.name = `categories[${i}][title_ar]`);
        row.querySelectorAll('[name="TITLE_EN"]')      .forEach(e => e.name = `categories[${i}][title_en]`);
        row.querySelectorAll('[name="POSITION_SLOT"]') .forEach(e => e.name = `categories[${i}][position_slot]`);
        row.querySelectorAll('[name="REQUIRED_PICKS"]').forEach(e => e.name = `categories[${i}][required_picks]`);
        row.querySelectorAll('[name="PLAYER_IDS"]')    .forEach(e => e.name = `categories[${i}][player_ids][]`);
        row.querySelector('.remove-cat').addEventListener('click', () => row.remove());
        container.appendChild(clone);
    }

    document.getElementById('addCategoryBtn').addEventListener('click', addCategory);
    addCategory();
</script>
@endsection
