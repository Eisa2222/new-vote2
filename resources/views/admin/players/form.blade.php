@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">
        {{ $player->exists ? __('Edit Player') : __('New Player') }}
    </h1>

    <form method="post" enctype="multipart/form-data"
          action="{{ $player->exists ? '/admin/players/'.$player->id : '/admin/players' }}"
          class="bg-white rounded-2xl shadow p-6 md:p-8 space-y-5 form-wrap">
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
                        {{-- ?club_id=N from the "Add player to this club"
                             shortcut on the club edit page preselects here. --}}
                        <option value="{{ $c->id }}" @selected(old('club_id', $player->club_id ?? request('club_id')) == $c->id)>{{ $c->localized('name') }}</option>
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

        {{--
          Nationality — visual two-card picker.
          Drives voter-ballot filtering on the Best Saudi / Best
          Foreign awards. Required on create, preserved on update.
        --}}
        @php
            $currentNationality = old('nationality', $player->nationality?->value ?? 'saudi');
        @endphp
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('Nationality') }} <span class="text-rose-600">*</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="relative flex items-center gap-3 rounded-2xl border-2 p-4 cursor-pointer transition
                              {{ $currentNationality === 'saudi'
                                 ? 'border-brand-500 bg-brand-50'
                                 : 'border-ink-200 bg-white hover:border-brand-300' }}">
                    <input type="radio" name="nationality" value="saudi" required
                           class="sr-only peer"
                           @checked($currentNationality === 'saudi')>
                    <div class="w-12 h-12 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-2xl flex-shrink-0">🇸🇦</div>
                    <div class="flex-1 min-w-0">
                        <div class="font-extrabold text-ink-900">{{ __('Saudi') }}</div>
                        <div class="text-xs text-ink-500">{{ __('Eligible for Best Saudi Player award.') }}</div>
                    </div>
                    <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition
                                 {{ $currentNationality === 'saudi' ? 'border-brand-600 bg-brand-600' : 'border-ink-300' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white {{ $currentNationality === 'saudi' ? 'opacity-100' : 'opacity-0' }}"
                             viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </label>

                <label class="relative flex items-center gap-3 rounded-2xl border-2 p-4 cursor-pointer transition
                              {{ $currentNationality === 'foreign'
                                 ? 'border-amber-500 bg-amber-50'
                                 : 'border-ink-200 bg-white hover:border-amber-300' }}">
                    <input type="radio" name="nationality" value="foreign" required
                           class="sr-only peer"
                           @checked($currentNationality === 'foreign')>
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0">🌍</div>
                    <div class="flex-1 min-w-0">
                        <div class="font-extrabold text-ink-900">{{ __('Non-Saudi') }}</div>
                        <div class="text-xs text-ink-500">{{ __('Eligible for Best Foreign Player award.') }}</div>
                    </div>
                    <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition
                                 {{ $currentNationality === 'foreign' ? 'border-amber-500 bg-amber-500' : 'border-ink-300' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white {{ $currentNationality === 'foreign' ? 'opacity-100' : 'opacity-0' }}"
                             viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </label>
            </div>
            @error('nationality') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
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

        {{-- National ID + mobile number removed from the admin create
             flow — they're voter-facing fields captured on the optional
             profile page after a player casts a vote, not at admin
             roster entry. The columns stay in the DB + request rules
             so that post-vote capture keeps working. --}}

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
              class="form-wrap mt-4 flex justify-end">
            @csrf @method('DELETE')
            <button type="submit" class="btn-delete">
                <span aria-hidden="true">🗑</span>
                <span>{{ __('Delete') }}</span>
            </button>
        </form>
    @endif
@endsection
