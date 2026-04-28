@extends('voting::club._layout')
@section('title', __('Your contact details'))

@section('content')
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- ── Hero ─────────────────────────────────────────────── --}}
        <div class="relative rounded-3xl overflow-hidden shadow-xl">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-brand-700"></div>
            <div class="absolute inset-0 opacity-15"
                style="background-image: radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 28px 28px;">
            </div>
            <div class="relative p-8 md:p-10 text-center text-white">
                <div
                    class="inline-flex w-20 h-20 rounded-3xl bg-white/15 backdrop-blur items-center justify-center text-5xl mb-3">
                    📬
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold">{{ __('Stay in the loop') }}</h1>
                <p class="text-white/90 mt-3 leading-7 max-w-lg mx-auto">
                    {{ __('Share your contact details so we can notify you the moment the committee announces the winners. All fields are optional — skip if you prefer.') }}
                </p>
            </div>
        </div>

        {{-- Voter identity embedded so they know the data lands on their
         own player record — not a generic mailing list. --}}
        @if (isset($player) && $player)
            @include('voting::club._partials.voter-card', ['voter' => $player, 'club' => $club])
        @endif

        {{-- ── Form card ────────────────────────────────────────── --}}
        <div class="card space-y-5">
            {{-- Show any errors NOT attached to a specific field at the
             top; per-field errors render below each input. --}}
            @if ($errors->has('*') && !$errors->has('mobile_number') && !$errors->has('email'))
                <div class="alert alert-error">
                    <span class="text-lg leading-none">⚠️</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="post" action="{{ route('voting.club.profile.save', $row->voting_link_token) }}"
                class="space-y-4">
                @csrf

                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-ink-900 mb-1.5">
                        <span
                            class="w-6 h-6 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center text-xs">📱</span>
                        <span>{{ __('Mobile number') }}</span>
                        <span class="text-xs font-normal text-ink-400">({{ __('optional') }})</span>
                    </label>
                    <input type="tel" name="mobile_number" value="{{ old('mobile_number', $player?->mobile_number) }}"
                        inputmode="tel" autocomplete="tel" placeholder="05XXXXXXXX"
                        class="w-full rounded-xl border-2 {{ $errors->has('mobile_number') ? 'border-rose-400' : 'border-ink-200' }} bg-white px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    @error('mobile_number')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-ink-900 mb-1.5">
                        <span
                            class="w-6 h-6 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center text-xs">✉️</span>
                        <span>{{ __('Email') }}</span>
                        <span class="text-xs font-normal text-ink-400">({{ __('optional') }})</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email', $player?->email) }}" autocomplete="email"
                        placeholder="example@domain.com"
                        class="w-full rounded-xl border-2 {{ $errors->has('email') ? 'border-rose-400' : 'border-ink-200' }} bg-white px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- National ID field removed (2026-04) — voters
                     should never need to type their NID into a public
                     form. Mobile + email are sufficient for the
                     winner-notification flow. The DB column stays so
                     admin-entered values are preserved. --}}


                <div class="flex items-center justify-between gap-2 flex-wrap pt-2">
                    <p class="text-xs text-ink-500 flex items-center gap-1">
                        🔒 {{ __('Your contact information is used only to announce results.') }}
                    </p>
                    <div class="flex items-center gap-2">
                        {{-- <a href="{{ route('public.campaigns') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-4 py-2.5 text-sm font-medium transition">
                        {{ __('Skip') }}
                    </a> --}}
                        <button
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                            <span>{{ __('Save & finish') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
