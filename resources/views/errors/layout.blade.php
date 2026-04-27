@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code ?? 500 }} — {{ $title ?? __('Something went wrong') }}</title>
    @include('partials.brand-head')
</head>
<body class="bg-ink-50 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md text-center">
        <div class="rounded-3xl bg-white border border-ink-200 shadow-xl p-8 md:p-10">
            <div class="text-7xl mb-3">{{ $emoji ?? '⚠️' }}</div>
            <div class="text-xs font-bold uppercase tracking-[0.25em] text-ink-500">
                {{ __('Error') }} {{ $code ?? 500 }}
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-ink-900 mt-2">
                {{ $title ?? __('Something went wrong') }}
            </h1>
            <p class="text-ink-500 mt-3 leading-7 text-sm md:text-base">
                {{ $message ?? __('Our team has been notified. Please try again in a moment.') }}
            </p>

            <div class="mt-6 flex items-center justify-center gap-2 flex-wrap">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                    <span aria-hidden="true">🏠</span>
                    <span>{{ __('Back to home') }}</span>
                </a>
                <button type="button" onclick="history.back()"
                        class="inline-flex items-center gap-2 rounded-xl border border-ink-200 hover:bg-ink-50 text-ink-700 px-4 py-2.5 text-sm font-medium transition">
                    {{ __('Back') }}
                </button>
            </div>

            {{-- Reference id — useful when a user reports the issue.
                 Only the timestamp is exposed; the full exception goes
                 to storage/logs/laravel.log on the server. --}}
            @isset($ref)
                <div class="mt-6 text-[11px] text-ink-400 tabular-nums">
                    {{ __('Reference') }}: <span class="font-mono">{{ $ref }}</span>
                </div>
            @endisset
        </div>

        <div class="mt-4 text-center text-xs text-ink-400">
            © {{ date('Y') }} {{ \App\Modules\Shared\Support\Branding::name() }}
        </div>
    </div>
</body>
</html>
