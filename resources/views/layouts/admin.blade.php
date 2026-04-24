@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $user = auth()->user();

    // Navigation items are filtered by the acting user's permissions so
// nobody sees a tab they can't open. Order is stable; gates keep the
    // layout from hitting permission checks in the child views.
    $nav = collect([
        [
            'route' => 'admin.landing',
            'label' => __('Dashboard'),
            'can' => true,
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
        ],
        [
            'route' => 'admin.users.index',
            'label' => __('Users'),
            'can' => (bool) $user?->can('users.manage'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
        ],
        [
            'route' => 'admin.roles.index',
            'label' => __('Roles'),
            'can' => (bool) $user?->can('users.manage'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
        ],
        [
            'route' => 'admin.clubs.index',
            'label' => __('Clubs'),
            'can' => (bool) $user?->can('clubs.viewAny'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>',
        ],
        [
            'route' => 'admin.players.index',
            'label' => __('Players'),
            'can' => (bool) $user?->can('players.viewAny'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>',
        ],
        [
            'route' => 'admin.campaigns.index',
            'label' => __('Campaigns'),
            'can' => (bool) $user?->can('campaigns.viewAny'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>',
        ],
        [
            'route' => 'admin.results.index',
            'label' => __('Results'),
            'can' => (bool) $user?->can('results.view'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" /></svg>',
        ],
        [
            'route' => 'admin.archive',
            'label' => __('Archive'),
            'can' =>
                (bool) $user?->can('users.manage') ||
                (bool) $user?->can('clubs.viewAny') ||
                (bool) $user?->can('players.viewAny') ||
                (bool) $user?->can('campaigns.viewAny'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
        ],
        // [
        //     'route' => 'admin.email-templates.index',
        //     'label' => __('Email templates'),
        //     'can' => (bool) $user?->can('users.manage'),
        //     'icon' =>
        //         '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
        // ],
        [
            'route' => 'admin.settings.index',
            'label' => __('Settings'),
            'can' => (bool) $user?->can('users.manage'),
            'icon' =>
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        ],
    ])
        ->filter(fn($item) => $item['can'])
        ->map(fn($item) => $item + ['path' => route($item['route'])])
        ->values();

    // $current = '/' . trim(request()->path(), '/');
    $current = request()->url();

    // Localised role labels for the header chip.
    $roleLabels = [
        'super_admin' => __('Super Admin'),
        'committee' => __('Voting Committee'),
        'campaign_manager' => __('Campaign Manager'),
        'auditor' => __('Auditor'),
    ];
    $primaryRole = $user?->roles->first()?->name;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('SFPA Voting'))</title>
    @include('partials.brand-head')
    <script defer src="https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script defer src="{{ asset('js/datatable.js') }}"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* TC011 — desktop sidebar collapse. Toggles via the header button. */
        @media (min-width: 1024px) {
            html.sidebar-collapsed #adminSidebar {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-ink-50 text-ink-900 min-h-screen">
    <div class="min-h-screen flex">

        {{-- Mobile drawer overlay --}}
        <div id="drawerOverlay" class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden" onclick="toggleDrawer(false)">
        </div>

        <aside id="adminSidebar"
            class="w-72 bg-brand-700 text-white flex flex-col fixed inset-y-0 z-50 transform transition-transform lg:static lg:translate-x-0
                  {{ $dir === 'rtl' ? 'right-0 translate-x-full' : 'left-0 -translate-x-full' }}"
            data-dir="{{ $dir }}">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <x-brand.logo size="md" />
                    <div class="leading-tight">
                        <div class="font-bold text-sm">{{ \App\Modules\Shared\Support\Branding::name() }}</div>
                        <div class="text-xs text-brand-200 mt-0.5">{{ __('Admin Panel') }}</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-1 text-sm overflow-y-auto">
                @foreach ($nav as $item)
                    @php($active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])))

                    {{-- @php($active = $current === $item['path'] || str_starts_with($current, $item['path'] . '/')) --}}
                    <a href="{{ $item['path'] }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl transition
                          {{ $active ? 'bg-white/15 text-white font-semibold shadow-brand' : 'text-brand-100 hover:bg-white/10 hover:text-white' }}">
                        <span class="w-5 h-5 flex-shrink-0 [&>svg]:w-5 [&>svg]:h-5">
                            {!! $item['icon'] !!}
                        </span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        {{--
      Sticky-footer column: min-h-screen guarantees the column fills
      the viewport even when the content is shorter than one screen,
      so the footer always sits at the bottom (not in the middle of
      a near-empty page).
    --}}
        <div class="flex-1 flex flex-col min-w-0 min-h-screen">
            <header class="bg-white border-b border-ink-200 px-4 md:px-8 py-3 sticky top-0 z-30">
                <div class="flex items-center gap-4 justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Mobile: opens the drawer.
                         Desktop: collapses/expands the sidebar (TC011). --}}
                        <button type="button" onclick="toggleDrawer(true)" aria-label="{{ __('Open menu') }}"
                            class="lg:hidden inline-flex w-10 h-10 items-center justify-center rounded-lg border border-ink-200 hover:bg-ink-50">
                            <span class="text-xl">☰</span>
                        </button>
                        <button type="button" onclick="toggleSidebarCollapse()"
                            aria-label="{{ __('Collapse sidebar') }}"
                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-lg border border-ink-200 hover:bg-ink-50">
                            <span class="text-xl">☰</span>
                        </button>
                        <div class="min-w-0">
                            <h1 class="text-lg md:text-xl font-bold text-ink-900 truncate">@yield('page_title', __('Dashboard'))</h1>
                            <p class="hidden md:block text-xs text-ink-500 mt-0.5 truncate">@yield('page_description', __('Manage the e-voting platform'))</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 md:gap-3 flex-shrink-0">
                        {{-- Language switcher --}}
                        <div
                            class="hidden md:flex items-center rounded-xl border border-ink-200 bg-white overflow-hidden text-xs font-semibold">
                            <a href="/set-locale/ar"
                                class="px-3 py-2 {{ $locale === 'ar' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">AR</a>
                            <a href="/set-locale/en"
                                class="px-3 py-2 {{ $locale === 'en' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">EN</a>
                        </div>

                        @auth
                            {{-- User dropdown --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" type="button"
                                    class="flex items-center gap-2 md:gap-3 rounded-2xl border border-ink-200 hover:border-brand-400 bg-white px-2 md:px-3 py-2 transition">
                                    <div
                                        class="w-9 h-9 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold text-sm">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="hidden md:block text-start leading-tight">
                                        <div class="font-semibold text-ink-900 text-sm truncate max-w-[160px]">
                                            {{ $user->name }}</div>
                                        <div class="text-[11px] text-ink-500 truncate max-w-[160px]">
                                            {{ $primaryRole ? $roleLabels[$primaryRole] ?? $primaryRole : $user->email }}
                                        </div>
                                    </div>
                                    <span class="text-ink-400 text-xs">▼</span>
                                </button>

                                <div x-show="open" x-cloak @click.outside="open = false"
                                    class="absolute end-0 mt-2 w-64 rounded-2xl bg-white border border-ink-200 shadow-xl z-40 overflow-hidden">
                                    <div class="px-4 py-3 border-b border-ink-100 bg-ink-50">
                                        <div class="text-sm font-bold text-ink-900 truncate">{{ $user->name }}</div>
                                        <div class="text-xs text-ink-500 truncate">{{ $user->email }}</div>
                                        @if ($primaryRole)
                                            <div
                                                class="mt-1.5 inline-flex items-center rounded-full bg-brand-100 text-brand-700 px-2 py-0.5 text-[10px] font-bold">
                                                {{ $roleLabels[$primaryRole] ?? $primaryRole }}
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('profile.show') }}"
                                        class="flex items-center gap-2 px-4 py-3 text-sm text-ink-700 hover:bg-ink-50 border-b border-ink-100">
                                        <span aria-hidden="true">👤</span>
                                        <span>{{ __('My profile') }}</span>
                                    </a>
                                    <div class="md:hidden border-b border-ink-100">
                                        <a href="/set-locale/{{ $locale === 'ar' ? 'en' : 'ar' }}"
                                            class="flex items-center gap-2 px-4 py-3 text-sm text-ink-700 hover:bg-ink-50">
                                            🌐 {{ $locale === 'ar' ? 'English' : 'العربية' }}
                                        </a>
                                    </div>
                                    <form method="post" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 text-start px-4 py-3 text-sm text-rose-600 hover:bg-rose-50 transition">
                                            <span>↩</span>
                                            <span>{{ __('Sign out') }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6 space-y-5">
                @yield('content')
            </main>

            <footer class="px-8 py-5 text-center text-xs text-ink-500 border-t border-ink-200">
                © {{ date('Y') }} {{ __('Saudi Football Players Association') }} — {{ __('Voting Platform') }}
            </footer>
        </div>
    </div>

    {{-- Toast notifications — replaces the banner alerts that used to sit in each page. --}}
    <x-admin.toaster />

    <script>
        /* Desktop sidebar collapse — toggles between the normal 72-unit width
                                   and a fully hidden state. Preference is stored in localStorage so
                                   reloading the page keeps the same layout (TC011). */
        (function restoreSidebarState() {
            if (localStorage.getItem('sidebarCollapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();

        function toggleSidebarCollapse() {
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
        }

        function toggleDrawer(show) {
            const sb = document.getElementById('adminSidebar');
            const ov = document.getElementById('drawerOverlay');
            const hideCls = sb.dataset.dir === 'rtl' ? 'translate-x-full' : '-translate-x-full';
            if (show) {
                sb.classList.remove(hideCls);
                sb.classList.add('translate-x-0');
                ov.classList.remove('hidden');
            } else {
                sb.classList.remove('translate-x-0');
                sb.classList.add(hideCls);
                ov.classList.add('hidden');
            }
        }
        document.querySelectorAll('#adminSidebar nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) toggleDrawer(false);
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
