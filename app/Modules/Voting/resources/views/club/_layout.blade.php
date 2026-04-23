@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Vote')) — {{ \App\Modules\Shared\Support\Branding::name() }}</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="bg-ink-50 min-h-screen flex flex-col">
    <header class="bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 text-white">
        <div class="max-w-4xl mx-auto px-4 py-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-brand.logo size="md" />
                <div class="leading-tight">
                    <div class="text-xs uppercase tracking-[0.25em] text-accent-400">{{ \App\Modules\Shared\Support\Branding::name() }}</div>
                    <div class="text-sm text-brand-100 mt-0.5">{{ __('Official Platform') }}</div>
                </div>
            </div>
            <div class="flex items-center rounded-xl border border-white/20 bg-white/5 overflow-hidden text-xs font-semibold">
                <a href="/set-locale/ar" class="px-3 py-2 {{ $locale === 'ar' ? 'bg-white text-brand-800' : 'text-white hover:bg-white/10' }}">AR</a>
                <a href="/set-locale/en" class="px-3 py-2 {{ $locale === 'en' ? 'bg-white text-brand-800' : 'text-white hover:bg-white/10' }}">EN</a>
            </div>
        </div>
    </header>

    <main class="flex-1 py-8 px-4">
        <div class="max-w-4xl mx-auto">
            @yield('content')
        </div>
    </main>

    <footer class="bg-white border-t border-ink-200 py-5 px-4 text-center text-xs text-ink-500 space-y-1">
        <div>© {{ date('Y') }} {{ \App\Modules\Shared\Support\Branding::name() }}</div>
        <div class="text-brand-700 font-semibold tracking-wide">
            #SFPA_Awards  #{{ __('SFPA_Awards') }}
        </div>
    </footer>
    <style>[x-cloak]{display:none!important;}</style>

    {{--
      Per-page scripts land here via @push('scripts'). Without this
      @stack, the ballot's clubBallot() Alpine factory never makes
      it into the DOM and the console fills with
          "clubBallot is not defined / total is not defined / ..."
      as Alpine evaluates each x-data / x-text expression.
    --}}
    @stack('scripts')
</body>
</html>
