/**
 * Live-stats poll for the admin campaign show page.
 *
 * Reads the campaign id from the body's data-campaign-id attribute
 * and refreshes two elements every 7 seconds:
 *   - [data-live-votes]  → current vote count
 *   - [data-live-bar]    → progress bar width (if max_voters is set)
 *
 * Silently ignores network errors so a flaky connection doesn't spam
 * the console — the next interval tick will retry.
 */
(function () {
    // Host element marks the campaign we're polling for.
    const host = document.querySelector('[data-campaign-id]');
    const campaignId = host?.dataset?.campaignId;
    if (!campaignId) return;

    const pollInterval = 7000;

    async function poll() {
        try {
            const response = await fetch(`/admin/campaigns/${campaignId}/stats`, {
                headers: { Accept: 'application/json' },
            });
            const { data } = await response.json();

            const votesEl = document.querySelector('[data-live-votes]');
            if (votesEl) votesEl.textContent = data.votes_count;

            const barEl = document.querySelector('[data-live-bar]');
            if (barEl && data.percentage != null) {
                barEl.style.width = data.percentage + '%';
            }
        } catch (_error) {
            /* swallow — the next tick will try again */
        }
    }

    setInterval(poll, pollInterval);
})();
