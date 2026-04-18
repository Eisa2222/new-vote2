@extends('layouts.admin')

@section('title', __('Results'))
@section('page_title', $campaign->localized('title'))
@section('page_description', __('Manage results for this campaign'))

@section('content')
<?php
    $visibility   = $campaign->results_visibility?->value ?? 'hidden';
    $resultStatus = $result?->status?->value;
    $totalVotes   = $campaign->votes()->count();

    $visibilityLabels = [
        'hidden'    => __('Hidden from public'),
        'approved'  => __('Approved (internal only)'),
        'announced' => __('Announced to public'),
    ];
    $visibilityDescriptions = [
        'hidden'    => __('Results are not visible anywhere — only admins can see them.'),
        'approved'  => __('Committee approved but still not public. Click Announce to publish.'),
        'announced' => __('Results are live on the public page.'),
    ];
    $visibilityColors = [
        'hidden'    => 'bg-slate-100 text-slate-700 border-slate-200',
        'approved'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'announced' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];

    // Progress steps — Calculated → Approved → Announced
    $step = match (true) {
        $resultStatus === 'announced'  => 3,
        $resultStatus === 'approved'   => 2,
        $resultStatus === 'calculated' => 1,
        default                        => 0,
    };
?>

<div class="flex items-center gap-2 text-sm text-ink-500 mb-4">
    <a href="/admin/results" class="hover:underline">{{ __('Results') }}</a>
    <span>·</span>
    <a href="/admin/campaigns/{{ $campaign->id }}" class="hover:underline">{{ $campaign->localized('title') }}</a>
</div>

