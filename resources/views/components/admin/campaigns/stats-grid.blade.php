@props(['campaign'])

@php
    // Pull a handful of aggregates in one pass so the view stays
    // dumb. These are admin-only and campaigns are small (< a few
    // dozen clubs), so the extra queries are fine.
    $clubRows       = $campaign->campaignClubs()->with('club')->get();
    $attachedClubs  = $clubRows->count();
    $activeClubs    = $clubRows->where('is_active', true)->count();
    $totalVotes     = (int) $campaign->votes_count;

    // Per-club quota is the new source of truth (campaign-level
    // max_voters was removed). Sum across attached clubs; if any
    // row is unlimited, treat the total cap as unlimited.
    $anyUnlimited = $clubRows->contains(fn ($r) => $r->max_voters === null);
    $totalCap     = $anyUnlimited ? null : (int) $clubRows->sum('max_voters');

    $progressPct  = ($totalCap && $totalCap > 0)
        ? min(100, (int) round(($totalVotes / $totalCap) * 100))
        : null;

    // Eligible roster: active players across attached clubs = the
    // theoretical maximum voter base for this campaign.
    $eligiblePlayers = $attachedClubs > 0
        ? \App\Modules\Players\Models\Player::active()
            ->whereIn('club_id', $clubRows->pluck('club_id'))
            ->count()
        : 0;

    $participation = $eligiblePlayers > 0
        ? (int) round(($totalVotes / $eligiblePlayers) * 100)
        : 0;

    // Time status — "ends in X days", "ended N days ago", "starts in X"
    $now       = now();
    $timeLabel = null;
    $timeHint  = null;
    if ($campaign->start_at > $now) {
        $timeLabel = $campaign->start_at->diffForHumans($now, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        $timeHint  = __('Starts in');
    } elseif ($campaign->end_at >= $now) {
        $timeLabel = $campaign->end_at->diffForHumans($now, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        $timeHint  = __('Ends in');
    } else {
        $timeLabel = $campaign->end_at->diffForHumans($now, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
        $timeHint  = __('Ended');
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- 1. Votes with progress bar (tracks the summed per-club caps) --}}
    <div class="relative bg-gradient-to-br from-emerald-600 to-emerald-800 text-white rounded-2xl p-5 shadow-lg overflow-hidden">
        <div class="absolute -top-6 -end-6 text-7xl opacity-10">🗳</div>
        <div class="relative">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-white/75">{{ __('Votes') }}</div>
            <div class="text-3xl font-extrabold tabular-nums mt-1" data-live-votes>{{ $totalVotes }}</div>
            @if($progressPct !== null)
                <div class="mt-3 h-1.5 rounded-full bg-white/20 overflow-hidden">
                    <div data-live-bar class="h-full bg-white rounded-full transition-all"
                         style="width: {{ $progressPct }}%"></div>
                </div>
                <div class="text-[11px] text-white/80 mt-1.5 tabular-nums">
                    {{ $progressPct }}% {{ __('of total cap') }}
                    <span class="text-white/60">({{ number_format($totalCap) }})</span>
                </div>
            @else
                <div class="text-[11px] text-white/80 mt-3">∞ {{ __('unlimited') }}</div>
            @endif
        </div>
    </div>

    {{-- 2. Participation: voters / eligible players --}}
    <div class="bg-white border border-ink-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Participation') }}</div>
            <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-base">👥</div>
        </div>
        <div class="text-3xl font-extrabold tabular-nums text-ink-900 mt-1">{{ $participation }}<span class="text-lg text-ink-400">%</span></div>
        <div class="text-xs text-ink-500 mt-1.5 tabular-nums">
            {{ number_format($totalVotes) }} / {{ number_format($eligiblePlayers) }} {{ __('players') }}
        </div>
    </div>

    {{-- 3. Clubs attached vs active --}}
    <div class="bg-white border border-ink-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Clubs') }}</div>
            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-base">🏟</div>
        </div>
        <div class="text-3xl font-extrabold tabular-nums text-ink-900 mt-1">
            {{ $activeClubs }}<span class="text-ink-400 text-lg">/{{ $attachedClubs }}</span>
        </div>
        <div class="text-xs text-ink-500 mt-1.5">{{ __('active of attached') }}</div>
    </div>

    {{-- 4. Time status — live "ends in X" / "ended N ago" --}}
    <div class="bg-white border border-ink-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ $timeHint }}</div>
            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-base">⏱</div>
        </div>
        <div class="text-2xl font-extrabold text-ink-900 mt-1 leading-tight truncate">{{ $timeLabel }}</div>
        <div class="text-xs text-ink-500 mt-1.5 tabular-nums">
            {{ $campaign->start_at->format('Y-m-d') }} → {{ $campaign->end_at->format('Y-m-d') }}
        </div>
    </div>
</div>

{{-- Public voting link — full-width row on its own so the URL has
     breathing room and can't push the stat cards around. --}}
<div class="bg-white border border-ink-200 rounded-2xl p-4 mb-6 flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-lg flex-shrink-0">🔗</div>
    <div class="flex-1 min-w-0">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Public voting link') }}</div>
        <input data-public-link type="text" readonly value="{{ url('/vote/'.$campaign->public_token) }}"
               onclick="this.select()"
               class="w-full font-mono text-xs text-ink-800 bg-transparent border-0 p-0 mt-0.5 focus:outline-none">
    </div>
    <button type="button"
            onclick="const i=this.parentElement.querySelector('[data-public-link]'); navigator.clipboard.writeText(i.value); this.innerText='✓ {{ __('Copied') }}'; setTimeout(()=>this.innerText='📋 {{ __('Copy') }}', 1500);"
            class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 hover:bg-ink-50 text-ink-700 px-3 py-1.5 text-xs font-semibold transition flex-shrink-0">
        📋 {{ __('Copy') }}
    </button>
</div>
