@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">
        {{ $club->exists ? __('Edit Club') : __('New Club') }}
    </h1>

    <form method="post" enctype="multipart/form-data"
          action="{{ $club->exists ? '/admin/clubs/'.$club->id : '/admin/clubs' }}"
          class="bg-white rounded-2xl shadow p-6 space-y-5 max-w-3xl">
        @csrf
        @if($club->exists) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }} (AR)</label>
                <input name="name_ar" value="{{ old('name_ar', $club->name_ar) }}"
                       class="w-full border rounded-lg px-3 py-2" required>
                @error('name_ar') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }} (EN)</label>
                <input name="name_en" value="{{ old('name_en', $club->name_en) }}"
                       class="w-full border rounded-lg px-3 py-2" required>
                @error('name_en') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Short name') }}</label>
            <input name="short_name" value="{{ old('short_name', $club->short_name) }}"
                   class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Logo') }}</label>
            <input type="file" name="logo" accept="image/*" class="w-full">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Sports') }}</label>
            <div class="flex flex-wrap gap-2">
                @foreach($sports as $s)
                    <label class="flex items-center gap-2 border rounded-lg px-3 py-1.5">
                        <input type="checkbox" name="sport_ids[]" value="{{ $s->id }}"
                               @checked(in_array($s->id, old('sport_ids', $club->sports->pluck('id')->all())))>
                        {{ $s->localized('name') }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Status') }}</label>
            <select name="status" class="border rounded-lg px-3 py-2">
                <option value="active"   @selected(old('status', $club->status?->value) === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(old('status', $club->status?->value) === 'inactive')>{{ __('Inactive') }}</option>
            </select>
        </div>

        <div class="sticky bottom-0 bg-white pt-5 pb-2 -mx-6 px-6 border-t border-ink-200 flex items-center justify-between gap-3 flex-wrap">
            <a href="/admin/clubs"
               class="rounded-2xl border-2 border-ink-200 hover:bg-ink-50 text-ink-700 px-6 py-3 font-semibold text-base">
                {{ __('Cancel') }}
            </a>
            <div class="flex gap-3">
                @if($club->exists)
                    <form method="post" action="/admin/clubs/{{ $club->id }}"
                          onsubmit="return confirm('{{ __('Delete this club?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="rounded-2xl border-2 border-danger-500/50 text-danger-600 hover:bg-danger-500/10 px-6 py-3 font-semibold text-base">
                            🗑 {{ __('Delete') }}
                        </button>
                    </form>
                @endif
                <button type="submit"
                        class="rounded-2xl bg-brand-600 hover:bg-brand-700 text-white px-8 py-3 font-semibold text-base shadow-brand">
                    💾 {{ __('Save') }}
                </button>
            </div>
        </div>
    </form>
@endsection
