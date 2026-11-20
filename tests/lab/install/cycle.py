"""Package installation and upgrade browser acceptance."""
from common import *
b = a = None
try:
    before = inspect()
    check('native OJS target and required extensions', all(before['extensions'].values()) and any((v['product'] == 'ojs2' and [v[k] for k in ['major', 'minor', 'revision', 'build']] == [3, 3, 0, 20] for v in before['versions'])))
    expected = 37 if case == 'upgrade' else 39
    check('native plugin version registered', any((v['product'] == 'estrabao' and v['minor'] == expected and (v['current'] == 1) for v in before['versions'])))
    for _ in range(60):
        try:
            b = browser.Browser()
            break
        except RuntimeError:
            time.sleep(0.5)
    if b is None:
        raise RuntimeError('Synthetic browser unavailable')
    a = browser.Browser()
    b.login('admin')
    a.login('author')
    view(b)
    check('gate starts explicitly disabled', not b.js('return !!document.querySelector("#estrabaoApaReviewForm")'))
    gate()
    view(b)
    check('packaged engine activates review', b.js('return !!document.querySelector("#estrabaoApaReviewForm")'))
    check('no automatic release', 'Há liberação editorial válida' not in b.text())
    finish(a, 'review')
    check('inconclusive draft waits for review', progress('review') == 4)
    approve(b)
    check('human review releases analysis', 'Há liberação editorial válida' in b.text())
    generate(b)
    b.js('let f=document.querySelector("#estrabaoEditorialForm");f.querySelector("#estrabaoEditorial_metadata").value="reviewed";f.querySelector("[name=editorialReviewed]").checked=true;f.requestSubmit()')
    wait(b, 'return document.body.innerText.includes("Conferência editorial registrada.")')
    ledgers = inspect()['ledgers']
    check('both private histories created', any((x.startswith('review-') for x in ledgers)) and any((x.startswith('editorial-') for x in ledgers)))
    if case == 'upgrade':
        install(case, 'release-current')
        after = inspect()
        check('upgrade keeps native version history', any((v['minor'] == 39 and v['current'] == 1 for v in after['versions'] if v['product'] == 'estrabao')) and any((v['minor'] == 37 and v['current'] == 0 for v in after['versions'] if v['product'] == 'estrabao')))
        check('upgrade preserves exact ledger bytes', ledgers == after['ledgers'])
        view(b)
        check('engine change invalidates old release', 'Há liberação editorial válida' not in b.text())
        generate(b)
        check('engine change resets checklist retaining history', b.js('return !!document.querySelector("#estrabaoEditorialStale") && document.querySelector("#estrabaoEditorial_metadata").value==="pending"'))
        finish(a, 'review')
        check('old release cannot complete after upgrade', progress('review') == 4)
        view(b)
        approve(b)
    saved = inspect()['ledgers']
    inspect('disable')
    view(b)
    check('disable removes private handler', not b.js('return !!document.querySelector("#estrabaoApaReviewForm")'))
    check('disable preserves histories', saved == inspect()['ledgers'])
    inspect('enable')
    view(b)
    check('re-enable restores release', 'Há liberação editorial válida' in b.text())
    check('re-enable preserves histories', saved == inspect()['ledgers'])
    finish(a, 'review', False)
    check('native CSRF required', progress('review') == 4)
    finish(a, 'review')
    check('reviewed draft completes native wizard', progress('review') == 0)
    view(b, 'abnt')
    check('ABNT offers no approval', not b.js('return !!document.querySelector("button[value=approve]")'))
    finish(a, 'abnt')
    check('ABNT cannot complete wizard', progress('abnt') == 4)
    a.get(base + '/estrabaoAdmission/review?submissionId=' + str(ids['abnt']))
    check('author cannot view private form', not a.js('return !!document.querySelector("#estrabaoApaReviewForm") || !!document.querySelector("#estrabaoManuscriptType")'))
    gate(engine=False)
    finish(a, 'revoke')
    check('missing engine fails closed', progress('revoke') == 4)
    check('missing engine does not leak paths', '/opt/' not in a.text() and '/var/www/' not in a.text())
    gate()
    b.get(base + '/management/settings/website#plugins')
    time.sleep(3)
    check('administrator discovers plugin', 'Estrabão' in b.text())
    b.screenshot('installed-plugin')
    check('no checkout mounts', not json.loads(docker('inspect', web))[0]['HostConfig'].get('Binds'))
    (folder / 'summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'case': case, 'ojs': '3.3.0.20', 'plugin': '0.39.0.0', 'engine': '0.44.0', 'previous_plugin': '0.37.0.0' if case == 'upgrade' else None, 'production_accessed': False, 'external_messages_sent': False}, indent=2))
finally:
    gate()
    if b:
        b.call('DELETE', '')
    if a:
        a.call('DELETE', '')
