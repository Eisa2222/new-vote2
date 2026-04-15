@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in') }} — SFPA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        body { font-family: {{ $locale === 'ar' ? "'Tajawal','Cairo',sans-serif" : "'Inter',system-ui,sans-serif" }}; }
    </style>
</head>
<body>
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-900 flex items-center justify-center p-6">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl bg-white">

        <div class="hidden lg:flex flex-col justify-between p-10 text-white bg-slate-900">
            <div>
                <div class="text-sm uppercase tracking-widest text-emerald-300">SFPA Voting</div>
                <h1 class="text-4xl font-bold mt-4 leading-tight">{{ __('Professional e-voting platform') }}</h1>
                <p class="text-slate-300 mt-5 leading-8">
                    {{ __('Manage campaigns, candidates, public voting, results and winner approval through a bilingual admin panel.') }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="rounded-2xl bg-white/10 p-4">
                    <div class="text-2xl font-bold">{{ \App\Modules\Clubs\Models\Club::count() }}</div>
                    <div class="text-slate-300 mt-1">{{ __('Clubs') }}</div>
                </div>
                <div class="rounded-2xl bg-white/10 p-4">
                    <div class="text-2xl font-bold">{{ \App\Modules\Players\Models\Player::count() }}</div>
                    <div class="text-slate-300 mt-1">{{ __('Players') }}</div>
                </div>
            </div>
        </div>

        <div class="p-8 md:p-12">
            <div class="max-w-md mx-auto">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-3xl font-bold">{{ __('Sign in') }}</h2>
                        <p class="text-gray-500 mt-2">{{ __('Enter your credentials to access the admin panel') }}</p>
                    </div>
                    <select onchange="window.location.href='/set-locale/'+this.value"
                            class="border border-gray-300 rounded-xl px-3 py-2 bg-white text-sm">
                        <option value="ar" @selected($locale === 'ar')>العربية</option>
                        <option value="en" @selected($locale === 'en')>English</option>
                    </select>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="admin@sfpa.sa">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('Password') }}</label>
                        <input type="password" name="password" required
                               class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-emerald-600">
                            <span>{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <button class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 font-semibold transition">
                        {{ __('Sign in') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
