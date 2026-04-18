@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">
        {{ $player->exists ? __('Edit Player') : __('New Player') }}
    </h1>

    <form method="post" enctype="multipart/form-data"
          action="{{ $player->exists ? '/admin/players/'.$player->id : '/admin/players' }}"
          class="bg-white rounded-2xl shadow p-6 space-y-5 max-w-3xl">
        @csrf
        @if($player->exists) @method('PUT') @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }} (AR)</label>
                <input name="name_ar" value="{{ old('name_ar', $player->name_ar) }}" required class="w-full border rounded-lg px-3 py-2">
                @error('name_ar') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Name') }} (EN)</label>
                <input name="name_en" value="{{ old('name_en', $player->name_en) }}" required class="w-full border rounded-lg px-3 py-2">
                @error('name_en') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Club') }}</label>
                <select name="club_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">—</option>
                    @foreach($clubs as $c)
                        <option value="{{ $c->id }}" @selected(old('club_id', $player->club_id) == $c->id)>{{ $c->localized('name') }}</option>
                    @endforeach
                </select>
                @error('club_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Sport') }}</label>
                <select name="sport_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">—</option>
                    @foreach($sports as $s)
                        <option value="{{ $s->id }}" @selected(old('sport_id', $player->sport_id) == $s->id)>{{ $s->localized('name') }}</option>
                    @endforeach
                </select>
                @error('sport_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Position') }}</label>
                <select name="position" required class="w-full border rounded-lg px-3 py-2">
                    @foreach($positions as $p)
                        <option value="{{ $p->value }}" @selected(old('position', $player->position?->value) === $p->value)>{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Jersey #') }}</label>
                <input type="number" name="jersey_number" value="{{ old('jersey_number', $player->jersey_number) }}" class="w-full border rounded-lg px-3 py-2">
                @error('jersey_number') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Status') }}</label>
                <select name="status" class="w-full border rounded-lg px-3 py-2">
                    <option value="active"   @selected(old('status', $player->status?->value) === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(old('status', $player->status?->value) === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_captain" value="0">
                <input type="checkbox" name="is_captain" value="1" @checked(old('is_captain', $player->is_captain))>
                {{ __('Captain') }}
            </label>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('National ID') }}</label>
                <input name="national_id" value="{{ old('national_id', $player->national_id) }}"
                       placeholder="1xxxxxxxxx"
                       class="w-full border rounded-lg px-3 py-2">
                @error('national_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Mobile number') }}</label>
                <input name="mobile_number" value="{{ old('mobile_number', $player->mobile_number) }}"
                       placeholder="05xxxxxxxx"
                       class="w-full border rounded-lg px-3 py-2">
                @error('mobile_number') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Photo') }}</label>
            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp">
        </div>

        <div class="sticky bottom-0 bg-white pt-4 border-t flex gap-2">
            <button type="submit" class="btn-primary">{{ __('Save') }}</button>
            <a href="/admin/players" class="btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>

    @if($player->exists)
        {{-- Delete form — MUST be outside the edit form. Nested <form> tags are
             not allowed in HTML and cause _method inputs to collide, turning an
             edit submit into a delete. --}}
        <form method="post" action="/admin/players/{{ $player->id }}"
              onsubmit="return confirm('{{ __('Delete this player?') }}')"
              class="max-w-3xl mt-4 flex justify-end">
            @csrf @method('DELETE')
            <button type="submit" class="text-rose-600 hover:underline">{{ __('Delete') }}</button>
        </form>
    @endif
@endsection
