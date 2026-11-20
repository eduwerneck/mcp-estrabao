"""Fail-closed/CSRF/revision checks on package-installed synthetic OJS."""
from common import *
b = a = None
root = '/var/www/files/estrabao-review'
original = None
ledger = None
try:
    b = browser.Browser()
    a = browser.Browser()
    b.login('admin')
    a.login('author')
    view(b, 'revoke')
    approve(b)
    ledger = root + '/review-' + str(state['journal_id']) + '-' + str(ids['revoke']) + '.json'
    original = docker('exec', web, 'cat', ledger)
    payload = b.js('return Array.from(new FormData(document.querySelector("#estrabaoApaReviewForm")).entries())')
    url = b.js('return document.querySelector("#estrabaoApaReviewForm").action')

    def post(who, entries):
        return who.call('POST', '/execute/async', {'script': 'const done=arguments[arguments.length-1];fetch(arguments[0],{method:"POST",body:new URLSearchParams(arguments[1])}).then(async r=>done(await r.text()));', 'args': [url, entries]})
    for label, who, entries in [('author POST', a, payload + [['reviewAction', 'revoke']]), ('bad CSRF', b, [[k, 'invalid' if k == 'csrfToken' else v] for k, v in payload] + [['reviewAction', 'revoke']])]:
        post(who, entries)
        check(label + ' does not mutate ledger', docker('exec', web, 'cat', ledger) == original)
    good = payload + [['reviewAction', 'revoke']]
    post(b, good)
    changed = docker('exec', web, 'cat', ledger)
    check('valid POST appends review event', changed != original)
    post(b, good)
    check('stale revision cannot replay', docker('exec', web, 'cat', ledger) == changed)
    view(b, 'revoke')
    approve(b)
    original = docker('exec', web, 'cat', ledger)
    docker('exec', web, 'chmod', '755', root)
    try:
        finish(a, 'revoke')
        check('insecure review directory blocks released draft', progress('revoke') == 4)
    finally:
        docker('exec', web, 'chmod', '700', root)
    docker('exec', '-i', web, 'sh', '-c', 'cat > ' + ledger, input=b'{corrupt-synthetic')
    try:
        finish(a, 'revoke')
        check('corrupt ledger blocks released draft', progress('revoke') == 4)
        view(b, 'revoke')
        check('corrupt record leaks no paths or contents', '/var/www/' not in b.text() and 'corrupt-synthetic' not in b.text())
    finally:
        docker('exec', '-i', web, 'sh', '-c', 'cat > ' + ledger, input=original)
    check('negative storage tests restore exact history', docker('exec', web, 'cat', ledger) == original)
    gate(False)
    finish(a, 'csrf')
    check('explicit gate off retains native completion', progress('csrf') == 0)
    gate()
    inspect('disable')
    finish(a, 'replace')
    check('disabled plugin retains native completion', progress('replace') == 0)
    inspect('enable')
    (folder / 'negative-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'case': case, 'scope': 'new synthetic package installation', 'production_accessed': False, 'external_messages_sent': False}, indent=2))
finally:
    docker('exec', web, 'chmod', '700', root)
    if original is not None:
        docker('exec', '-i', web, 'sh', '-c', 'cat > ' + ledger, input=original)
    gate()
    inspect('enable')
    if b:
        b.call('DELETE', '')
    if a:
        a.call('DELETE', '')
