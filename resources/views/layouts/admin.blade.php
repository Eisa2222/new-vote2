@php
    $locale = app()->getLocale();
    $dir    = $locale === 'ar' ? 'rtl' : 'ltr';
    $user   = auth()->user();

    // Navigation items are filtered by the acting user's permissions so
    // nobody sees a tab they can't open. Order is stable; gates keep the
    // layout from hitting permission checks in the child views.
    $nav = collect([
        ['route' => 'admin.landing',         'icon' => '🏠', 'label' => __('Dashboard'), 'can' => true],
        ['route' => 'admin.users.index',     'icon' => '👥', 'label' => __('Users'),     'can' => (bool) $user?->can('users.manage')],
        ['route' => 'admin.roles.index',     'icon' => '🛡️', 'label' => __('Roles'),     'can' => (bool) $user?->can('users.manage')],
        ['route' => 'admin.clubs.index',     'icon' => '🏟️', 'label' => __('Clubs'),     'can' => (bool) $user?->can('clubs.viewAny')],
        ['route' => 'admin.players.index',   'icon' => '🧍', 'label' => __('Players'),   'can' => (bool) $user?->can('players.viewAny')],
        ['route' => 'admin.campaigns.index', 'icon' => '🗳️', 'label' => __('Campaigns'), 'can' => (bool) $user?->can('campaigns.viewAny')],
        ['route' => 'admin.results.index',   'icon' => '🏆', 'label' => __('Results'),   'can' => (bool) $user?->can('results.view')],
        ['route' => 'admin.archive',         'icon' => '🗃', 'label' => __('Archive'),          'can' => (bool) $user?->can('users.manage') || (bool) $user?->can('clubs.viewAny') || (bool) $user?->can('players.viewAny') || (bool) $user?->can('campaigns.viewAny')],
        ['route' => 'admin.email-templates.index', 'icon' => '✉️', 'label' => __('Email templates'), 'can' => (bool) $user?->can('users.manage')],
        ['route' => 'admin.settings.index',  'icon' => '⚙️', 'label' => __('Settings'),         'can' => (bool) $user?->can('users.manage')],
    ])->filter(fn ($item) => $item['can'])
      ->map(fn ($item) => $item + ['path' => route($item['route'])])
      ->values();

    $current = '/'.trim(request()->path(), '/');

    // Localised role labels for the header chip.
    $roleLabels = [
        'super_admin'      => __('Super Admin'),
        'committee'        => __('Voting Committee'),
        'campaign_manager' => __('Campaign Manager'),
        'auditor'          => __('Auditor'),
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
        [x-cloak] { display: none !important; }
        /* TC011 — desktop sidebar collapse. Toggles via the header button. */
        @media (min-width: 1024px) {
            html.sidebar-collapsed #adminSidebar { display: none; }
        }
    </style>
</head>
<body class="bg-ink-50 text-ink-900 min-h-screen">
<div class="min-h-screen flex">

    {{-- Mobile drawer overlay --}}
    <div id="drawerOverlay" class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden" onclick="toggleDrawer(false)"></div>

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
                    <button type="button" onclick="toggleSidebarCollapse()" aria-label="{{ __('Collapse sidebar') }}"
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
                    <div class="hidden md:flex items-center rounded-xl border border-ink-200 bg-white overflow-hidden text-xs font-semibold">
                        <a href="/set-locale/ar" class="px-3 py-2 {{ $locale === 'ar' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">AR</a>
                        <a href="/set-locale/en" class="px-3 py-2 {{ $locale === 'en' ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-50' }}">EN</a>
                    </div>

                    @auth
                        {{-- User dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button"
                                    class="flex items-center gap-2 md:gap-3 rounded-2xl border border-ink-200 hover:border-brand-400 bg-white px-2 md:px-3 py-2 transition">
                                <div class="w-9 h-9 rounded-full bg-brand-700 text-white flex items-center justify-center font-bold text-sm">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                                </div>
                                <div class="hidden md:block text-start leading-tight">
                                    <div class="font-semibold text-ink-900 text-sm truncate max-w-[160px]">{{ $user->name }}</div>
                                    <div class="text-[11px] text-ink-500 truncate max-w-[160px]">
                                        {{ $primaryRole ? ($roleLabels[$primaryRole] ?? $primaryRole) : $user->email }}
                                    </div>
                                </div>
                                <span class="text-ink-400 text-xs">▼</span>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                 class="absolute end-0 mt-2 w-64 rounded-2xl bg-white border border-ink-200 shadow-xl z-40 overflow-hidden">
                                <div class="px-4 py-3 border-b border-ink-100 bg-ink-50">
                                    <div class="text-sm font-bold text-ink-900 truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-ink-500 truncate">{{ $user->email }}</div>
                                    @if($primaryRole)
                                        <div class="mt-1.5 inline-flex items-center rounded-full bg-brand-100 text-brand-700 px-2 py-0.5 text-[10px] font-bold">
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
        link.addEventListener('click', () => { if (window.innerWidth < 1024) toggleDrawer(false); });
    });
</script>
@stack('scripts')
</body>
</html>
