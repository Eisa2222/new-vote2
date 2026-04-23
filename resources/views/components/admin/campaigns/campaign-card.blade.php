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

    // Localized type label — was showing the raw enum key (e.g.
    // "individual_award") because $campaign->type is a bare-backed
    // enum without a label() method on the old cases.
    $typeLabels = [
        'individual_award'   => __('Individual award'),
        'team_award'         => __('Team award'),
        'team_of_the_season' => __('Team of the Season'),
    ];
    $typeLabel = $typeLabels[$campaign->type?->value] ?? ucfirst(str_replace('_', ' ', (string) $campaign->type?->value));

    $progress = $campaign->max_voters
        ? min(100, (int) round(($campaign->votes_count / $campaign->max_voters) * 100))
        : null;
@endphp

<div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.campaigns.show', $campaign) }}"
               class="text-xl font-bold hover:text-brand-700 block truncate">
                {{ $campaign->localized('title') }}
            </a>
            <p class="text-sm text-gray-500 mt-1">{{ $typeLabel }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap {{ $statusClass[$status] ?? '' }}">
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
                <div class="h-full bg-brand-500 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        @else
            <div class="text-xs text-gray-400">{{ __('No voter cap.') }}</div>
        @endif
    </div>

    {{--
      Action row — every button now carries an explicit border + bg so
      they read as buttons, not text links. Layout: lifecycle actions
      first (Edit / Submit / Activate), then utilities (Public link,
      Club links), and finally the primary "Manage" CTA pushed to the
      end of the row.
    --}}
    <div class="mt-6 pt-4 border-t border-ink-100 flex flex-wrap items-center gap-2">
        @if(in_array($status, ['draft', 'rejected'], true))
            <a href="{{ route('admin.campaigns.edit', $campaign) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 text-amber-700 hover:bg-amber-50 px-4 py-2 text-sm font-semibold transition">
                <span aria-hidden="true">✏️</span>
                <span>{{ __('Edit') }}</span>
            </a>
            <form method="post" action="{{ route('admin.campaigns.submit-approval', $campaign) }}">
                @csrf
                <button class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                    <span aria-hidden="true">📤</span>
                    <span>{{ __('Submit for approval') }}</span>
                </button>
            </form>
        @endif

        @if($status === 'published')
            <form method="post" action="{{ route('admin.campaigns.activate', $campaign) }}">
                @csrf
                <button class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition">
                    <span aria-hidden="true">⚡</span>
                    <span>{{ __('Activate') }}</span>
                </button>
            </form>
        @endif

        @if(in_array($status, ['active', 'published'], true))
            <a href="{{ url('/vote/'.$campaign->public_token) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl border border-ink-200 bg-white hover:bg-ink-50 text-ink-700 px-4 py-2 text-sm font-medium transition">
                <span aria-hidden="true">🌐</span>
                <span>{{ __('Public link') }}</span>
            </a>
        @endif

        <a href="{{ route('admin.campaigns.clubs.index', $campaign) }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-brand-300 bg-brand-50 text-brand-700 hover:bg-brand-100 px-4 py-2 text-sm font-semibold transition">
            <span aria-hidden="true">🔗</span>
            <span>{{ __('Club voting links') }}</span>
        </a>

        <a href="{{ route('admin.campaigns.show', $campaign) }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-ink-900 hover:bg-ink-800 text-white px-4 py-2 text-sm font-semibold shadow-sm ms-auto transition">
            <span aria-hidden="true">⚙️</span>
            <span>{{ __('Manage') }}</span>
        </a>
    </div>
</div>
