@props(['campaign', 'errors' => null])

<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-6 text-white">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 text-xl font-bold mb-4 backdrop-blur">FPA</div>
        <div class="text-xs uppercase tracking-[0.25em] text-accent-500">{{ __('Official Platform') }}</div>
        <h1 class="text-2xl md:text-3xl font-bold mt-2">{{ $campaign->localized('title') }}</h1>
        <p class="text-brand-100 text-sm mt-3">{{ __('Identity verification required to vote') }}</p>
    </div>

    <div class="bg-white rounded-3xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-ink-900">{{ __('Verify your identity') }}</h2>
        <p class="text-sm text-ink-500 mt-1">{{ __('Enter your national ID OR mobile number to continue.') }}</p>

        @if($errors && $errors->any())
            <div class="mt-4 rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('voting.verify', $campaign->public_token) }}" class="mt-5 space-y-4"
              x-data="{ sending: false }" @submit="sending = true">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('National ID') }}</label>
                <input type="text" name="national_id" inputmode="numeric" autocomplete="off"
                       value="{{ old('national_id') }}"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500"
                       placeholder="1xxxxxxxxx">
            </div>

            <div class="flex items-center gap-3 text-xs text-ink-500">
                <span class="h-px flex-1 bg-ink-200"></span>
                {{ __('OR') }}
                <span class="h-px flex-1 bg-ink-200"></span>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Mobile number') }}</label>
                <input type="tel" name="mobile" inputmode="tel" autocomplete="off"
                       value="{{ old('mobile') }}"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500"
                       placeholder="05xxxxxxxx">
            </div>

            <button type="submit" :disabled="sending"
                    class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white py-3.5 font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!sending">{{ __('Continue') }}</span>
                <span x-show="sending">{{ __('Verifying...') }}</span>
            </button>

            <p class="text-xs text-ink-500 text-center mt-2">
                {{ __('Your identity is used only to authenticate your vote and is never published.') }}
            </p>
        </form>
    </div>

    <div class="text-center mt-6 text-xs text-brand-200">
        © {{ date('Y') }} {{ __('Saudi Football Players Association') }}
    </div>
</div>
