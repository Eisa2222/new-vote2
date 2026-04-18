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
</head>
<body class="bg-ink-50">
<div class="min-h-screen bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 flex items-center justify-center p-4 md:p-6">
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
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-ink-900">{{ __('Sign in') }}</h2>
                        <p class="text-ink-500 mt-2 text-sm">{{ __('Enter your credentials to access the admin panel') }}</p>
                    </div>
                    <select onchange="window.location.href='/set-locale/'+this.value"
                            class="border border-ink-200 rounded-xl px-3 py-2 bg-white text-sm">
                        <option value="ar" @selected($locale === 'ar')>العربية</option>
                        <option value="en" @selected($locale === 'en')>English</option>
                    </select>
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
                               placeholder="admin@sfpa.sa">
                    </div>
                    <div>
                        <label for="loginPassword" class="block text-sm font-medium mb-1.5 text-ink-800">{{ __('Password') }}</label>
                        <input id="loginPassword" type="password" name="password" required
                               autocomplete="current-password"
                               class="w-full rounded-xl border border-ink-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                               placeholder="••••••••">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>{{ __('Remember me') }}</span>
                    </label>
                    <button class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white py-3.5 font-semibold transition">
                        {{ __('Sign in') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
