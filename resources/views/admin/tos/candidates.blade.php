@extends('layouts.admin')

@section('title', __('TOTS candidates'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Attach players to each line. Only players whose position matches the line can be attached.'))

@section('content')
<div class="flex items-center gap-2 text-sm text-ink-500 mb-6">
    <a href="/admin/campaigns" class="hover:underline">{{ __('Campaigns') }}</a>
    <span>·</span>
    <span>{{ __('Team of the Season candidates') }}</span>
</div>

{{-- TOP: current candidates per line --}}
@foreach($campaign->categories->sortBy('display_order') as $category)
    <?php $slot = $category->position_slot; ?>
    <div class="rounded-3xl border border-ink-200 bg-white p-6 shadow-sm mb-4">
        <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
            <div>
                <h3 class="text-lg font-bold">{{ $category->localized('title') }}</h3>
                <p class="text-sm text-ink-500 mt-1">
                    {{ __('Required picks per voter') }}: <strong>{{ $category->required_picks }}</strong>
                </p>
            </div>
            <span class="badge badge-active">
                {{ $category->candidates->count() }} {{ __('candidates') }}
            </span>
        </div>

        @if($category->candidates->count())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($category->candidates as $cand)
                    <div class="flex items-center justify-between rounded-xl border border-ink-200 p-3">
                        <div class="text-sm min-w-0">
                            <div class="font-medium truncate">{{ $cand->player?->localized('name') }}</div>
                            <div class="text-xs text-ink-500 truncate">{{ $cand->player?->club?->localized('name') }} · #{{ $cand->player?->jersey_number }}</div>
                        </div>
                        <form method="post" action="/admin/candidates/{{ $cand->id }}" onsubmit="return confirm('{{ __('Remove?') }}')">
                            @csrf @method('DELETE')
                            <button class="text-danger-600 hover:underline text-xs">{{ __('Remove') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-ink-500 py-3">{{ __('No candidates yet.') }}</div>
        @endif
    </div>
@endforeach

<div class="rounded-3xl bg-brand-50 border border-brand-200 p-5 flex items-center justify-between gap-4 mb-6 flex-wrap">
    <div class="text-brand-800 text-sm min-w-0">
        {{ __('Public link') }}:
        <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank"
           class="font-mono text-xs underline break-all">{{ url('/vote/'.$campaign->public_token) }}</a>
    </div>
    <a href="/admin/campaigns/{{ $campaign->id }}"
       class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 font-medium whitespace-nowrap">
        {{ __('Go to campaign page') }}
    </a>
</div>

{{-- BOTTOM: unified add-players form --}}
<div class="rounded-3xl border-2 border-dashed border-brand-300 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold mb-2">➕ {{ __('Add players') }}</h2>
    <p class="text-sm text-ink-500 mb-5">
        {{ __('Tick the players you want to attach. Each player goes to the line that matches their position.') }}
    </p>

    @if(collect($availableByPosition)->flatten()->isEmpty())
        <div class="text-center text-ink-500 py-8">
            {{ __('All players are already attached, or none exist for these positions.') }}
        </div>
    @else
        <form method="post" action="/admin/tos/{{ $campaign->id }}/candidates" id="addPlayersForm">
            @csrf
            @php
                $positionLabels = [
                    'attack' => __('Attack'), 'midfield' => __('Midfield'),
                    'defense' => __('Defense'), 'goalkeeper' => __('Goalkeeper'),
                ];
            @endphp

            @foreach(['goalkeeper', 'defense', 'midfield', 'attack'] as $slot)
                @if($availableByPosition[$slot]->isNotEmpty())
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold uppercase text-ink-700 tracking-wide">
                                {{ $positionLabels[$slot] }}
                                <span class="text-xs font-normal text-ink-500">({{ $availableByPosition[$slot]->count() }})</span>
                            </h3>
                            <button type="button" data-select-all="{{ $slot }}"
                                    class="text-xs text-brand-700 hover:underline">{{ __('Select all') }}</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($availableByPosition[$slot] as $player)
                                <label class="player-row flex items-center gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:border-brand-400 hover:bg-brand-50/40 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" name="player_ids[]" value="{{ $player->id }}"
                                           data-slot="{{ $slot }}"
                                           class="w-5 h-5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm truncate">{{ $player->localized('name') }}</div>
                                        <div class="text-xs text-ink-500 truncate">{{ $player->club?->localized('name') }} · #{{ $player->jersey_number }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="sticky bottom-4 bg-brand-700 text-white rounded-2xl px-5 py-3.5 mt-6 flex items-center justify-between gap-3 shadow-brand">
                <div class="text-sm">
                    <span id="selectedCount" class="text-lg font-bold">0</span>
                    {{ __('player(s) selected') }}
                </div>
                <button type="submit" id="attachBtn" disabled
                        class="rounded-xl bg-white text-brand-700 hover:bg-brand-50 px-6 py-2.5 font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                    ✓ {{ __('Attach selected') }}
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    const form = document.getElementById('addPlayersForm');
    if (form) {
        const counter = document.getElementById('selectedCount');
        const btn = document.getElementById('attachBtn');
        function refresh() {
            const n = form.querySelectorAll('input[name="player_ids[]"]:checked').length;
            counter.textContent = n;
            btn.disabled = n === 0;
        }
        form.addEventListener('change', refresh);
        document.querySelectorAll('[data-select-all]').forEach(b => {
            b.addEventListener('click', () => {
                const slot = b.dataset.selectAll;
                const inputs = form.querySelectorAll(`input[data-slot="${slot}"]`);
                const allChecked = [...inputs].every(i => i.checked);
                inputs.forEach(i => i.checked = !allChecked);
                refresh();
            });
        });
    }
</script>
@endpush
@endsection
