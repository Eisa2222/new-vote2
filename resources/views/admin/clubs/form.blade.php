@extends('layouts.admin')
@section('content')
    <h1 class="text-2xl font-bold mb-6 text-slate-800">
        {{ $club->exists ? __('Edit Club') : __('New Club') }}
    </h1>

    <form method="post" enctype="multipart/form-data"
          action="{{ $club->exists ? '/admin/clubs/'.$club->id : '/admin/clubs' }}"
          class="bg-white rounded-2xl shadow p-6 md:p-8 space-y-5 form-wrap">
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
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="w-full">
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

        @if(isset($leagues) && $leagues->count())
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Leagues') }}</label>
                <p class="text-xs text-gray-500 mb-2">{{ __('A club can join multiple leagues across different sports.') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($leagues as $league)
                        <label class="flex items-center gap-2 border rounded-lg px-3 py-1.5">
                            <input type="checkbox" name="league_ids[]" value="{{ $league->id }}"
                                   @checked(in_array($league->id, old('league_ids', $club->leagues->pluck('id')->all())))>
                            <span>{{ $league->localized('name') }}</span>
                            <span class="text-xs text-gray-400">({{ $league->sport?->localized('name') }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

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
            <button type="submit"
                    class="rounded-2xl bg-brand-600 hover:bg-brand-700 text-white px-8 py-3 font-semibold text-base shadow-brand">
                {{ __('Save') }}
            </button>
        </div>
    </form>

    @if($club->exists)
        {{--
          Club roster (current players) + inline "add player" shortcut.
          Clicking "Add player" opens the new-player form with the club
          pre-selected so admins don't have to re-pick from the dropdown.
          This is the "add player from inside the club" workflow.
        --}}
        @php
            $clubPlayers = $club->players()->orderBy('name_en')->limit(50)->get();
        @endphp
        <div class="form-wrap mt-6 card">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ __('Club roster') }}</h2>
                    <p class="text-sm text-ink-500 mt-1">
                        {{ __(':n player(s) registered', ['n' => $club->players()->count()]) }}
                    </p>
                </div>
                <a href="{{ route('admin.players.create') }}?club_id={{ $club->id }}" class="btn-save">
                    <span aria-hidden="true">+</span>
                    <span>{{ __('Add player to this club') }}</span>
                </a>
            </div>

            @if($clubPlayers->isEmpty())
                <div class="text-center text-ink-400 py-10 border-2 border-dashed border-ink-200 rounded-2xl">
                    {{ __('No players yet — add the first one.') }}
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($clubPlayers as $p)
                        <a href="{{ route('admin.players.edit', $p) }}"
                           class="flex items-center gap-3 rounded-xl border border-ink-200 p-3 hover:border-brand-400 hover:bg-brand-50/40 transition">
                            @if($p->photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($p->photo_path) }}"
                                     class="w-10 h-10 rounded-lg object-cover" alt="">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-ink-100 text-ink-500 flex items-center justify-center text-xs">🧍</div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate">{{ $p->localized('name') }}</div>
                                <div class="text-xs text-ink-500">
                                    {{ $p->position?->label() }}
                                    @if($p->jersey_number) · #{{ $p->jersey_number }} @endif
                                </div>
                            </div>
                            @if($p->is_captain)
                                <span class="text-amber-500" title="{{ __('Captain') }}">★</span>
                            @endif
                        </a>
                    @endforeach
                </div>
                @if($club->players()->count() > 50)
                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.players.index') }}?club_id={{ $club->id }}"
                           class="text-sm text-brand-700 font-semibold hover:underline">
                            {{ __('See all :n players →', ['n' => $club->players()->count()]) }}
                        </a>
                    </div>
                @endif
            @endif
        </div>

        {{-- Delete form — MUST be outside the edit form (nested forms break _method). --}}
        <form method="post" action="/admin/clubs/{{ $club->id }}"
              onsubmit="return confirm('{{ __('Delete this club?') }}')"
              class="form-wrap mt-4 flex justify-end">
            @csrf @method('DELETE')
            <button type="submit" class="btn-delete">
                <span aria-hidden="true">🗑</span>
                <span>{{ __('Delete') }}</span>
            </button>
        </form>
    @endif
@endsection
