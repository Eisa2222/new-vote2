@extends('layouts.admin')

@section('title', __('Players'))
@section('page_title', __('Players'))
@section('page_description', __('Add, edit, filter and manage player data'))

@section('content')
    @include('admin._partials.import-export-bar', [
        'exportUrl' => '/admin/players/export',
        'templateUrl' => '/admin/players/export/template',
        'importUrl' => '/admin/players/import',
        'label' => __('players'),
    ])

    <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm space-y-5 mt-4">
        <form method="get" class="flex items-center gap-3 flex-wrap">

            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search player') }}..."
                class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 outline-none min-w-0 flex-1">

            <select name="club_id" class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm min-w-40">
                <option value="">{{ __('All clubs') }}</option>
                @foreach ($clubs as $c)
                    <option value="{{ $c->id }}" @selected(request('club_id') == $c->id)>{{ $c->localized('name') }}</option>
                @endforeach
            </select>

            <select name="position" class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm min-w-40">
                <option value="">{{ __('All positions') }}</option>
                @foreach ($positions as $p)
                    <option value="{{ $p->value }}" @selected(request('position') === $p->value)>{{ $p->label() }}</option>
                @endforeach
            </select>

            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-semibold shadow-sm transition flex-shrink-0">
                {{ __('Filter') }}
            </button>

            <a href="{{ route('admin.players.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-semibold shadow-sm transition flex-shrink-0">
                <span>+</span>
                <span>{{ __('New Player') }}</span>
            </a>

        </form>


        <div class="text-xs text-ink-500 mt-2">
            {{ __(':total players — showing :shown', ['total' => $players->total(), 'shown' => $players->count()]) }}
        </div>

        @if ($players->count())
            {{-- Denser 4-column grid keeps the page compact when the
             player count grows (TC021). --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
                @foreach ($players as $player)
                    <div class="rounded-2xl border border-gray-200 p-4 bg-white shadow-sm hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            @if ($player->photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($player->photo_path) }}"
                                    class="w-12 h-12 rounded-xl object-cover" alt="">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-xl">🧍
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-lg font-bold truncate">{{ $player->localized('name') }}</h3>
                                    @if ($player->is_captain)
                                        <span
                                            class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold whitespace-nowrap">
                                            ★ {{ __('Captain') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ $player->club?->localized('name') }}</p>
                                <div class="mt-3 flex items-center gap-2 flex-wrap">
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                                        {{ $player->position?->label() }}
                                    </span>
                                    @if ($player->jersey_number)
                                        <span
                                            class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">#{{ $player->jersey_number }}</span>
                                    @endif
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $player->status->value === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $player->status->label() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex gap-2">
                            {{-- Edit button uses the shared btn-edit (amber)
                             so the action vocabulary matches the rest of
                             the app instead of a one-off dark slate button. --}}
                            <a href="{{ route('admin.players.edit', $player) }}" class="btn-edit flex-1 justify-center">
                                <span aria-hidden="true">✏️</span>
                                <span>{{ __('Edit') }}</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div>{{ $players->links() }}</div>
        @else
            <div class="py-16 text-center text-gray-400">{{ __('No players yet.') }}</div>
        @endif
    </div>
@endsection
