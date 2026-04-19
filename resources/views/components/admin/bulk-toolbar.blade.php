@props([
    'action',
    'selectAllId' => 'bulkSelectAll',
    'itemCheckboxClass' => 'bulk-check',
    'confirmTemplate' => null,
    'label' => null,
    'color' => 'rose',
])

{{-- Reusable bulk-action toolbar.

     Consumers render a sticky bar over their table and give each row
     checkbox the class passed in `itemCheckboxClass`. The toolbar
     auto-counts checked rows, enables/disables the action button, and
     wires a confirm dialog with the count before POST.

     Example:
       <x-admin.bulk-toolbar
           :action="route('admin.users.bulkDelete')"
           confirm-template="{{ __('Archive :n user(s)?') }}"
           label="{{ __('Archive selected') }}" />
--}}

<form x-data="bulkToolbar(@js($confirmTemplate ?? __('Confirm action on :n item(s)?')))"
      method="post"
      action="{{ $action }}"
      @submit="onSubmit"
      class="sticky top-16 z-20 -mx-4 md:-mx-6 px-4 md:px-6 mb-4">
    @csrf

    <div x-show="count > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="rounded-2xl bg-{{ $color }}-50 border border-{{ $color }}-200 px-4 py-2.5 flex items-center justify-between gap-3 shadow-sm">
        <div class="text-sm text-{{ $color }}-800 font-medium flex items-center gap-2">
            <span x-text="count"></span>
            <span>{{ __('selected') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="deselectAll"
                    class="text-xs text-ink-600 hover:underline px-2 py-1">
                {{ __('Clear') }}
            </button>
            {{-- Ensure selected ids are POSTed. We inject them as
                 hidden inputs on submit so the form stays simple. --}}
            <template x-for="id in ids" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-{{ $color }}-600 hover:bg-{{ $color }}-700 text-white px-4 py-2 text-sm font-semibold shadow-sm">
                <span aria-hidden="true">🗑</span>
                <span>{{ $label ?? __('Delete selected') }}</span>
            </button>
        </div>
    </div>
</form>

@once
<script>
    function bulkToolbar(confirmTpl) {
        return {
            count: 0,
            ids: [],
            boxes() { return document.querySelectorAll('.bulk-check'); },
            init() {
                this.recount();
                document.addEventListener('change', (e) => {
                    if (e.target.matches('.bulk-check, .bulk-select-all')) this.recount();
                });
                document.querySelectorAll('.bulk-select-all').forEach(sa => {
                    sa.addEventListener('change', () => {
                        this.boxes().forEach(cb => cb.checked = sa.checked);
                        this.recount();
                    });
                });
            },
            recount() {
                const checked = [...this.boxes()].filter(cb => cb.checked);
                this.count = checked.length;
                this.ids   = checked.map(cb => cb.value);
                document.querySelectorAll('.bulk-select-all').forEach(sa => {
                    const all = this.boxes().length;
                    sa.checked       = all > 0 && this.count === all;
                    sa.indeterminate = this.count > 0 && this.count < all;
                });
            },
            deselectAll() {
                this.boxes().forEach(cb => cb.checked = false);
                document.querySelectorAll('.bulk-select-all').forEach(sa => sa.checked = false);
                this.recount();
            },
            onSubmit(e) {
                const msg = confirmTpl.replace(':n', this.count);
                if (!confirm(msg)) e.preventDefault();
            },
        };
    }
</script>
@endonce
