@props([])

{{--
  Toast notifications. Reads the usual session flash keys and the
  ShareErrorsFromSession view-error bag. Gracefully degrades when
  rendered outside the web middleware stack (no errors bag available).
--}}
<div id="toaster-root"
     class="fixed top-4 end-4 z-[70] flex flex-col gap-2 max-w-sm w-[calc(100%-2rem)] md:w-96 pointer-events-none">

    @if(session('success'))
        <div data-toast
             class="pointer-events-auto rounded-2xl border-2 border-emerald-300 bg-white shadow-lg px-4 py-3 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg flex-shrink-0">✓</div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider">{{ __('Success') }}</div>
                <div class="text-sm text-ink-800 mt-0.5">{{ session('success') }}</div>
            </div>
            <button type="button" aria-label="Close"
                    onclick="this.closest('[data-toast]').remove()"
                    class="text-ink-400 hover:text-ink-700 text-lg leading-none">&times;</button>
        </div>
    @endif

    @if(session('warning'))
        <div data-toast
             class="pointer-events-auto rounded-2xl border-2 border-amber-300 bg-white shadow-lg px-4 py-3 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg flex-shrink-0">!</div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-amber-700 uppercase tracking-wider">{{ __('Warning') }}</div>
                <div class="text-sm text-ink-800 mt-0.5">{{ session('warning') }}</div>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()"
                    class="text-ink-400 hover:text-ink-700 text-lg leading-none">&times;</button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        @foreach($errors->all() as $errorMessage)
            <div data-toast
                 class="pointer-events-auto rounded-2xl border-2 border-rose-300 bg-white shadow-lg px-4 py-3 flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center text-lg flex-shrink-0">✕</div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-rose-700 uppercase tracking-wider">{{ __('Error') }}</div>
                    <div class="text-sm text-ink-800 mt-0.5">{{ $errorMessage }}</div>
                </div>
                <button type="button" onclick="this.closest('[data-toast]').remove()"
                        class="text-ink-400 hover:text-ink-700 text-lg leading-none">&times;</button>
            </div>
        @endforeach
    @endif
</div>

<script>
    setTimeout(() => {
        document.querySelectorAll('#toaster-root [data-toast]').forEach(toast => {
            toast.style.transition = 'opacity .3s, transform .3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 300);
        });
    }, 5000);
</script>
