@extends('voting::club._layout')
@section('title', __('Thank you'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- ── Headline ─────────────────────────────────────────── --}}
    <div class="card text-center space-y-3">
        <div class="text-6xl">🎉</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900">{{ __('Your vote has been recorded') }}</h1>
        <p class="text-ink-500">
            {{ __('Thank you for participating in :title.', ['title' => $campaign->localized('title')]) }}
        </p>
    </div>

    @isset($picks)
        {{-- ── Picks recap — shown once, the first time the user
             lands on success after submit. `with(...)` puts it in the
             flash bag so a refresh won't re-show it. ────────────── --}}

        {{-- Individual awards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $awardCards = [
                    ['title' => __('Best Saudi Player'),   'icon' => '🏆', 'player' => $picks['saudi']],
                    ['title' => __('Best Foreign Player'), 'icon' => '🌍', 'player' => $picks['foreign']],
                ];
            @endphp
            @foreach($awardCards as $c)
                @if($c['player'])
                    <div class="card !p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="text-3xl">{{ $c['icon'] }}</div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wider text-ink-500">{{ $c['title'] }}</div>
                                <div class="text-lg font-bold text-ink-900 leading-tight">{{ $c['player']->localized('name') }}</div>
                                <div class="text-xs text-ink-500">
                                    {{ $c['player']->club?->localized('name') }}
                                    @if($c['player']->jersey_number) · #{{ $c['player']->jersey_number }} @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Team of the Season pitch recap --}}
        @if(! empty($picks['lineup']))
            <div class="card space-y-4">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚽</span>
                    <h2 class="text-lg font-bold">{{ __('Your Team of the Season') }}</h2>
                </div>

                <div class="rounded-3xl bg-gradient-to-b from-brand-700 to-brand-900 p-6 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10"
                         style="background: repeating-linear-gradient(180deg, #fff 0 2px, transparent 2px 60px);"></div>

                    @php
                        $slotMeta = [
                            'attack'     => ['icon' => '⚡', 'label' => __('Attack')],
                            'midfield'   => ['icon' => '⚙️', 'label' => __('Midfield')],
                            'defense'    => ['icon' => '🛡', 'label' => __('Defense')],
                            'goalkeeper' => ['icon' => '🧤', 'label' => __('Goalkeeper')],
                        ];
                    @endphp
                    @foreach(['attack','midfield','defense','goalkeeper'] as $slot)
                        @php $players = $picks['lineup'][$slot] ?? collect(); @endphp
                        <div class="relative mb-3 last:mb-0">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-white/80 mb-2 flex items-center gap-1">
                                <span>{{ $slotMeta[$slot]['icon'] }}</span>
                                <span>{{ $slotMeta[$slot]['label'] }}</span>
                            </div>
                            <div class="grid gap-2" style="grid-template-columns: repeat({{ count($players) }}, minmax(0, 1fr));">
                                @foreach($players as $p)
                                    <div class="rounded-xl bg-white/10 backdrop-blur border border-white/30 p-3 text-center text-white">
                                        <div class="font-bold text-xs truncate">{{ $p->localized('name') }}</div>
                                        <div class="text-[10px] text-white/80 truncate">{{ $p->club?->localized('name') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endisset

    {{-- ── CTA → optional profile capture ─────────────────────── --}}
    <div class="card text-start space-y-4">
        <div class="rounded-2xl bg-brand-50 border border-brand-200 p-5 text-sm text-brand-800">
            <div class="font-bold mb-1">📬 {{ __('Be the first to know when results are announced') }}</div>
            <div class="text-brand-700">
                {{ __('Share your contact details below (optional) and we will let you know the moment the committee announces the winners.') }}
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('voting.club.profile', $row->voting_link_token) }}" class="btn-save">
                <span aria-hidden="true">✉️</span>
                <span>{{ __('Add my contact details') }}</span>
            </a>
            <a href="{{ route('public.campaigns') }}" class="btn-ghost">
                <span>{{ __('Finish') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection
