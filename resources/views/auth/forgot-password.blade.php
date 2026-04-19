@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Forgot password') }} — FPA</title>
    @include('partials.brand-head')
</head>
<body class="bg-ink-50 min-h-screen flex flex-col">
<main class="flex-1 bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-md rounded-3xl overflow-hidden shadow-2xl bg-white p-8 md:p-10">
        <div class="mb-6">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-xl bg-brand-700 text-white flex items-center justify-center font-bold">FPA</div>
                <div class="text-xs uppercase tracking-[0.25em] text-accent-600">SFPA VOTING</div>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-ink-900">{{ __('Forgot password?') }}</h1>
            <p class="text-ink-500 mt-2 text-sm">{{ __('Enter your email and we will send you a reset link.') }}</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-2xl bg-brand-50 border border-brand-200 text-brand-800 p-3 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="fpEmail" class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Email') }}</label>
                <input id="fpEmail" type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" inputmode="email" spellcheck="false"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                       placeholder="example@domain.com">
            </div>
            <button class="w-full btn-save justify-center py-3.5 text-base">
                {{ __('Send reset link') }}
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" class="text-brand-700 font-semibold hover:underline">
                ← {{ __('Back to sign in') }}
            </a>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-ink-200 text-center text-xs text-ink-500 py-4 px-4">
    © {{ date('Y') }} {{ __('Saudi Football Players Association') }}
</footer>
</body>
</html>
