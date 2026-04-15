@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Identity verification') }} — {{ $campaign->localized('title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: '{{ $locale === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-900 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-6 text-white">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-600 text-3xl mb-4">🗳️</div>
        <h1 class="text-2xl font-bold">{{ $campaign->localized('title') }}</h1>
        <p class="text-slate-300 text-sm mt-2">{{ __('Identity verification required to vote') }}</p>
    </div>

    <div class="bg-white rounded-3xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-gray-900">{{ __('Verify your identity') }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ __('Enter your national ID OR mobile number to continue.') }}</p>

        @if($errors->any())
            <div class="mt-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('voting.verify', $campaign->public_token) }}" class="mt-5 space-y-4" id="verifyForm">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('National ID') }}</label>
                <input type="text" name="national_id" inputmode="numeric" autocomplete="off"
                       value="{{ old('national_id') }}"
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="1xxxxxxxxx">
            </div>

            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="h-px flex-1 bg-gray-200"></span>
                {{ __('OR') }}
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('Mobile number') }}</label>
                <input type="tel" name="mobile" inputmode="tel" autocomplete="off"
                       value="{{ old('mobile') }}"
                       class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="05xxxxxxxx">
            </div>

            <button type="submit" id="continueBtn"
                    class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed">
                {{ __('Continue') }}
            </button>

            <p class="text-xs text-gray-400 text-center mt-2">
                {{ __('Your identity is used only to authenticate your vote and is never published.') }}
            </p>
        </form>
    </div>
</div>

<script>
    document.getElementById('verifyForm').addEventListener('submit', function (e) {
        const btn = document.getElementById('continueBtn');
        btn.disabled = true;
        btn.textContent = '{{ __('Verifying...') }}';
    });
</script>
</body>
</html>
