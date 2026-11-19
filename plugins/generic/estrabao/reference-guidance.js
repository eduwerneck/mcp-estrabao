/* Advisory for OJS citationsRaw. Never rewrites text, saves, sends data or certifies references. */
(function (root, factory) {
    'use strict';
    var api = factory();
    if (typeof module === 'object' && module.exports) { module.exports = api; }
    else { api.start(root.document, root.estrabaoReferenceGuidance); }
}(typeof window !== 'undefined' ? window : this, function () {
    'use strict';
    var first, next;
    try {
        var word = "\\p{L}[\\p{L}\\p{Pd}'’]{0,63}";
        var surname = word + '(?: ' + word + '){0,4}';
        var person = surname + ',[ \\t]{0,8}(?:\\p{Lu}\\.[ \\t]?){1,4}';
        var authors = person + '(?:,[ \\t]{0,8}' + person + '){0,18}(?:,[ \\t]{0,8}&[ \\t]{0,8}' + person + ')?';
        var institution = '(?:Instituto|Universidade|Organização|University|Institute) [\\p{L} &’\\p{Pd}]{2,220}\\.';
        var year = '(?:[0-9]{4}[a-z]?|[ns]\\.\\s*d\\.|in press|no prelo)';
        var head = '(?:' + authors + '|' + institution + ')[ \\t]{0,8}\\(' + year + '\\)\\.\\s+\\S';
        first = new RegExp('^' + head, 'iu');
        next = new RegExp('[.!?]\\s+' + head, 'giu');
    } catch (error) { /* Older browsers retain the guidance and manual-review message. */ }

    function inspect(value) {
        if (typeof value !== 'string' || value.length > 131072 || !first) { return {state: 'review', lines: []}; }
        if (!value.trim()) { return {state: 'empty', lines: []}; }
        if (/[<>\u2028\u2029]/.test(value)) { return {state: 'review', lines: []}; }
        var lines = value.replace(/\r\n?/g, '\n').split('\n');
        if (lines.length > 1024 || lines.some(function (line) { return line.length > 8192; })) { return {state: 'review', lines: []}; }
        var joined = [], unknown = false;
        lines.forEach(function (raw, index) {
            var line = raw.trim();
            if (!line) { if (/[^ \t]/.test(raw)) { unknown = true; } return; }
            if (!first.test(line)) { unknown = true; return; }
            next.lastIndex = 0;
            var match, found = false;
            while ((match = next.exec(line)) !== null) {
                // A match inside the initial author block must not count as a second reference.
                if (match.index >= line.indexOf(').') + 2) { found = true; break; }
            }
            if (found && joined.length < 32) { joined.push(index + 1); }
        });
        return {state: joined.length ? 'warning' : (unknown ? 'review' : 'no_signal'), lines: joined};
    }

    function start(document, config) {
        if (!document || !config || ['help', 'warning', 'review'].some(function (key) { return typeof config[key] !== 'string'; })) { return; }
        var attached = new WeakSet(), sequence = 0;
        function attach() {
            document.querySelectorAll('textarea[name="citationsRaw"], textarea#citationsRaw, textarea#citations-citationsRaw-control').forEach(function (field) {
                if (attached.has(field)) { return; }
                attached.add(field); sequence++;
                var hint = document.createElement('p'), status = document.createElement('p');
                hint.id = 'estrabaoReferencesHelp-' + sequence;
                hint.className = 'pkp_help estrabaoReferencesHelp'; hint.textContent = config.help;
                status.id = 'estrabaoReferencesStatus-' + sequence;
                status.className = 'pkp_help estrabaoReferencesStatus';
                status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite'); status.setAttribute('aria-atomic', 'true');
                field.insertAdjacentElement('afterend', hint); hint.insertAdjacentElement('afterend', status);
                field.setAttribute('aria-describedby', ((field.getAttribute('aria-describedby') || '') + ' ' + hint.id + ' ' + status.id).trim());
                function update() {
                    var result = inspect(field.value);
                    status.dataset.state = result.state;
                    status.hidden = result.state === 'empty' || result.state === 'no_signal';
                    status.textContent = result.state === 'warning' ? config.warning : (result.state === 'review' ? config.review : '');
                }
                var timer;
                field.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(update, 200); });
                field.addEventListener('change', update); field.addEventListener('blur', update); update();
            });
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', attach); }
        else { attach(); }
        new MutationObserver(function (records) {
            if (records.some(function (record) { return Array.from(record.addedNodes).some(function (node) {
                return node.nodeType === 1 && (node.matches('textarea') || node.querySelector('textarea'));
            }); })) { attach(); }
        }).observe(document.documentElement, {childList: true, subtree: true});
    }
    return {inspect: inspect, start: start};
}));
