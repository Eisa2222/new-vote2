@props(['campaign'])

@php
    // Pull the aggregates needed by the view in a single pass.
    $clubRows = $campaign->campaignClubs()->with('club')->get();
    $attachedClubs = $clubRows->count();
    $activeClubs = $clubRows->where('is_active', true)->count();
    $totalVotes = (int) $campaign->votes_count;

    // Per-club quota is the source of truth. Sum across attached clubs;
    // any unlimited row means the combined cap is unlimited too.
    $anyUnlimited = $clubRows->contains(fn($r) => $r->max_voters === null);
    $totalCap = $anyUnlimited ? null : (int) $clubRows->sum('max_voters');

    // "Eligible roster" = active players across attached clubs. This is
    // the theoretical max voter base (one vote per player, per campaign)
    // and the denominator we actually want for turnout — not the admin-set
    // cap, which is usually a hard-limit safety net.
    $eligiblePlayers =
        $attachedClubs > 0
            ? \App\Modules\Players\Models\Player::active()->whereIn('club_id', $clubRows->pluck('club_id'))->count()
            : 0;

    $turnoutPct = $eligiblePlayers > 0 ? min(100, (int) round(($totalVotes / $eligiblePlayers) * 100)) : 0;

    // Countdown target: pre-start → start_at; during live → end_at;
    // ended → null.
    $now = now();
    $phase = 'idle'; // pre | live | ended
    $countdown = null; // ISO string the JS counts down to
    $phaseLabel = __('Starts');
    if ($campaign->start_at > $now) {
        $phase = 'pre';
        $countdown = $campaign->start_at->toIso8601String();
        $phaseLabel = __('Starts');
    } elseif ($campaign->end_at >= $now) {
        $phase = 'live';
        $countdown = $campaign->end_at->toIso8601String();
        $phaseLabel = __('Ends in');
    } else {
        $phase = 'ended';
        $phaseLabel = __('Already ended');
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- 1. Votes —————— hero card with turnout progress. ───────────── --}}
    <div
        class="relative bg-gradient-to-br from-emerald-600 to-emerald-800 text-white rounded-2xl p-5 shadow-lg overflow-hidden">
        <div class="absolute -top-6 -end-6 text-7xl opacity-10">🗳</div>
        <div class="relative">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-white/75">
                {{ __('Total votes received') }}
            </div>
            <div class="text-4xl font-black tabular-nums mt-1 leading-none" data-live-votes>
                {{ number_format($totalVotes) }}
            </div>

            {{-- Denominator chip — shows exact counts so the admin sees
                 "X of Y eligible" instead of a bare percentage. --}}
            <div class="mt-3 text-[11px] text-white/85 tabular-nums">
                @if ($eligiblePlayers > 0)
                    {{ number_format($totalVotes) }} / {{ number_format($eligiblePlayers) }}
                    {{ __('players') }}
                    <span class="text-white/55 mx-1">·</span>
                    <strong class="text-white">{{ $turnoutPct }}%</strong>
                    {{ __('Turnout') }}
                @else
                    {{ __('No eligible players yet') }}
                @endif
            </div>

            @if ($eligiblePlayers > 0)
                <div class="mt-2 h-1.5 rounded-full bg-white/20 overflow-hidden">
                    <div data-live-bar class="h-full bg-white rounded-full transition-all"
                        style="width: {{ $turnoutPct }}%"></div>
                </div>
            @endif
        </div>
    </div>

    {{-- 2. Clubs — attached vs active, with crisp ratio + microcopy. --}}
    <div class="bg-white border border-ink-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Clubs') }}</div>
            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-base">🏟
            </div>
        </div>
        <div class="text-3xl font-extrabold tabular-nums text-ink-900 mt-1">
            {{ $activeClubs }}<span class="text-ink-400 text-lg">/{{ $attachedClubs }}</span>
        </div>
        <div class="text-xs text-ink-500 mt-1.5">{{ __('active of attached') }}</div>
    </div>

    {{-- 3. Quota — combined cap or ∞ when any row is unlimited. ───── --}}
    <div class="bg-white border border-ink-200 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Max voters') }}</div>
            <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-base">👥
            </div>
        </div>
        <div class="text-3xl font-extrabold tabular-nums text-ink-900 mt-1">
            {{ $totalCap !== null ? number_format($totalCap) : '∞' }}
        </div>
        <div class="text-xs text-ink-500 mt-1.5">
            {{ $totalCap !== null ? __('combined across all clubs') : __('unlimited') }}
        </div>
    </div>

    {{--
      4. Live countdown — js ticks once a second so the value is always
      accurate. Falls back to the raw Y-m-d pair on browsers without JS.
    --}}
    <div class="bg-white border border-indigo-200 rounded-2xl p-5 shadow-sm relative overflow-hidden"
        x-data="campaignCountdown({
            phase: @js($phase),
            target: @js($countdown),
            labels: {
                days: @js(__('days')),
                hours: @js(__('hours')),
                mins: @js(__('mins')),
                secs: @js(__('secs')),
                ended: @js(__('Already ended')),
            }
        })" x-init="tick();
        const h = setInterval(() => tick(), 1000);
        document.addEventListener('livewire:navigating', () => clearInterval(h));">
        <div class="flex items-center justify-between">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ $phaseLabel }}</div>
            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-base">⏱
            </div>
        </div>

        {{-- Live D/H/M/S blocks — big numerals so a glance tells the
             admin how much time is left. Pulses subtly on update. --}}
        <div class="mt-2" x-show="phase !== 'ended'">
            <div class="flex items-baseline gap-2 flex-wrap tabular-nums">
                <template x-if="days > 0">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-ink-900" x-text="days"></span>
                        <span class="text-xs text-ink-500" x-text="labels.days"></span>
                    </div>
                </template>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black text-ink-900" x-text="pad(hours)"></span>
                    <span class="text-xs text-ink-500" x-text="labels.hours"></span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black text-ink-900" x-text="pad(minutes)"></span>
                    <span class="text-xs text-ink-500" x-text="labels.mins"></span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black transition"
                        :class="pulse ? 'text-brand-600 scale-110' : 'text-ink-900'" x-text="pad(seconds)"></span>
                    <span class="text-xs text-ink-500" x-text="labels.secs"></span>
                </div>
            </div>
        </div>
        <div x-show="phase === 'ended'" class="text-2xl font-black text-ink-900 mt-1" x-text="labels.ended"></div>

        {{-- Date pair beneath, for context. --}}
        <div class="text-xs text-ink-500 mt-2 tabular-nums">
            {{ $campaign->start_at->format('Y-m-d H:i') }}
            <span class="mx-1 opacity-60">→</span>
            {{ $campaign->end_at->format('Y-m-d H:i') }}
        </div>
    </div>
