@props([
    'campaign',
    'result',
    'visibility'   => 'hidden',
    'resultStatus' => null,
    'totalVotes'   => 0,
])

@php
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

    $publicResultsUrl = url('/results/'.$campaign->public_token);
@endphp

<div class="space-y-4">
    {{-- Current visibility big card --}}
    <div class="rounded-3xl border-2 p-6 shadow-sm {{ $visibilityColors[$visibility] ?? '' }}">
        <div class="text-xs font-bold uppercase tracking-wide opacity-70">{{ __('Public visibility') }}</div>
        <div class="text-xl font-bold mt-2">{{ $visibilityLabels[$visibility] ?? $visibility }}</div>
        <p class="text-sm mt-3 opacity-80 leading-6">
            {{ $visibilityDescriptions[$visibility] ?? '' }}
        </p>
    </div>

    {{-- State details --}}
    <div class="rounded-3xl border border-gray-200 bg-white p-5 space-y-3">
        <h3 class="font-bold text-ink-700">{{ __('Result state') }}</h3>

        @php
            $rows = [
                ['label' => __('Calculation'), 'at' => $result?->calculated_at],
                ['label' => __('Approved'),    'at' => $result?->approved_at],
                ['label' => __('Announced'),   'at' => $result?->announced_at],
            ];
        @endphp
        @foreach($rows as $row)
            <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0">
                <span class="text-sm text-ink-500">{{ $row['label'] }}</span>
                <span class="font-semibold text-sm">
                    @if($row['at'])
                        ✓ {{ $row['at']->format('Y-m-d H:i') }}
                    @else
                        — {{ __('Not yet') }}
                    @endif
                </span>
            </div>
        @endforeach

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
            <a href="{{ $publicResultsUrl }}" target="_blank" class="font-semibold underline">
                {{ __('View public page') }} ↗
            </a>
        </div>
    @endif
</div>
