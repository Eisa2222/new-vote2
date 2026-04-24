@extends('layouts.admin')

@section('title', __('Edit Campaign'))
@section('page_title', __('Edit Campaign'))
@section('page_description', __('Only draft campaigns can be edited. Categories and candidates are managed separately.'))

@section('content')
<div class="flex items-center gap-2 text-sm text-ink-500 mb-6">
    <a href="/admin/campaigns" class="hover:underline">{{ __('Campaigns') }}</a>
    <span>·</span>
    <a href="/admin/campaigns/{{ $campaign->id }}" class="hover:underline">{{ $campaign->localized('title') }}</a>
    <span>·</span>
    <span>{{ __('Edit') }}</span>
</div>

<form method="post" action="/admin/campaigns/{{ $campaign->id }}" class="space-y-6 form-wrap">
    @csrf
    @method('PUT')

    <div class="card space-y-5">
        <h2 class="text-xl font-bold">{{ __('Campaign info') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Title') }} (AR)</label>
                <input name="title_ar" value="{{ old('title_ar', $campaign->title_ar) }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                @error('title_ar') <p class="text-danger-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Title') }} (EN)</label>
                <input name="title_en" value="{{ old('title_en', $campaign->title_en) }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                @error('title_en') <p class="text-danger-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Description') }} (AR)</label>
                <textarea name="description_ar" rows="3"
                          class="w-full rounded-xl border border-ink-200 px-4 py-3">{{ old('description_ar', $campaign->description_ar) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Description') }} (EN)</label>
                <textarea name="description_en" rows="3"
                          class="w-full rounded-xl border border-ink-200 px-4 py-3">{{ old('description_en', $campaign->description_en) }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Type selector removed — see form.blade.php for rationale.
                 Hidden input preserves the existing value on update. --}}
            <input type="hidden" name="type" value="{{ old('type', $campaign->type->value) }}">
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Start at') }}</label>
                <input type="datetime-local" name="start_at"
                       value="{{ old('start_at', $campaign->start_at->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-3">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('End at') }}</label>
                <input type="datetime-local" name="end_at"
                       value="{{ old('end_at', $campaign->end_at->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-xl border border-ink-200 px-4 py-3">
            </div>
            {{-- Campaign-level max_voters removed — per-club quota
                 lives on the Campaign Clubs page. --}}
        </div>
    </div>

    <div class="rounded-2xl bg-brand-50 border border-brand-200 p-4 text-sm text-brand-800">
        💡 {{ __('To edit questions, answers, or candidates — use') }}
        <a href="/admin/campaigns/{{ $campaign->id }}/categories" class="font-semibold underline">{{ __('Manage categories & candidates') }}</a>.
    </div>

    <div class="sticky bottom-0 bg-white border-t border-ink-200 p-4 rounded-t-3xl shadow-lg flex items-center justify-between gap-2">
        <a href="/admin/campaigns/{{ $campaign->id }}" class="btn-ghost">{{ __('Cancel') }}</a>
        <button class="btn-save">
            <span>{{ __('Save changes') }}</span>
        </button>
    </div>
</form>
@endsection
