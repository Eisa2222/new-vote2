{{-- Shared brand identity head: fonts + Tailwind config + FPA palette --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
    // FPA-inspired palette: institutional dark green + warm gold accent + clean ink.
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['{{ app()->getLocale() === 'ar' ? 'Tajawal' : 'Inter' }}',
                           'Tajawal', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
                colors: {
                    // Institutional green — close in spirit to the official green of Saudi football
                    brand: {
                        50:  '#ECF5EF',
                        100: '#D0E6D6',
                        200: '#A3CEB0',
                        300: '#6FB185',
                        400: '#3F9261',
                        500: '#1F7A49',
                        600: '#115C42',  // primary
                        700: '#0B3D2E',  // dark primary (sidebar, hero)
                        800: '#083024',
                        900: '#052219',
                    },
                    // Warm gold accent for winners / CTAs emphasis
                    accent: {
                        400: '#DDB97A',
                        500: '#C8A365',
                        600: '#A8834A',
                    },
                    ink: {
                        50:  '#F8FAFC',
                        100: '#F1F5F9',
                        200: '#E2E8F0',
                        300: '#CBD5E1',
                        500: '#64748B',
                        700: '#334155',
                        800: '#1E293B',
                        900: '#0F172A',
                        950: '#020617',
                    },
                    danger:  { 500: '#D94552', 600: '#B5343F' },
                    warning: { 500: '#E8A951' },
                    success: { 500: '#16A34A' },
                    info:    { 500: '#2563EB' },
                },
                boxShadow: {
                    brand: '0 10px 30px -10px rgba(11, 61, 46, 0.25)',
                },
            },
        },
    };
