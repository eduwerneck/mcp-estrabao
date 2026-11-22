/* OJS 3.3.0.20: contextual navigation only; authorization remains on the server. */
(function () {
    'use strict';
    var config = window.estrabaoReviewNavigation;
    if (!config || typeof config.label !== 'string' || typeof config.url !== 'string') { return; }
    var base;
    try {
        base = new URL(config.url, window.location.href);
        if (base.origin !== window.location.origin || !/^https?:$/.test(base.protocol)) { return; }
    } catch (error) { return; }
    function attach() {
        document.querySelectorAll('.listPanel__item--submission').forEach(function (row) {
            var identity = row.querySelector('.listPanel__item--submission__id');
            var actions = row.querySelector('.listPanel__itemExpandedActions');
            var existing = row.querySelector('a.estrabaoReviewNavigation');
            var id = identity ? identity.textContent.trim() : '';
            if (!actions || !/^[1-9][0-9]{0,8}$/.test(id)) {
                if (existing) { existing.remove(); }
                return;
            }
            var target = new URL(base.href);
            target.searchParams.set('submissionId', id);
            if (!existing) {
                existing = document.createElement('a');
                existing.className = 'pkpButton estrabaoReviewNavigation';
                actions.appendChild(existing);
            }
            if (existing.getAttribute('href') !== target.href) { existing.setAttribute('href', target.href); }
            if (existing.textContent !== config.label) { existing.textContent = config.label; }
            var label = config.label + ' — ' + id;
            if (existing.getAttribute('aria-label') !== label) { existing.setAttribute('aria-label', label); }
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', attach); }
    else { attach(); }
    // Vue replaces rows on tab changes, pagination, search and expansion.
    new MutationObserver(attach).observe(document.documentElement, {childList: true, subtree: true, characterData: true});
}());
