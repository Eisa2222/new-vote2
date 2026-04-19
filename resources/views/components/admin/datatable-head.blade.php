@props(['searchPlaceholder' => null])

{{-- Drop-in toolbar that sits above a <table data-datatable>. Adds a
     live search input (filters rows by visible text) and, when any
     <th data-sort> is present, column sorting on click.

     Markup contract expected on the following table:
       <table data-datatable class="...">
         <thead>
           <tr>
             <th data-sort>Col A</th>
             <th data-sort="number">Col B</th>   (number|text|date)
             <th>Col C (no sort)</th>
           </tr>
         </thead>
         <tbody>...</tbody>
       </table>
--}}
<div class="flex items-center justify-between gap-3 flex-wrap mb-3">
    <div class="relative flex-1 min-w-[220px] max-w-sm">
        <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-ink-400">🔍</span>
        <input type="text" data-datatable-search
               placeholder="{{ $searchPlaceholder ?? __('Search...') }}"
               class="w-full rounded-xl border border-ink-200 bg-white ps-9 pe-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
    </div>
    <div class="text-xs text-ink-500 tabular-nums">
        <span data-datatable-count>—</span> {{ __('rows') }}
    </div>
</div>
