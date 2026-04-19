@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in') }} — FPA</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
{{--
  Sticky-footer layout:
    <body> is flex-col min-h-screen
    <main> is flex-1 (takes all available height, centres the card)
    <footer> sits at the bottom no matter how tall the viewport is.
--}}
<body class="bg-ink-50 min-h-screen flex flex-col">
<main class="flex-1 bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 flex items-center justify-center p-4 md:p-6">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl bg-white">

        <div class="hidden lg:flex flex-col justify-between p-10 text-white bg-brand-800 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10"
                 style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px);
                        background-size: 22px 22px;"></div>
            <div class="relative">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center text-lg font-bold">FPA</div>
                    <div class="leading-tight">
                        <div class="text-xs uppercase tracking-[0.25em] text-accent-500">SFPA VOTING</div>
                        <div class="text-sm text-brand-100 mt-0.5">{{ __('Official Platform') }}</div>
                    </div>
                </div>
                <h1 class="text-3xl lg:text-4xl font-bold leading-tight">
                    {{ __('Saudi Football Players Association') }}
                </h1>
                <p class="text-brand-100 mt-4 leading-8 text-sm">
                    {{ __('Manage campaigns, candidates, public voting, results and winner approval through a bilingual admin panel.') }}
                </p>
            </div>
            <div class="relative grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-white/10 backdrop-blur p-4">
                    <div class="text-2xl font-bold">{{ \App\Modules\Clubs\Models\Club::count() }}</div>
                    <div class="text-brand-200 mt-1">{{ __('Clubs') }}</div>
                </div>
                <div class="rounded-2xl bg-white/10 backdrop-blur p-4">
                    <div class="text-2xl font-bold">{{ \App\Modules\Players\Models\Player::count() }}</div>
                    <div class="text-brand-200 mt-1">{{ __('Players') }}</div>
                </div>
            </div>
        </div>

        <div class="p-8 md:p-12">
            <div class="max-w-md mx-auto">
                <div class="flex items-start justify-between mb-8 gap-4">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-ink-900">{{ __('Sign in') }}</h2>
                        <p class="text-ink-500 mt-2 text-sm">{{ __('Enter your credentials to access the admin panel') }}</p>
                    </div>
                    {{--
                      Language toggle — matches the AR/EN chip used in the admin
                      header exactly (bg-brand-600 for the active locale,
                      hover:bg-ink-50 for the other). One visual vocabulary
                      across the whole product.
                    --}}
                    <div class="flex items-center rounded-xl border border-ink-200 bg-white overflow-hidden text-xs font-semibold shrink-0">
                        <a href="/set-locale/ar" class="px-3 py-2 {{ $locale === 'ar' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">AR</a>
                        <a href="/set-locale/en" class="px-3 py-2 {{ $locale === 'en' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">EN</a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login.attempt') }}" class="space-y-4" autocomplete="on" novalidate>
                    @csrf
                    <div>
                        <label for="loginEmail" class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Email') }}</label>
                        <input id="loginEmail" type="email" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="username" inputmode="email" spellcheck="false"
                               class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                               placeholder="example@domain.com">
                    </div>
                    {{--
                      Password field with a show/hide eye toggle. Alpine.js
                      keeps the state local to this block. The SVG icons are
                      inline so the form works even if a CDN is blocked —
                      critical for an auth screen.
                    --}}
                    <div x-data="{ show: false }">
                        <label for="loginPassword" class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Password') }}</label>
                        <div class="relative">
                            <input id="loginPassword" name="password" required
                                   autocomplete="current-password"
                                   :type="show ? 'text' : 'password'"
                                   class="w-full rounded-xl border border-ink-200 px-4 py-3 {{ $dir === 'rtl' ? 'pl-12' : 'pr-12' }} focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                   placeholder="••••••••">
                            <button type="button" @click="show = !show"
                                    :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                                    class="absolute inset-y-0 {{ $dir === 'rtl' ? 'left-0' : 'right-0' }} flex items-center px-3 text-ink-500 hover:text-brand-700 focus:outline-none">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>{{ __('Remember me') }}</span>
                    </label>
                    <button class="w-full btn-save justify-center py-3.5 text-base">
                        {{ __('Sign in') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white border-t border-ink-200 text-center text-xs text-ink-500 py-4 px-4">
    © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Voting Platform') }}
</footer>
<style>[x-cloak]{display:none!important;}</style>
</body>
</html>
