"""Cross-context access, actual UI toggle and corrected file flow."""
from common import *
import secrets
b = a = f = None
try:
    c = json.loads((folder / 'credentials.json').read_text())
    c['foreign_username'] = 'foreign61'
    c.setdefault('foreign_password', secrets.token_hex(18))
    (folder / 'credentials.json').write_text(json.dumps(c))
    docker('cp', str(folder / 'credentials.json'), web + ':/tmp/lab-credentials.json')
    docker('cp', str(Path(__file__).with_name('extra-fixture.php')), web + ':/tmp/extra-fixture.php')

    def fixture(mode):
        return json.loads(docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/extra-fixture.php', mode))
    fixture('foreign')
    b = browser.Browser()
    a = browser.Browser()
    b.login('admin')
    a.login('foreign')
    view(b, 'revoke')
    before = inspect()['ledgers']
    payload = b.js('return Array.from(new FormData(document.querySelector("#estrabaoApaReviewForm")).entries())') + [['reviewAction', 'revoke']]
    url = b.js('return document.querySelector("#estrabaoApaReviewForm").action')
    a.get(base)
    a.call('POST', '/execute/async', {'script': 'const done=arguments[arguments.length-1];fetch(arguments[0],{method:"POST",body:new URLSearchParams(arguments[1])}).then(async r=>done(r.status));', 'args': [url, payload]})
    check('foreign manager POST preserves private history', inspect()['ledgers'] == before)
    download = b.js('return Array.from(document.querySelectorAll("a")).find(x=>x.textContent.includes("Baixar a versão atual")).href')
    response = a.call('POST', '/execute/async', {'script': 'const done=arguments[arguments.length-1];fetch(arguments[0]).then(async r=>done(Array.from(new Uint8Array(await r.arrayBuffer())).slice(0,4)));', 'args': [download]})
    check('foreign manager cannot download private manuscript', response != [80, 75, 3, 4])
    a.get(base + '/estrabaoAdmission/review?submissionId=' + str(ids['abnt']))
    check('foreign manager cannot view private review', not a.js('return !!document.querySelector("#estrabaoManuscriptType")'))
    a.call('DELETE', '')
    a = browser.Browser()
    a.login('author')
    original = fixture('inspect')
    apud = fixture('apud')
    check('apud fixture is a new server file', apud['file_id'] != original['file_id'])
    view(b, 'abnt')
    generate(b)
    check('apud is explicitly refused without approval', b.js('return document.querySelector("#estrabaoDeskBody").value.includes("apud") && !document.querySelector("button[value=approve]")'))
    finish(a, 'abnt')
    check('apud cannot complete', progress('abnt') == 4)
    docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/replace.php', '--correct-abnt')
    corrected = fixture('inspect')
    check('correction creates another server file', corrected['file_id'] != apud['file_id'])
    view(b, 'abnt')
    check('correction becomes reviewable without release', b.js('return !!document.querySelector("button[value=approve]")') and 'Há liberação editorial válida' not in b.text())
    finish(a, 'abnt')
    check('corrected version still requires human review', progress('abnt') == 4)
    view(b, 'abnt')
    approve(b)
    finish(a, 'abnt')
    check('corrected reviewed version completes', progress('abnt') == 0)
    check('completion preserves manuscript bytes', fixture('inspect') == corrected)
    b.get(base + '/management/settings/website#plugins')
    wait(b, 'return Array.from(document.querySelectorAll("tr")).some(x=>x.textContent.includes("Estrabão") && x.querySelector("input[type=checkbox]"))')
    selector = 'Array.from(document.querySelectorAll("tr")).find(x=>x.textContent.includes("Estrabão") && x.querySelector("input[type=checkbox]"))'
    b.js('let r=' + selector + ';r.scrollIntoView({block:"center"})')
    b.screenshot('plugin-enabled')
    ledgers = inspect()['ledgers']
    b.js('(' + selector + ').querySelector("input[type=checkbox]").click()')
    for _ in range(60):
        b.js('let c=Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.offsetParent!==null);if(c)c.click()')
        if not inspect()['enabled']:
            break
        time.sleep(0.1)
    check('administrator checkbox disables plugin', not inspect()['enabled'])
    b.js('(' + selector + ').querySelector("input[type=checkbox]").click()')
    for _ in range(60):
        b.js('let c=Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.offsetParent!==null);if(c)c.click()')
        if inspect()['enabled']:
            break
        time.sleep(0.1)
    check('administrator checkbox re-enables plugin', bool(inspect()['enabled']))
    check('UI toggles preserve histories', inspect()['ledgers'] == ledgers)
    (folder / 'extended-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'case': case, 'production_accessed': False, 'external_messages_sent': False}, indent=2))
finally:
    inspect('enable')
    if b:
        b.call('DELETE', '')
    if a:
        a.call('DELETE', '')
