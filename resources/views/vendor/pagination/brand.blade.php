@if ($paginator->hasPages())
    @php
        $isRtl = app()->getLocale() === 'ar';
        $prev  = $isRtl ? '→' : '←';
        $next  = $isRtl ? '←' : '→';
    @endphp
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between gap-3 py-3 flex-wrap">

        {{-- Summary --}}
        <div class="text-xs text-ink-500">
            {!! __('Showing <strong>:first</strong> to <strong>:last</strong> of <strong>:total</strong>', [
                'first' => $paginator->firstItem() ?? 0,
                'last'  => $paginator->lastItem() ?? 0,
                'total' => $paginator->total(),
            ]) !!}
        </div>

        {{-- Page buttons --}}
        <div class="inline-flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 bg-ink-50 text-ink-400 text-sm cursor-not-allowed">
                    {{ $prev }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 bg-white text-ink-700 hover:border-brand-400 hover:text-brand-700 text-sm transition">
                    {{ $prev }}
                </a>
            @endif

            {{-- Numbered pages (Laravel's windowed array) --}}
            @foreach ($elements ?? [] as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-10 h-10 text-ink-400 text-sm">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-brand-600 text-white font-bold text-sm shadow-brand">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 bg-white text-ink-700 hover:border-brand-400 hover:text-brand-700 text-sm transition">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 bg-white text-ink-700 hover:border-brand-400 hover:text-brand-700 text-sm transition">
                    {{ $next }}
                </a>
            @else
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-ink-200 bg-ink-50 text-ink-400 text-sm cursor-not-allowed">
                    {{ $next }}
                </span>
            @endif
        </div>
    </nav>
@endif
