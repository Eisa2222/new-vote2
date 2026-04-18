@props([
    'campaign',
    'result',
    'resultStatus' => null,
])

{{-- Lifecycle action buttons: recalculate / approve / announce / hide.
     Appears at the top of the ranking card; each button reveals only
     for the appropriate stage. --}}
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
