/*
 * sfpa-datatable.js — minimal client-side enhancement for any table
 * tagged with `data-datatable`. Ships without DataTables.net so we
 * stay dependency-free on the public CDN; ~120 lines of plain JS.
 *
 * Features:
 *   • search  — filters rows by visible text, via any <input data-datatable-search>
 *               that shares an ancestor with the table
 *   • sort    — click any <th data-sort> to toggle asc/desc.
 *               `data-sort="number"` uses numeric compare,
 *               `data-sort="date"` parses ISO dates, default is locale-aware text.
 *   • count   — updates <… data-datatable-count> with the currently-visible row count
 *
 * Auto-runs on DOMContentLoaded; also re-runs after HTMX / Turbo swaps if
 * the caller dispatches a `sfpa:datatable:rescan` event.
 */
(function () {
    function byText(row) { return row.textContent.toLowerCase(); }

    function initTable(table) {
        if (table.__dtInit) return; table.__dtInit = true;

        // Anchor to the nearest wrapping parent that contains the search
        // input/count element — usually the table's card.
        const scope = table.closest('[data-datatable-scope]') || table.parentElement;
        const searchEl = scope.querySelector('[data-datatable-search]');
        const countEl  = scope.querySelector('[data-datatable-count]');
        const tbody    = table.tBodies[0];
        if (!tbody) return;

        const rows = () => Array.from(tbody.rows);

        function refresh() {
            const q = (searchEl?.value || '').trim().toLowerCase();
            let visible = 0;
            rows().forEach(row => {
                const match = !q || byText(row).includes(q);
                row.hidden = !match;
                if (match) visible++;
            });
            if (countEl) countEl.textContent = visible;
        }

        searchEl?.addEventListener('input', refresh);

        // Sorting on click for every <th data-sort>.
        Array.from(table.tHead?.rows[0]?.cells || []).forEach((th, col) => {
            if (!('sort' in th.dataset)) return;
            th.classList.add('cursor-pointer', 'select-none');
            th.title = th.title || 'Click to sort';
            const type = th.dataset.sort || 'text';
            th.__dir = null;
            const arrow = document.createElement('span');
            arrow.className = 'ms-1 text-ink-400 text-xs';
            th.appendChild(arrow);
            th.addEventListener('click', () => {
                const dir = th.__dir === 'asc' ? 'desc' : 'asc';
                th.__dir = dir;
                Array.from(th.parentElement.cells).forEach(o => {
                    if (o !== th) { o.__dir = null; const s = o.querySelector('span.text-ink-400'); if (s) s.textContent = ''; }
                });
                arrow.textContent = dir === 'asc' ? '▲' : '▼';
                const rs = rows();
                rs.sort((a, b) => {
                    const av = (a.cells[col]?.textContent || '').trim();
                    const bv = (b.cells[col]?.textContent || '').trim();
                    let cmp;
                    if (type === 'number') cmp = (parseFloat(av) || 0) - (parseFloat(bv) || 0);
                    else if (type === 'date') cmp = (Date.parse(av) || 0) - (Date.parse(bv) || 0);
                    else cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' });
                    return dir === 'asc' ? cmp : -cmp;
                });
                rs.forEach(r => tbody.appendChild(r));
                refresh();
            });
        });

        refresh();
    }

    function initAll(root = document) {
        root.querySelectorAll('table[data-datatable]').forEach(initTable);
    }

    document.addEventListener('DOMContentLoaded', () => initAll());
    document.addEventListener('sfpa:datatable:rescan', () => initAll());
})();
