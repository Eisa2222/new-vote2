@extends('layouts.admin')
@section('content')
@push('scripts')
<script>
    (function () {
        const id = {{ $campaign->id }};
        async function poll() {
            try {
                const r = await fetch(`/admin/campaigns/${id}/stats`, { headers: { 'Accept': 'application/json' }});
                const { data } = await r.json();
                document.getElementById('liveVotes').textContent = data.votes_count;
                const bar = document.getElementById('liveBar');
                if (bar && data.percentage != null) bar.style.width = data.percentage + '%';
            } catch (e) {}
        }
        setInterval(poll, 7000);
    })();
</script>
@endpush
    <div class="flex items-start justify-between mb-6">
        <div>
            <a href="/admin/campaigns" class="text-sm text-slate-500 hover:underline">← {{ __('Campaigns') }}</a>
            <h1 class="text-2xl font-bold text-slate-800 mt-1">{{ $campaign->localized('title') }}</h1>
            <div class="mt-2 flex gap-2 items-center">
                <span class="badge-{{ $campaign->status->value }}">{{ $campaign->status->value }}</span>
                <span class="text-sm text-slate-500">{{ $campaign->type->value }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            @if(in_array($campaign->status->value, ['draft']))
                <form method="post" action="/admin/campaigns/{{ $campaign->id }}/publish">@csrf
                    <button class="btn-primary">{{ __('Publish') }}</button>
                </form>
            @endif
            @if(in_array($campaign->status->value, ['active','published']))
                <form method="post" action="/admin/campaigns/{{ $campaign->id }}/close">@csrf
                    <button class="text-rose-600 border border-rose-300 hover:bg-rose-50 px-4 py-2 rounded-lg">{{ __('Close') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-slate-500 text-sm">{{ __('Votes') }}</div>
            <div id="liveVotes" class="text-2xl font-bold text-emerald-600">{{ $campaign->votes_count }}</div>
            @if($campaign->max_voters)
                <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                    <div id="liveBar" class="h-full bg-emerald-500 rounded-full" style="width: {{ min(100, round(($campaign->votes_count / $campaign->max_voters) * 100)) }}%"></div>
                </div>
            @endif
        </div>
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-slate-500 text-sm">{{ __('Max voters') }}</div>
            <div class="text-2xl font-bold">{{ $campaign->max_voters ?? '∞' }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4 col-span-2">
            <div class="text-slate-500 text-sm">{{ __('Public voting link') }}</div>
            <div class="mt-1 flex items-center gap-2">
                <input id="publink" type="text" readonly value="{{ url('/vote/'.$campaign->public_token) }}"
                       class="flex-1 border rounded px-2 py-1 text-sm">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('publink').value); this.innerText='✓'"
                        class="btn-ghost text-sm">{{ __('Copy') }}</button>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-4">
        <a href="/admin/campaigns/{{ $campaign->id }}/categories"
           class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 font-medium">
            + {{ __('Manage categories & candidates') }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="font-semibold text-slate-800 mb-4">{{ __('Categories') }}</h2>
        @foreach($campaign->categories as $cat)
            <div class="border rounded-lg p-4 mb-3">
                <div class="flex justify-between items-center">
                    <h3 class="font-medium">{{ $cat->localized('title') }}</h3>
                    <span class="text-xs text-slate-500">
                        {{ __('Pick exactly :n', ['n' => $cat->required_picks]) }}
                        @if($cat->position_slot !== 'any') · {{ __(ucfirst($cat->position_slot)) }} @endif
                    </span>
                </div>
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach($cat->candidates as $cand)
                        <div class="text-sm text-slate-600 border rounded p-2">
                            {{ $cand->player?->localized('name') ?? $cand->club?->localized('name') }}
                            @if($cand->player)<span class="text-xs text-slate-400">({{ $cand->player->club?->localized('name') }})</span>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