</div>

{{-- Public voting link + public stats link side-by-side. Admins
     copy the voting link for voters and the stats link for sharing
     the live dashboard (on social, WhatsApp, etc.). --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    {{-- Voting link (for voters) --}}
    {{-- <div class="bg-white border border-ink-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center text-lg flex-shrink-0">🔗</div>
        <div class="flex-1 min-w-0">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{{ __('Public voting link') }}</div>
            <input data-public-link type="text" readonly value="{{ url('/vote/'.$campaign->public_token) }}"
                   onclick="this.select()"
                   class="w-full font-mono text-xs text-ink-800 bg-transparent border-0 p-0 mt-0.5 focus:outline-none">
        </div>
        <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank"
           title="{{ __('Open') }}"
           class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-ink-200 hover:bg-ink-50 text-xs transition flex-shrink-0">↗</a>
        <button type="button"
                onclick="const i=this.parentElement.querySelector('[data-public-link]'); navigator.clipboard.writeText(i.value); this.innerText='✓ {{ __('Copied') }}'; setTimeout(()=>this.innerText='📋 {{ __('Copy') }}', 1500);"
                class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 hover:bg-ink-50 text-ink-700 px-3 py-1.5 text-xs font-semibold transition flex-shrink-0">
            📋 {{ __('Copy') }}
        </button>
    </div> --}}

    {{-- Stats link (sharable dashboard) --}}
    <div class="bg-white border border-indigo-200 rounded-2xl p-4 flex items-center gap-3">
        <div
            class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg flex-shrink-0">
            📊</div>
        <div class="flex-1 min-w-0">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600">
                {{ __('Public stats page') }}</div>
            <input data-stats-link type="text" readonly
                value="{{ route('public.campaigns.stats', $campaign->public_token) }}" onclick="this.select()"
                class="w-full font-mono text-xs text-ink-800 bg-transparent border-0 p-0 mt-0.5 focus:outline-none">
        </div>
        <a href="{{ route('public.campaigns.stats', $campaign->public_token) }}" target="_blank"
            title="{{ __('Open') }}"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-indigo-200 hover:bg-indigo-50 text-indigo-700 text-xs transition flex-shrink-0">↗</a>
        <button type="button"
            onclick="const i=this.parentElement.querySelector('[data-stats-link]'); navigator.clipboard.writeText(i.value); this.innerText='✓ {{ __('Copied') }}'; setTimeout(()=>this.innerText='📋 {{ __('Copy') }}', 1500);"
            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 hover:bg-indigo-50 text-indigo-700 px-3 py-1.5 text-xs font-semibold transition flex-shrink-0">
            📋 {{ __('Copy') }}
        </button>
    </div>
</div>

@push('scripts')
    <script>
        // Per-card Alpine factory: counts down every second. Kept inline here
        // because the stats-grid is the only component that uses it; promoting
        // to a separate JS file would be premature.
        function campaignCountdown({
            phase,
            target,
            labels
        }) {
            return {
                phase,
                target,
                labels,
                days: 0,
                hours: 0,
                minutes: 0,
                seconds: 0,
                pulse: false,
                pad(n) {
                    return String(n).padStart(2, '0');
                },
                tick() {
                    if (this.phase === 'ended' || !this.target) {
                        this.phase = 'ended';
                        return;
                    }
                    const now = new Date();
                    const end = new Date(this.target);
                    let diff = Math.max(0, end - now);
                    if (diff <= 0) {
                        this.phase = 'ended';
                        this.days = this.hours = this.minutes = this.seconds = 0;
                        return;
                    }
                    const d = Math.floor(diff / 86400000);
                    diff -= d * 86400000;
                    const h = Math.floor(diff / 3600000);
                    diff -= h * 3600000;
                    const m = Math.floor(diff / 60000);
                    diff -= m * 60000;
                    const s = Math.floor(diff / 1000);
                    this.days = d;
                    this.hours = h;
                    this.minutes = m;
                    this.seconds = s;
                    this.pulse = true;
                    setTimeout(() => this.pulse = false, 150);
                },
            };
        }
    </script>
@endpush
