@props(['campaign'])

@php
    $statusClass = [
        'active'            => 'bg-emerald-100 text-emerald-700',
        'published'         => 'bg-blue-100 text-blue-700',
        'closed'            => 'bg-gray-100 text-gray-700',
        'draft'             => 'bg-amber-100 text-amber-700',
        'pending_approval'  => 'bg-amber-100 text-amber-800',
        'rejected'          => 'bg-rose-100 text-rose-700',
        'archived'          => 'bg-slate-100 text-slate-600',
    ];
    $status = $campaign->status->value;

    $progress = $campaign->max_voters
        ? min(100, (int) round(($campaign->votes_count / $campaign->max_voters) * 100))
        : null;
@endphp

<div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.campaigns.show', $campaign) }}"
               class="text-xl font-bold hover:text-emerald-700 block truncate">
                {{ $campaign->localized('title') }}
            </a>
            <p class="text-sm text-gray-500 mt-1">{{ $campaign->type?->value }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass[$status] ?? '' }}">
            {{ $campaign->status->label() }}
        </span>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-gray-500">
                {{ __('Votes') }}:
                <strong>{{ $campaign->votes_count }}</strong>
                @if($campaign->max_voters)/ {{ $campaign->max_voters }}@endif
            </span>
            @if($progress !== null)
                <span class="font-semibold">{{ $progress }}%</span>
            @endif
        </div>
        @if($progress !== null)
            <div class="w-full h-3 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        @else
            <div class="text-xs text-gray-400">{{ __('No voter cap.') }}</div>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @if(in_array($status, ['draft', 'rejected'], true))
            <a href="{{ route('admin.campaigns.edit', $campaign) }}"
               class="rounded-2xl border-2 border-amber-500 text-amber-700 hover:bg-amber-50 px-4 py-2.5 font-semibold">
                ✏️ {{ __('Edit') }}
            </a>
            <form method="post" action="{{ route('admin.campaigns.submit-approval', $campaign) }}">
                @csrf
                <button class="rounded-2xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 font-semibold">
                    📤 {{ __('Submit for approval') }}
                </button>
            </form>
        @endif

        @if($status === 'published')
            <form method="post" action="{{ route('admin.campaigns.activate', $campaign) }}">
                @csrf
                <button class="btn-save">
                    <span aria-hidden="true">⚡</span>
                    <span>{{ __('Activate') }}</span>
                </button>
            </form>
        @endif

        @if(in_array($status, ['active', 'published'], true))
            <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank" class="btn-ghost">
                {{ __('Public link') }}
            </a>
        @endif

        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn-brand">
            {{ __('Manage') }}
        </a>
    </div>
</div>
