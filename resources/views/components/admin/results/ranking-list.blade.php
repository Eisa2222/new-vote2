@props(['result'])

@php
    use Illuminate\Support\Facades\Storage;

    // Group items by category up-front so we don't call groupBy in the loop.
    $groups = $result->items->groupBy('voting_category_id');
@endphp

@foreach($groups as $items)
    @php
        $category = $items->first()->category;
        $categoryTotal = max(1, $items->sum('votes_count'));
    @endphp
    <div class="mb-6 last:mb-0">
        <h3 class="font-semibold text-ink-800 mb-3 flex items-center gap-2">
            <span>{{ $category->localized('title') }}</span>
            @if($category->position_slot !== 'any')
                <span class="text-xs text-ink-500 font-normal">· {{ __(ucfirst($category->position_slot)) }}</span>
            @endif
        </h3>

        <div class="space-y-2.5">
            @foreach($items->sortBy('rank') as $item)
                @php
                    $player     = $item->candidate->player;
                    $label      = $player?->localized('name') ?? $item->candidate->club?->localized('name');
                    $clubName   = $player?->club?->localized('name');
                    $percentage = (int) round(($item->votes_count / $categoryTotal) * 100);
                @endphp
                <div class="rounded-xl border p-3 {{ $item->is_winner ? 'border-emerald-500 bg-emerald-50' : 'border-ink-200' }}">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                                         {{ $item->is_winner ? 'bg-emerald-600 text-white' : 'bg-ink-100 text-ink-700' }}">
                                {{ $item->rank }}
                            </span>
                            <div class="min-w-0">
                                <div class="font-semibold truncate">{{ $label }}</div>
                                @if($clubName)
                                    <div class="text-xs text-ink-500 truncate">{{ $clubName }}</div>
                                @endif
                            </div>
                            @if($item->is_winner)
                                <span class="ms-1 px-2 py-0.5 rounded-full text-xs bg-emerald-600 text-white font-semibold whitespace-nowrap">
                                    ★ {{ __('Winner') }}
                                </span>
                            @endif
                        </div>
                        <div class="text-end whitespace-nowrap">
                            <div class="font-bold">{{ $item->votes_count }}</div>
                            <div class="text-xs text-ink-500">{{ $percentage }}%</div>
                        </div>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-ink-100 overflow-hidden">
                        <div class="h-full rounded-full {{ $item->is_winner ? 'bg-emerald-500' : 'bg-slate-400' }}"
                             style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