{{-- WORKFLOW TIMELINE --}}
<div class="rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm mb-5">
    <h2 class="text-sm font-bold text-ink-700 uppercase tracking-wide mb-5">
        {{ __('Results workflow') }}
    </h2>
    <div class="flex items-center gap-2 md:gap-4">
        @foreach([
            ['label' => __('Calculate'),  'desc' => __('Tally votes'),       'done' => $step >= 1],
            ['label' => __('Approve'),    'desc' => __('Committee sign-off'),'done' => $step >= 2],
            ['label' => __('Announce'),   'desc' => __('Publish publicly'),  'done' => $step >= 3],
        ] as $i => $s)
            <div class="flex-1">
                <div class="flex items-center gap-2 md:gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold flex-shrink-0
                                {{ $s['done'] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                        {{ $s['done'] ? '✓' : $i + 1 }}
                    </div>
                    @if($i < 2)
                        <div class="flex-1 h-1 rounded-full {{ ($s['done'] && $step > $i + 1) ? 'bg-emerald-500' : 'bg-slate-100' }}"></div>
                    @endif
                </div>
                <div class="mt-2">
                    <div class="font-semibold text-sm md:text-base {{ $s['done'] ? 'text-emerald-700' : 'text-ink-500' }}">{{ $s['label'] }}</div>
                    <div class="text-xs text-ink-500">{{ $s['desc'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- LEFT: ranking --}}
    <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
            <h2 class="text-xl font-bold">{{ __('Ranking') }}</h2>
            <div class="flex gap-2 flex-wrap">
                <form method="post" action="{{ route('admin.results.calculate', $campaign) }}">
                    @csrf
                    <button class="rounded-xl border border-ink-200 hover:bg-ink-50 px-4 py-2 text-sm font-medium">
                        🔄 {{ __('Recalculate') }}
                    </button>
                </form>
                @if($resultStatus === 'calculated')
                    <form method="post" action="{{ route('admin.results.approve', $result) }}">
                        @csrf
                        <button class="rounded-xl bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 text-sm font-semibold">
                            ✓ {{ __('Approve') }}
                        </button>
                    </form>
                @endif
                @if($resultStatus === 'approved')
                    <form method="post" action="{{ route('admin.results.announce', $result) }}">
                        @csrf
                        <button class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold">
                            📢 {{ __('Announce') }}
                        </button>
                    </form>
                @endif
                @if($result && $resultStatus !== 'hidden')
                    <form method="post" action="{{ route('admin.results.hide', $result) }}">
                        @csrf
                        <button class="rounded-xl border border-ink-200 text-ink-700 hover:bg-ink-50 px-4 py-2 text-sm font-medium">
                            🔒 {{ __('Hide') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(! $result)
            <div class="py-12 text-center">
                <div class="inline-flex w-16 h-16 rounded-full bg-slate-100 text-slate-400 items-center justify-center text-3xl mb-4">📊</div>
                <div class="text-ink-700 font-semibold">{{ __('No results yet') }}</div>
                <p class="text-sm text-ink-500 mt-2 max-w-md mx-auto">
                    {{ __('Results not calculated yet. Click Recalculate to generate.') }}
                </p>
            </div>
        @else
            {{-- COMMITTEE TIE-BREAK PANEL — shown only when ties exist --}}
            @php
                $tiedByCategory = $result->items->where('needs_committee_decision', true)->groupBy('voting_category_id');
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

                    @foreach($tiedByCategory as $catId => $tiedItems)
                        @php
                            $cat = $campaign->categories->firstWhere('id', $catId);
                            if (!$cat) continue;
                            $confirmedWinners = $result->items->where('voting_category_id', $catId)->where('is_winner', true)->count();
                            $remaining = max(0, (int) $cat->required_picks - $confirmedWinners);
                        @endphp
                        <form method="post" action="/admin/results/{{ $result->id }}/resolve-tie"
                              class="rounded-2xl bg-white border border-amber-200 p-4 mb-3">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $cat->id }}">

                            <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
                                <div>
                                    <div class="font-bold text-ink-900">{{ $cat->localized('title') }}</div>
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
                                        $p = $item->candidate?->player;
                                        $name = $p?->localized('name') ?? $item->candidate?->club?->localized('name');
                                        $club = $p?->club?->localized('name');
                                        $photo = $p?->photo_path ? \Illuminate\Support\Facades\Storage::url($p->photo_path) : null;
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

            {{-- Pitch view for TOS campaigns after approval --}}
            @if($campaign->type?->value === 'team_of_the_season' && in_array($resultStatus, ['approved', 'announced']))
                @php
                    $tosFormation  = \App\Modules\Campaigns\Domain\TeamOfSeasonFormation::fromCampaign($campaign);
                    $winnersBySlot = $result->items->where('is_winner', true)->groupBy('position');
                @endphp
                <div class="mb-6 rounded-3xl overflow-hidden shadow-xl relative"
                     style="background: linear-gradient(to bottom, #065f46, #064e3b); min-height: 480px;">
                    <div class="absolute inset-0 opacity-20"
                         style="background-image: linear-gradient(to right, rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.08) 1px, transparent 1px); background-size: 40px 40px;"></div>

                    <div class="relative z-10 p-5 md:p-8">
                        <div class="text-center text-white mb-6">
                            <div class="text-xs uppercase tracking-wider text-emerald-300">{{ __('Official Lineup') }}</div>
                            <div class="text-2xl font-bold mt-1">{{ $tosFormation['defense'] }}-{{ $tosFormation['midfield'] }}-{{ $tosFormation['attack'] }}</div>
                        </div>

                        @foreach($tosFormation as $slot => $n)
                            <div class="mb-6 last:mb-0">
                                <div class="text-center text-xs text-emerald-200 mb-3 font-semibold uppercase tracking-wider">
                                    {{ __(ucfirst($slot)) }} ({{ $winnersBySlot->get($slot, collect())->count() }}/{{ $n }})
                                </div>
                                <div class="flex flex-wrap justify-center gap-3">
                                    @foreach($winnersBySlot->get($slot, collect())->sortBy('rank') as $item)
                                        <?php
                                            $p = $item->candidate->player;
                                            $photo = $p?->photo_path;
                                        ?>
                                        <div class="w-32 rounded-2xl bg-white p-3 text-center shadow-lg">
                                            <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 overflow-hidden mb-2 flex items-center justify-center text-2xl">
                                                @if($photo)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" class="w-full h-full object-cover" alt="">
                                                @else
                                                    🧍
                                                @endif
                                            </div>
                                            <div class="font-bold text-xs text-gray-900 truncate">{{ $p?->localized('name') }}</div>
                                            <div class="text-xs text-gray-500 truncate">{{ $p?->club?->localized('name') }}</div>
                                            <div class="mt-1.5 text-xs text-emerald-700 font-bold">
                                                {{ $item->votes_count }} · {{ $item->vote_percentage }}%
                                            </div>
                                        </div>
                                    @endforeach
                                    @for($i = $winnersBySlot->get($slot, collect())->count(); $i < $n; $i++)
                                        <div class="w-32 rounded-2xl border-2 border-dashed border-white/30 p-3 text-center opacity-40 text-white text-xs py-8">
                                            {{ __('empty') }}
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <h3 class="font-bold text-ink-800">{{ __('Full ranking per line') }}</h3>
                    <a href="{{ url('/results/'.$campaign->public_token) }}" target="_blank"
                       class="text-sm text-brand-700 hover:underline">
                        {{ __('View public page') }} ↗
                    </a>
                </div>
            @endif

            @foreach($result->items->groupBy('voting_category_id') as $categoryId => $items)
                <?php $category = $items->first()->category; ?>
                <div class="mb-6 last:mb-0">
                    <h3 class="font-semibold text-ink-800 mb-3 flex items-center gap-2">
                        <span>{{ $category->localized('title') }}</span>
                        @if($category->position_slot !== 'any')
                            <span class="text-xs text-ink-500 font-normal">· {{ __(ucfirst($category->position_slot)) }}</span>
                        @endif
                    </h3>
                    <div class="space-y-2.5">
                        @foreach($items->sortBy('rank') as $item)
                            <?php
                                $catTotal = $items->sum('votes_count') ?: 1;
                                $pct = round(($item->votes_count / $catTotal) * 100);
                                $label = $item->candidate->player?->localized('name')
                                      ?? $item->candidate->club?->localized('name');
                                $club = $item->candidate->player?->club?->localized('name');
                            ?>
                            <div class="rounded-xl border p-3 {{ $item->is_winner ? 'border-emerald-500 bg-emerald-50' : 'border-ink-200' }}">
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                                                     {{ $item->is_winner ? 'bg-emerald-600 text-white' : 'bg-ink-100 text-ink-700' }}">
                                            {{ $item->rank }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-semibold truncate">{{ $label }}</div>
                                            @if($club)<div class="text-xs text-ink-500 truncate">{{ $club }}</div>@endif
                                        </div>
                                        @if($item->is_winner)
                                            <span class="ms-1 px-2 py-0.5 rounded-full text-xs bg-emerald-600 text-white font-semibold whitespace-nowrap">★ {{ __('Winner') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-end whitespace-nowrap">
                                        <div class="font-bold">{{ $item->votes_count }}</div>
                                        <div class="text-xs text-ink-500">{{ $pct }}%</div>
                                    </div>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-ink-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $item->is_winner ? 'bg-emerald-500' : 'bg-slate-400' }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- RIGHT: state panel --}}
    <div class="space-y-4">
        {{-- Current visibility big card --}}
        <div class="rounded-3xl border-2 p-6 shadow-sm {{ $visibilityColors[$visibility] ?? '' }}">
            <div class="text-xs font-bold uppercase tracking-wide opacity-70">{{ __('Public visibility') }}</div>
            <div class="text-xl font-bold mt-2">
                {{ $visibilityLabels[$visibility] ?? $visibility }}
            </div>
            <p class="text-sm mt-3 opacity-80 leading-6">
                {{ $visibilityDescriptions[$visibility] ?? '' }}
            </p>
        </div>

        {{-- State details --}}
        <div class="rounded-3xl border border-gray-200 bg-white p-5 space-y-3">
            <h3 class="font-bold text-ink-700">{{ __('Result state') }}</h3>

            <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0">
                <span class="text-sm text-ink-500">{{ __('Calculation') }}</span>
                <span class="font-semibold text-sm">
                    @if($result?->calculated_at)
                        ✓ {{ $result->calculated_at->format('Y-m-d H:i') }}
                    @else
                        — {{ __('Not yet') }}
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0">
                <span class="text-sm text-ink-500">{{ __('Approved') }}</span>
                <span class="font-semibold text-sm">
                    @if($result?->approved_at)
                        ✓ {{ $result->approved_at->format('Y-m-d H:i') }}
                    @else
                        — {{ __('Not yet') }}
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0">
                <span class="text-sm text-ink-500">{{ __('Announced') }}</span>
                <span class="font-semibold text-sm">
                    @if($result?->announced_at)
                        ✓ {{ $result->announced_at->format('Y-m-d H:i') }}
                    @else
                        — {{ __('Not yet') }}
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between py-2">
                <span class="text-sm text-ink-500">{{ __('Total votes') }}</span>
                <span class="font-bold text-lg text-emerald-700">{{ $totalVotes }}</span>
            </div>
        </div>

        {{-- Next-step hint --}}
        @if($resultStatus === 'calculated')
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
                💡 {{ __('Results are calculated. The committee must approve them before they can be announced.') }}
            </div>
        @elseif($resultStatus === 'approved')
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
                ✅ {{ __('Results are approved. Click Announce to make them public.') }}
            </div>
        @elseif($resultStatus === 'announced')
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
                🎉 {{ __('Results are live.') }}
                <a href="{{ url('/results/'.$campaign->public_token) }}" target="_blank" class="font-semibold underline">
                    {{ __('View public page') }} ↗
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
