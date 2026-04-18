@props([
    'result',
    'campaign',
])

@php
    use Illuminate\Support\Facades\Storage;

    $tiedByCategory = $result->items
        ->where('needs_committee_decision', true)
        ->groupBy('voting_category_id');
@endphp

@if($tiedByCategory->isNotEmpty())
    <div class="mb-6 rounded-3xl border-2 border-amber-300 bg-gradient-to-br from-amber-50 to-rose-50 p-5 md:p-6 shadow-sm">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl flex-shrink-0">⚖️</div>
            <div class="flex-1 min-w-0">
                <h2 class="font-extrabold text-amber-900 text-lg">
                    {{ __('Tied candidates — committee decision needed') }}
                </h2>
                <p class="text-sm text-amber-800 mt-0.5">
                    {{ __('Two or more candidates tied on the winners cutoff. Pick who gets the remaining slot(s) in each line. Approval and announcement are blocked until every tie is resolved.') }}
                </p>
            </div>
        </div>

        @foreach($tiedByCategory as $categoryId => $tiedItems)
            @php
                $category = $campaign->categories->firstWhere('id', $categoryId);
                if (! $category) continue;
                $confirmedWinners = $result->items
                    ->where('voting_category_id', $categoryId)
                    ->where('is_winner', true)
                    ->count();
                $remaining = max(0, (int) $category->required_picks - $confirmedWinners);
            @endphp

            <form method="post" action="{{ route('admin.results.resolveTie', $result) }}"
                  class="rounded-2xl bg-white border border-amber-200 p-4 mb-3">
                @csrf
                <input type="hidden" name="category_id" value="{{ $category->id }}">

                <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
                    <div>
                        <div class="font-bold text-ink-900">{{ $category->localized('title') }}</div>
                        <div class="text-xs text-ink-500">
                            {{ __('Pick :n of :t tied candidates', ['n' => $remaining, 't' => $tiedItems->count()]) }}
                            · {{ $tiedItems->first()?->votes_count }} {{ __('votes each') }}
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 text-xs font-bold">
                        ⚖️ {{ __('Tie') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                    @foreach($tiedItems as $item)
                        @php
                            $player = $item->candidate?->player;
                            $name   = $player?->localized('name') ?? $item->candidate?->club?->localized('name');
                            $club   = $player?->club?->localized('name');
                            $photo  = $player?->photo_path ? Storage::url($player->photo_path) : null;
                        @endphp
                        <label class="flex items-center gap-3 rounded-xl border border-ink-200 p-3 cursor-pointer hover:border-brand-400 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 transition">
                            <input type="checkbox" name="winner_ids[]" value="{{ $item->candidate_id }}"
                                   class="w-5 h-5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            @if($photo)
                                <img src="{{ $photo }}" alt="{{ $name }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <div class="w-10 h-10 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold">
                                    {{ mb_strtoupper(mb_substr($name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate">{{ $name }}</div>
                                @if($club)<div class="text-xs text-ink-500 truncate">{{ $club }}</div>@endif
                            </div>
                            <span class="text-xs font-bold text-ink-500 whitespace-nowrap">
                                {{ $item->votes_count }} {{ __('votes') }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-3 font-bold shadow-brand">
                    ⚖️ {{ __('Save committee decision') }}
                </button>
            </form>
        @endforeach
    </div>
@endif
