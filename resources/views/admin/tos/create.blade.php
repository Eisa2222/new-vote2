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

    <div class="rounded-3xl bg-emerald-50 border border-emerald-200 p-6">
        <h3 class="font-bold text-emerald-900 mb-3">{{ __('Formation (locked)') }}</h3>
        <div class="grid grid-cols-4 gap-3">
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <div class="text-3xl font-bold text-emerald-600">3</div>
                <div class="text-sm text-gray-600 mt-1">{{ __('Attack') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <div class="text-3xl font-bold text-emerald-600">3</div>
                <div class="text-sm text-gray-600 mt-1">{{ __('Midfield') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <div class="text-3xl font-bold text-emerald-600">4</div>
                <div class="text-sm text-gray-600 mt-1">{{ __('Defense') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 text-center shadow-sm">
                <div class="text-3xl font-bold text-emerald-600">1</div>
                <div class="text-sm text-gray-600 mt-1">{{ __('Goalkeeper') }}</div>
            </div>
        </div>
        <p class="text-sm text-emerald-800 mt-4">
            {{ __('The 4 line categories will be created automatically. Next step: attach eligible players to each line.') }}
        </p>
    </div>

    <div class="flex items-center justify-between">
        <a href="/admin/campaigns" class="rounded-2xl border px-5 py-3 hover:bg-gray-50">{{ __('Cancel') }}</a>
        <button class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 font-semibold">
            {{ __('Create & add candidates') }}
        </button>
    </div>
</form>
@endsection
