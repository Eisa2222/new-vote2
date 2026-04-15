@extends('layouts.admin')

@section('title', __('TOTS candidates'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Attach players to each line. Only players whose position matches the line can be attached.'))

@section('content')
<div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
    <a href="/admin/campaigns" class="hover:underline">{{ __('Campaigns') }}</a>
    <span>·</span>
    <span>{{ __('Team of the Season candidates') }}</span>
</div>

@foreach($campaign->categories->sortBy('display_order') as $category)
    <?php
        $slot    = $category->position_slot;
        $choices = $byPosition[$slot] ?? collect();
        $picked  = $category->candidates->pluck('player_id')->filter()->all();
    ?>
    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm mb-5">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-bold">{{ $category->localized('title') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Required picks per voter') }}: {{ $category->required_picks }} ·
                    {{ __('Position filter') }}: {{ __(ucfirst($slot)) }}
                </p>
            </div>
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-sm font-semibold">
                {{ $category->candidates->count() }} {{ __('candidates') }}
            </span>
        </div>

        <form method="post" action="/admin/tos/{{ $campaign->id }}/candidates" class="flex items-end gap-2 mb-4">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1">{{ __('Add players') }}</label>
                <select name="player_ids[]" multiple size="5"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
                    @foreach($choices as $p)
                        @continue(in_array($p->id, $picked))
                        <option value="{{ $p->id }}">
                            {{ $p->localized('name') }} — {{ $p->club?->localized('name') }} (#{{ $p->jersey_number }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">{{ __('Hold Ctrl / Cmd to select multiple') }}</p>
            </div>
            <button class="rounded-xl bg-slate-900 text-white px-5 py-3 font-medium">+ {{ __('Attach') }}</button>
        </form>

        @if($category->candidates->count())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($category->candidates as $cand)
                    <div class="flex items-center justify-between rounded-xl border border-gray-200 p-3">
                        <div class="text-sm">
                            <div class="font-medium">{{ $cand->player?->localized('name') }}</div>
                            <div class="text-xs text-gray-500">{{ $cand->player?->club?->localized('name') }}</div>
                        </div>
                        <form method="post" action="/admin/candidates/{{ $cand->id }}" onsubmit="return confirm('{{ __('Remove?') }}')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 hover:underline text-xs">{{ __('Remove') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-gray-400 py-4">{{ __('No candidates yet.') }}</div>
        @endif
    </div>
@endforeach

<div class="rounded-3xl bg-emerald-50 border border-emerald-200 p-5 flex items-center justify-between">
    <div class="text-emerald-900">
        {{ __('Public link') }}:
        <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank"
           class="font-mono text-sm underline">{{ url('/vote/'.$campaign->public_token) }}</a>
    </div>
    <a href="/admin/campaigns/{{ $campaign->id }}"
       class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 font-medium">
        {{ __('Go to campaign page') }}
    </a>
</div>
@endsection
