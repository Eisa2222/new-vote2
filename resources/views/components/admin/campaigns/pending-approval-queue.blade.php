@props(['pending'])

@if($pending->isNotEmpty() && auth()->user()?->can('campaigns.approve'))
    <div class="rounded-3xl border-2 border-amber-300 bg-gradient-to-{{ app()->getLocale() === 'ar' ? 'l' : 'r' }} from-amber-50 to-rose-50 p-5 md:p-6 mb-6 shadow-sm">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl flex-shrink-0">⏳</div>
            <div class="flex-1">
                <h2 class="font-extrabold text-amber-900 text-lg">
                    {{ __(':n campaign(s) pending your approval', ['n' => $pending->count()]) }}
                </h2>
                <p class="text-sm text-amber-800 mt-0.5">
                    {{ __('Review each campaign and approve or reject. Voting opens only after approval.') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($pending as $campaign)
                <div class="rounded-2xl bg-white border border-amber-200 p-4 flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.campaigns.show', $campaign) }}"
                           class="font-bold text-ink-900 hover:text-brand-700 truncate block">
                            {{ $campaign->localized('title') }}
                        </a>
                        <div class="text-xs text-ink-500 mt-0.5">
                            {{ $campaign->type?->value }}
                            @if($campaign->start_at) · {{ $campaign->start_at->format('Y-m-d') }} @endif
                        </div>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <a href="{{ route('admin.campaigns.show', $campaign) }}"
                           class="rounded-xl border border-ink-200 hover:bg-ink-50 px-3 py-2 text-xs font-semibold">
                            👁 {{ __('Review') }}
                        </a>
                        <form method="post" action="{{ route('admin.campaigns.approve', $campaign) }}" class="inline">
                            @csrf
                            <button class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-xs font-bold">
                                ✓ {{ __('Approve') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
