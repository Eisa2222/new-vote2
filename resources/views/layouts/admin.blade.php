@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('SFPA Voting'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        body { font-family: {{ $locale === 'ar' ? "'Tajawal','Cairo',sans-serif" : "'Inter',system-ui,sans-serif" }}; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
<div class="min-h-screen flex">

    <aside class="w-72 bg-slate-900 text-white hidden lg:flex lg:flex-col">
        <div class="px-6 py-5 border-b border-white/10">
            <div class="text-xl font-bold">{{ __('Saudi Football Players Association') }}</div>
            <div class="text-sm text-slate-300 mt-1">{{ __('SFPA Voting Admin') }}</div>
        </div>

        <?php
            $nav = [
                ['path' => '/admin',           'icon' => '🏠', 'label' => __('Dashboard')],
                ['path' => '/admin/users',     'icon' => '👤', 'label' => __('Users')],
                ['path' => '/admin/clubs',     'icon' => '🏟️', 'label' => __('Clubs')],
                ['path' => '/admin/players',   'icon' => '🧍', 'label' => __('Players')],
                ['path' => '/admin/campaigns', 'icon' => '🗳️', 'label' => __('Campaigns')],
                ['path' => '/admin/results',   'icon' => '📊', 'label' => __('Results')],
            ];
            $current = '/'.trim(request()->path(), '/');
        ?>

        <nav class="flex-1 p-4 space-y-2 text-sm">
            @foreach($nav as $item)
                @php($active = $current === $item['path'] || str_starts_with($current, $item['path'].'/'))
                <a href="{{ $item['path'] }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ $active ? 'bg-white/10' : 'hover:bg-white/10' }}">
                    <span>{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @auth
            <div class="p-4 border-t border-white/10">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-start text-rose-300 hover:text-rose-200 px-4 py-2 rounded-xl hover:bg-white/5">
                        {{ __('Sign out') }}
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-gray-200 px-4 md:px-8 py-4 sticky top-0 z-20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">@yield('page_title', __('Dashboard'))</h1>
                    <p class="text-sm text-gray-500">@yield('page_description', __('Manage the e-voting platform'))</p>
                </div>
                <div class="flex items-center gap-3">
                    <select onchange="window.location.href='/set-locale/'+this.value"
                            class="border border-gray-300 rounded-xl px-3 py-2 bg-white text-sm">
                        <option value="ar" @selected($locale === 'ar')>العربية</option>
                        <option value="en" @selected($locale === 'en')>English</option>
                    </select>
                    @auth
                        <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-2">
                            <div class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold">
                                {{ strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="text-sm">
                                <div class="font-semibold">{{ auth()->user()->name }}</div>
                                <div class="text-gray-500">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </header>

        <main class="p-4 md:p-8 space-y-6">
            @if(session('success'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 p-4">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 p-4">
                    @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
