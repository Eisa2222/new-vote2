@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Reset password') }} — FPA</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="bg-ink-50 min-h-screen flex flex-col">
<main class="flex-1 bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-md rounded-3xl overflow-hidden shadow-2xl bg-white p-8 md:p-10">
        <h1 class="text-2xl md:text-3xl font-bold text-ink-900 mb-2">{{ __('Set a new password') }}</h1>
        <p class="text-ink-500 mb-6 text-sm">{{ __('Minimum 10 characters with upper & lower case, numbers and a symbol.') }}</p>

        @if ($errors->any())
            <div class="mb-4 rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 bg-ink-50 text-ink-700">
            </div>

            <div x-data="{ show: false }">
                <label for="np" class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('New password') }}</label>
                <div class="relative">
                    <input id="np" name="password" required :type="show ? 'text' : 'password'"
                           autocomplete="new-password"
                           class="w-full rounded-xl border border-ink-200 px-4 py-3 {{ $dir === 'rtl' ? 'pl-12' : 'pr-12' }} focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 {{ $dir === 'rtl' ? 'left-0' : 'right-0' }} flex items-center px-3 text-ink-500 hover:text-brand-700">
                        <svg x-show="!show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="show" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" required
                       autocomplete="new-password"
                       class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>

            <button class="w-full btn-save justify-center py-3.5 text-base">
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</main>
<footer class="bg-white border-t border-ink-200 text-center text-xs text-ink-500 py-4">
    © {{ date('Y') }} {{ __('Saudi Football Players Association') }}
</footer>
<style>[x-cloak]{display:none!important;}</style>
</body>
</html>
