@props([
    'campaign',
    'result',
])

@php
    use Illuminate\Support\Facades\Storage;
    use App\Modules\Campaigns\Domain\TeamOfSeasonFormation;

    $formation     = TeamOfSeasonFormation::fromCampaign($campaign);
    $winnersBySlot = $result->items->where('is_winner', true)->groupBy('position');
@endphp

<div class="mb-6 rounded-3xl overflow-hidden shadow-xl relative"
     style="background: linear-gradient(to bottom, #065f46, #064e3b); min-height: 480px;">
    <div class="absolute inset-0 opacity-20"
         style="background-image: linear-gradient(to right, rgba(255,255,255,.08) 1px, transparent 1px),
                                   linear-gradient(to bottom, rgba(255,255,255,.08) 1px, transparent 1px);
                background-size: 40px 40px;"></div>

    <div class="relative z-10 p-5 md:p-8">
        <div class="text-center text-white mb-6">
            <div class="text-xs uppercase tracking-wider text-emerald-300">{{ __('Official Lineup') }}</div>
            <div class="text-2xl font-bold mt-1">
                {{ $formation['defense'] }}-{{ $formation['midfield'] }}-{{ $formation['attack'] }}
            </div>
        </div>

        @foreach($formation as $slot => $slotCount)
            @php $winners = $winnersBySlot->get($slot, collect())->sortBy('rank'); @endphp
            <div class="mb-6 last:mb-0">
                <div class="text-center text-xs text-emerald-200 mb-3 font-semibold uppercase tracking-wider">
                    {{ __(ucfirst($slot)) }} ({{ $winners->count() }}/{{ $slotCount }})
                </div>
                <div class="flex flex-wrap justify-center gap-3">
                    @foreach($winners as $item)
                        @php
                            $player = $item->candidate->player;
                            $photo  = $player?->photo_path ? Storage::url($player->photo_path) : null;
                        @endphp
                        <div class="w-32 rounded-2xl bg-white p-3 text-center shadow-lg">
                            <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 overflow-hidden mb-2 flex items-center justify-center text-2xl">
                                @if($photo)
                                    <img src="{{ $photo }}" class="w-full h-full object-cover" alt="">
                                @else
                                    🧍
                                @endif
                            </div>
                            <div class="font-bold text-xs text-gray-900 truncate">{{ $player?->localized('name') }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $player?->club?->localized('name') }}</div>
                            <div class="mt-1.5 text-xs text-emerald-700 font-bold">
                                {{ $item->votes_count }} · {{ $item->vote_percentage }}%
                            </div>
                        </div>
                    @endforeach

                    @for($i = $winners->count(); $i < $slotCount; $i++)
                        <div class="w-32 rounded-2xl border-2 border-dashed border-white/30 p-3 text-center opacity-40 text-white text-xs py-8">
                            {{ __('empty') }}
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>
</div>
