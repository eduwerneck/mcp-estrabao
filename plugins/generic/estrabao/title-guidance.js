/* OJS 3.3 legacy submission fields. Advisory only: never changes input or saving. */
(function () {
    'use strict';
    function attach() {
        var message = window.estrabaoTitleGuidance;
        if (typeof message !== 'string') { return; }
        document.querySelectorAll('#submitStep3Form input[name^="title["], #submitStep3Form input[name^="subtitle["]').forEach(function (input) {
            if (input.dataset.estrabaoTitleGuidance) { return; }
            input.dataset.estrabaoTitleGuidance = 'true';
            var hint = document.createElement('p');
            hint.className = 'pkp_help estrabaoTitleGuidance';
            hint.id = 'estrabaoTitleGuidance-' + input.id;
            hint.textContent = message;
            input.insertAdjacentElement('afterend', hint);
            input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + hint.id).trim());
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', attach); }
    else { attach(); }
    // The wizard replaces its step through AJAX.
    new MutationObserver(function (records) {
        if (records.some(function (record) { return Array.from(record.addedNodes).some(function (node) {
            return node.nodeType === 1 && (node.matches('form, input') || node.querySelector('form, input'));
        }); })) { attach(); }
    }).observe(document.documentElement, {childList: true, subtree: true});
}());