</script>
<style>
    body {
        font-family: '{{ app()->getLocale() === 'ar' ? 'Tajawal' : 'Inter' }}', system-ui, sans-serif;
        /* Slightly larger base on Arabic — Tajawal reads smaller per-pixel
           than Inter, so 16px Tajawal feels cramped next to 16px Inter.
           Bumping to 17px only for Arabic keeps Latin UI unchanged. */
        @if(app()->getLocale() === 'ar')
        font-size: 17px;
        line-height: 1.65;
        @endif
    }
    @if(app()->getLocale() === 'ar')
    /* Headings get a small uptick too so hero/title pairs don't
       blend into body copy at the new 17px base size. */
    h1 { letter-spacing: -0.01em; }
    h1, h2, h3 { font-weight: 800; }
    @endif

    /* --- Action buttons --------------------------------------------------
       One visual language for the whole app:
         • .btn-save    — primary green, confirms positive action
         • .btn-edit    — amber/gold, reversible change
         • .btn-delete  — red, destructive
         • .btn-ghost   — neutral (Cancel, Back)
         • .btn-primary — historical alias for .btn-save (old views)
         • .btn-brand   / .btn-brand-lg — branded primary (existing CTAs)
       Each button keeps consistent radius, padding and weight so the
       whole admin feels one coherent system. -------------------------- */
    .btn-save        { @apply inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-xl px-5 py-2.5 font-semibold shadow-sm transition; }
    .btn-primary     { @apply inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-xl px-5 py-2.5 font-semibold shadow-sm transition; }
    .btn-brand       { @apply inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl px-5 py-2.5 font-semibold transition; }
    .btn-brand-lg    { @apply inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white rounded-2xl px-8 py-3.5 font-semibold text-lg shadow-brand transition; }
    .btn-edit        { @apply inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white rounded-xl px-5 py-2.5 font-semibold shadow-sm transition; }
    .btn-delete      { @apply inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl px-5 py-2.5 font-semibold shadow-sm transition; }
    .btn-ghost       { @apply inline-flex items-center gap-2 text-ink-700 border border-ink-200 hover:bg-ink-50 rounded-xl px-4 py-2 font-medium transition; }
    .btn-danger      { @apply inline-flex items-center gap-2 text-danger-600 border border-danger-500/40 hover:bg-danger-500/10 rounded-xl px-4 py-2 font-medium transition; }
    .btn-icon        { @apply inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 hover:bg-ink-50 transition; }

    /* --- Cards / badges (existing) --- */
    .card            { @apply bg-white rounded-2xl border border-ink-200 shadow-sm p-6; }
    .badge           { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold; }
    .badge-active    { @apply bg-brand-100 text-brand-700; }
    .badge-published { @apply bg-info-500/10 text-info-500; }
    .badge-draft     { @apply bg-warning-500/10 text-warning-500; }
    .badge-closed    { @apply bg-ink-100 text-ink-700; }
    .badge-archived  { @apply bg-ink-100 text-ink-500; }
    .badge-inactive  { @apply bg-ink-100 text-ink-500; }

    /* --- Forms -----------------------------------------------------------
       .form-page  → full-width form body, max-width wide enough for 3-4
                     columns on desktop but still centred on 4K screens.
       .field-*    → shared input styling so every form feels the same;
                     we apply these via Tailwind `@apply` so existing
                     inline classes continue to work side-by-side.       */
    .form-page       { @apply w-full max-w-none; }
    .form-wrap       { @apply w-full max-w-6xl mx-auto; }
    .field-label     { @apply block text-sm font-medium mb-1.5 text-ink-800; }
    .field-help      { @apply mt-1 text-xs text-ink-500; }
    .field-error     { @apply mt-1 text-xs text-rose-600; }
    .field-input     { @apply w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition; }
    .field-input:disabled, .field-input[readonly] { @apply bg-ink-50 text-ink-500; }
    .field-textarea  { @apply w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition; }
    .field-select    { @apply w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition; }
    .field-checkbox  { @apply rounded border-ink-300 text-brand-600 focus:ring-brand-500; }

    /* --- Dropdown chevron -----------------------------------------------
       Native <select> gets a real caret icon (instead of OS default)
       that respects LTR/RTL direction. Works everywhere the @tailwindcss/
       forms plugin is loaded — which is our case via the CDN query. */
    select:not([multiple]):not([size]) {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.75' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-size: 18px 18px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    [dir="ltr"] select:not([multiple]):not([size]) {
        background-position: right 12px center;
        padding-right: 40px !important;
    }
    [dir="rtl"] select:not([multiple]):not([size]) {
        background-position: left 12px center;
        padding-left: 40px !important;
    }

    /* --- Design system additions (QA pass 2026-04) -------------------
       All new additions; don't remove older classes so existing views
       keep working. These gaps were flagged by the QA audit:
         • buttons — hero, primary-sm, danger-soft, warn-soft
         • cards — large, tap, hero
         • chips — the unified status-chip family (single source of truth)
         • alerts — consistent inline feedback
         • eyebrow — the small UPPERCASE TRACKED label, auto-neutralised
           for Arabic so Arabic glyphs don't get broken tracking
    ------------------------------------------------------------------- */

    /* Hero "New Campaign" CTA — the gradient fill admins see on /admin/campaigns */
    .btn-primary-hero {
        @apply group relative inline-flex items-center gap-3 rounded-xl
               bg-gradient-to-br from-brand-600 via-brand-700 to-brand-800
               hover:from-brand-500 hover:via-brand-600 hover:to-brand-700
               text-white ps-3 pe-5 py-2.5 font-bold whitespace-nowrap
               shadow-lg shadow-brand-900/20 hover:shadow-xl
               ring-1 ring-white/10 hover:ring-white/20 transition;
    }
    .btn-primary-sm   { @apply inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 text-sm font-semibold shadow-sm transition; }
    .btn-secondary-sm { @apply inline-flex items-center gap-2 rounded-xl border border-ink-200 bg-white hover:bg-ink-50 text-ink-700 px-4 py-2 text-sm font-medium transition; }
    .btn-danger-soft  { @apply inline-flex items-center gap-1 rounded-lg border border-rose-200 hover:bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 transition; }
    .btn-warn-soft    { @apply inline-flex items-center gap-1 rounded-lg border border-amber-200 hover:bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition; }

    /* Card variants */
    .card-lg   { @apply bg-white rounded-3xl border border-ink-200 shadow-sm p-5; }
    .card-tap  { @apply bg-white rounded-3xl border-2 border-ink-200 shadow-sm overflow-hidden; }

    /* Unified status-chip family — stops the three drifting enum maps */
    .chip             { @apply inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium; }
    .chip-active      { @apply bg-green-50 text-green-700; }
    .chip-inactive    { @apply bg-ink-100 text-ink-500; }
    .chip-pending     { @apply bg-amber-50 text-amber-700; }
    .chip-published   { @apply bg-blue-50 text-blue-700; }
    .chip-announced   { @apply bg-emerald-50 text-emerald-700; }
    .chip-rejected    { @apply bg-rose-50 text-rose-700; }
    .chip-dot         { @apply w-1.5 h-1.5 rounded-full inline-block; }

    /* Alerts — one source of truth */
    .alert            { @apply rounded-xl p-3 text-sm flex items-start gap-2; }
    .alert-error      { @apply bg-rose-50 border border-rose-200 text-rose-700; }
    .alert-warning    { @apply bg-amber-50 border border-amber-200 text-amber-800; }
    .alert-info       { @apply bg-blue-50 border border-blue-200 text-blue-800; }
    .alert-success    { @apply bg-brand-50 border border-brand-200 text-brand-800; }

    /* Eyebrow — the "small UPPERCASE TRACKED" label pattern used on
       hero sections and stat cards. Auto-neutralises for RTL because
       uppercase + wide letter-spacing break Arabic glyphs. */
    .label-eyebrow    { @apply text-[10px] uppercase tracking-widest font-semibold text-ink-500; }
    [dir="rtl"] .label-eyebrow {
        text-transform: none;
        letter-spacing: 0;
        font-size: 11px;
    }
</style>
