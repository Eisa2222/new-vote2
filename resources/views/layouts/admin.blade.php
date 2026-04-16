@php($locale = app()->getLocale())
@php($dir = $locale === 'ar' ? 'rtl' : 'ltr')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('SFPA Voting'))</title>
    @include('partials.brand-head')
</head>
<body class="bg-ink-50 text-ink-900 min-h-screen">
<div class="min-h-screen flex">

    <aside class="w-72 bg-brand-700 text-white hidden lg:flex lg:flex-col">
        <div class="px-6 py-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/10 flex items-center justify-center text-base font-bold tracking-wide">FPA</div>
                <div class="leading-tight">
                    <div class="font-bold text-sm">{{ __('Saudi Football Players Association') }}</div>
                    <div class="text-xs text-brand-200 mt-0.5">{{ __('SFPA Voting Admin') }}</div>
                </div>
            </div>
        </div>

        <?php
            $nav = [
                ['path' => '/admin',           'icon' => '🏠', 'label' => __('Dashboard')],
                ['path' => '/admin/users',     'icon' => '👥', 'label' => __('Users')],
                ['path' => '/admin/clubs',     'icon' => '🏟️', 'label' => __('Clubs')],
                ['path' => '/admin/players',   'icon' => '🧍', 'label' => __('Players')],
                ['path' => '/admin/campaigns', 'icon' => '🗳️', 'label' => __('Campaigns')],
                ['path' => '/admin/results',   'icon' => '🏆', 'label' => __('Results')],
                ['path' => '/admin/settings',  'icon' => '⚙️', 'label' => __('Settings')],
            ];
            $current = '/'.trim(request()->path(), '/');
        ?>

        <nav class="flex-1 p-4 space-y-1 text-sm">
            @foreach($nav as $item)
                @php($active = $current === $item['path'] || str_starts_with($current, $item['path'].'/'))
                <a href="{{ $item['path'] }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition
                          {{ $active ? 'bg-white/15 text-white font-semibold shadow-brand' : 'text-brand-100 hover:bg-white/10 hover:text-white' }}">
                    <span>{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @auth
            <div class="p-4 border-t border-white/10">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-start text-danger-500 hover:bg-white/5 px-4 py-2 rounded-xl transition">
                        ← {{ __('Sign out') }}
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-ink-200 px-4 md:px-8 py-4 sticky top-0 z-20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-ink-900">@yield('page_title', __('Dashboard'))</h1>
                    <p class="text-sm text-ink-500 mt-0.5">@yield('page_description', __('Manage the e-voting platform'))</p>
                </div>
                <div class="flex items-center gap-3">
                    <select onchange="window.location.href='/set-locale/'+this.value"
                            class="border border-ink-200 rounded-xl px-3 py-2 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="ar" @selected($locale === 'ar')>العربية</option>
                        <option value="en" @selected($locale === 'en')>English</option>
                    </select>
                    @auth
                        <div class="flex items-center gap-3 rounded-2xl border border-ink-200 bg-white px-3 py-2">
                            <div class="w-10 h-10 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold">
                                {{ strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="text-sm leading-tight">
                                <div class="font-semibold text-ink-900">{{ auth()->user()->name }}</div>
                                <div class="text-ink-500 text-xs">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </header>

        <main class="p-4 md:p-8 space-y-6">
            @if(session('success'))
                <div class="rounded-2xl bg-brand-50 border border-brand-200 text-brand-700 p-4 flex items-center gap-3">
                    <span class="text-brand-500 text-xl">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl bg-danger-500/5 border border-danger-500/30 text-danger-600 p-4">
                    @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-8 py-5 text-center text-xs text-ink-500 border-t border-ink-200 mt-10">
            © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Voting Platform') }}
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
