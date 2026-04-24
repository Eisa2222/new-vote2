@props(['campaign'])

{{-- Lifecycle-aware banner at the top of the campaign show page. Covers
     the four stages that need a call-to-action: Draft/Rejected (submit
     for approval), PendingApproval (committee Approve/Reject), Published
     (activate), Active/Published (close/archive). --}}

@php $status = $campaign->status->value; @endphp

@if (in_array($status, ['draft', 'rejected'], true))
    <div class="rounded-3xl bg-gradient-to-r from-amber-50 to-emerald-50 border-2 border-amber-300 p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0">
                    {{ $status === 'rejected' ? '❌' : '⚠️' }}
                </div>
                <div>
                    <h3 class="font-bold text-amber-900 text-lg">
                        {{ $status === 'rejected' ? __('Rejected by committee') : __('This campaign is a draft') }}
                    </h3>
                    <p class="text-sm text-amber-800 mt-1">
                        {{ __('Submit the campaign to the committee for approval. Voting will be open only after approval.') }}
                    </p>
                    @if ($campaign->committee_rejection_note)
                        <div class="mt-3 rounded-xl bg-rose-50 border border-rose-200 p-3 text-sm text-rose-800">
                            <strong>{{ __('Rejection note') }}:</strong> {{ $campaign->committee_rejection_note }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0 flex-wrap">
                <a href="{{ route('admin.campaigns.edit', $campaign) }}"
                    class="rounded-2xl border-2 border-amber-500 text-amber-700 hover:bg-amber-100 px-6 py-3 font-semibold">
                    ✏️ {{ __('Edit') }}
                </a>
                <form method="post" action="{{ route('admin.campaigns.submit-approval', $campaign) }}">
                    @csrf
                    <button
                        class="rounded-2xl bg-brand-600 hover:bg-brand-700 text-white px-8 py-3 font-semibold text-lg shadow-brand">
                        📤 {{ __('Submit for committee approval') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($status === 'pending_approval')
    <div class="rounded-3xl bg-amber-50 border-2 border-amber-300 p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0">
                    ⏳</div>
                <div>
                    <h3 class="font-bold text-amber-900 text-lg">{{ __('Pending committee approval') }}</h3>
                    <p class="text-sm text-amber-800 mt-1">
                        {{ __('Awaiting review by a Voting Committee member before voting can be activated.') }}
                    </p>
                </div>
            </div>

            @can('campaigns.approve')
                {{--
                  Inline Tailwind classes instead of @apply-based
                  component classes: the Tailwind Play CDN occasionally
                  misses custom `.btn-*` under @apply, leaving the
                  markup as plain text. The utility classes below are
                  guaranteed to ship with the CDN bundle so the
                  buttons always render properly.
                --}}
                <div class="flex gap-2 flex-wrap">
                    <form method="post" action="{{ route('admin.campaigns.approve', $campaign) }}">
                        @csrf
                        <button
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white px-5 py-2.5 font-semibold shadow-sm transition">
                            <span aria-hidden="true">✓</span>
                            <span>{{ __('Approve') }}</span>
                        </button>
                    </form>
                    <form method="post" action="{{ route('admin.campaigns.reject', $campaign) }}"
                        onsubmit="this.querySelector('[name=reason]').value = prompt('{{ __('Reason for rejection (optional):') }}') || ''">
                        @csrf
                        <input type="hidden" name="reason">
                        <button
                            class="inline-flex items-center gap-2 rounded-xl border-2 border-rose-400 text-rose-700 bg-white hover:bg-rose-50 active:bg-rose-100 px-5 py-2.5 font-semibold shadow-sm transition">
                            <span aria-hidden="true">✗</span>
                            <span>{{ __('Reject') }}</span>
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
@endif

@if ($status === 'published')
    <div
        class="rounded-2xl bg-blue-50 border border-blue-200 p-5 mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⏰</span>
            <div>
                <div class="font-semibold text-blue-900">{{ __('Published — waiting for start time') }}</div>
                <div class="text-sm text-blue-700">{{ __('Start at') }}:
                    {{ $campaign->start_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
        <form method="post" action="{{ route('admin.campaigns.activate', $campaign) }}">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                       text-white text-sm font-semibold px-5 py-2.5 shadow-sm
                       transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <span aria-hidden="true">⚡</span>
                <span>{{ __('Activate now') }}</span>
            </button>
        </form>
    </div>
@endif

@if (in_array($status, ['active', 'published'], true))
    <div class="flex gap-2 mb-6">
        <form method="post" action="{{ route('admin.campaigns.close', $campaign) }}">
            @csrf
            <button class="rounded-xl text-rose-700 border border-rose-300 hover:bg-rose-50 px-4 py-2 font-medium">
                🛑 {{ __('Close campaign') }}
            </button>
        </form>
        <form method="post" action="{{ route('admin.campaigns.archive', $campaign) }}">
            @csrf
            <button class="rounded-xl text-slate-600 border border-slate-300 hover:bg-slate-50 px-4 py-2 font-medium"
                onclick="return confirm('{{ __('Archive this campaign?') }}')">
                📁 {{ __('Archive') }}
            </button>
        </form>
    </div>
@endif
