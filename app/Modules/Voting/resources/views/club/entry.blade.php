@extends('voting::club._layout')
@section('title', __('Cast your vote'))

@section('content')
<div class="card max-w-2xl mx-auto space-y-6">
    <div class="text-center space-y-2">
        <div class="text-sm uppercase tracking-widest text-brand-700 font-semibold">{{ $campaign->localized('title') }}</div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900">
            {{ $club->localized('name') }}
        </h1>
        <p class="text-ink-500 text-sm">
            {{ __('Pick your name from the list below to start voting.') }}
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($players->isEmpty())
        <div class="rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 p-4 text-sm text-center">
            {{ __('No eligible players found for this club. Please contact the organisers.') }}
        </div>
    @else
        <form method="post" action="{{ route('voting.club.start', $row->voting_link_token) }}" class="space-y-4"
              x-data="{ selected: '' }">
            @csrf
            <div>
                <label class="field-label">{{ __('Your name') }}</label>
                <select name="player_id" required x-model="selected" class="field-select text-base">
                    <option value="">— {{ __('Select your name') }} —</option>
                    @foreach($players as $p)
                        <option value="{{ $p->id }}">{{ $p->localized('name') }} @if($p->jersey_number) (#{{ $p->jersey_number }}) @endif</option>
                    @endforeach
                </select>
            </div>

            <button class="btn-save w-full justify-center py-3" :disabled="!selected">
                <span aria-hidden="true">🗳</span>
                <span>{{ __('Start voting') }}</span>
            </button>

            <p class="text-xs text-ink-500 text-center pt-2">
                {{ __('By proceeding you confirm that you are the selected player.') }}
            </p>
        </form>
    @endif
</div>

<div class="max-w-2xl mx-auto mt-6 text-center text-xs text-ink-500">
    {{ __('Questions? Contact the organisers at') }}
    <a href="mailto:contact@sfpa.sa" class="text-brand-700 font-semibold">contact@sfpa.sa</a>
</div>
@endsection
