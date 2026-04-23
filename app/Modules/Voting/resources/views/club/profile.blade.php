@extends('voting::club._layout')
@section('title', __('Your contact details'))

@section('content')
<div class="card max-w-xl mx-auto space-y-5">
    <div class="text-center">
        <div class="text-5xl">📬</div>
        <h1 class="text-2xl font-extrabold text-ink-900 mt-2">{{ __('Stay in the loop') }}</h1>
        <p class="text-ink-500 text-sm mt-1">{{ __('All fields are optional. Leave blank to skip.') }}</p>
    </div>

    @if($errors->any())
        <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('voting.club.profile.save', $row->voting_link_token) }}" class="space-y-4">
        @csrf
        <div>
            <label class="field-label">{{ __('Mobile number') }}</label>
            <input type="tel" name="mobile_number" value="{{ old('mobile_number', $player?->mobile_number) }}"
                   inputmode="tel" autocomplete="tel" placeholder="05XXXXXXXX"
                   class="field-input">
        </div>
        <div>
            <label class="field-label">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $player?->email) }}"
                   autocomplete="email" placeholder="example@domain.com"
                   class="field-input">
        </div>
        <div>
            <label class="field-label">{{ __('National ID') }}</label>
            <input type="text" name="national_id" value="{{ old('national_id', $player?->national_id) }}"
                   inputmode="numeric" placeholder="1XXXXXXXXX" class="field-input">
        </div>

        <div class="flex items-center gap-2 pt-2">
            <button class="btn-save">
                <span aria-hidden="true">💾</span>
                <span>{{ __('Save & finish') }}</span>
            </button>
            <a href="{{ route('public.campaigns') }}" class="btn-ghost">{{ __('Skip') }}</a>
        </div>
    </form>
</div>
@endsection
