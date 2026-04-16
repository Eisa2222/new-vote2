@props(['totalRequired'])

{{--
  Sticky, always-visible submit footer.
  - "Submit" button is disabled until totalSelected() === totalRequired.
  - Shows clear missing-pick message when incomplete.
--}}
<div class="sticky bottom-0 inset-x-0 z-30 pt-4 pb-4 bg-gradient-to-t from-white via-white/95 to-transparent">
    <div class="max-w-6xl mx-auto px-2 md:px-0">
        <div class="rounded-2xl border border-ink-200 bg-white shadow-xl p-3 md:p-4 flex flex-col md:flex-row md:items-center gap-3">
            <div class="flex-1 min-w-0">
                <template x-if="missingSummary()">
                    <div class="text-sm font-semibold text-rose-700 flex items-center gap-2">
                        <span>&#9888;</span>
                        <span x-text="missingSummary()"></span>
                    </div>
                </template>
                <template x-if="!missingSummary()">
                    <div class="text-sm font-semibold text-emerald-700 flex items-center gap-2">
                        <span>&#10003;</span>
                        <span>{{ __('Lineup complete — ready to submit.') }}</span>
                    </div>
                </template>
            </div>

            <button type="submit"
                    :disabled="!canSubmit() || submitting"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3.5 font-bold text-base transition"
                    :class="canSubmit() && !submitting
                              ? 'bg-brand-600 hover:bg-brand-700 text-white shadow-brand'
                              : 'bg-ink-200 text-ink-500 cursor-not-allowed'">
                <span x-show="!submitting">&#10003; {{ __('Submit my Team of the Season') }}</span>
                <span x-show="submitting">&#8230; {{ __('Submitting...') }}</span>
                <span class="inline-flex rounded-full bg-white/20 px-2.5 py-0.5 text-xs"
                      x-show="!submitting">
                    <span x-text="totalSelected()"></span>/{{ $totalRequired }}
                </span>
            </button>
        </div>
    </div>
</div>
