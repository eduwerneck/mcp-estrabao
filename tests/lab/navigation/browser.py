"""Native OJS navigation checks, on the isolated synthetic stage62 restore lab only.
Uses a private Docker loopback transport to the lab's existing web service.
"""
import json, subprocess, base64, sys
from urllib.parse import urlsplit
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[3]
BASE = 'http://127.0.0.1:18080/index.php/estrabao-lab'
results = []

def check(name, condition):
    assert condition, name
    results.append({'case': name, 'passed': True})
    print('PASS ' + name, flush=True)

with sync_playwright() as p:
    browser = p.chromium.launch(executable_path='/usr/bin/chromium', headless=True, args=['--no-sandbox', '--disable-dev-shm-usage'])
    subprocess.run(['docker','--host=unix:///var/run/docker.sock','cp',str(Path(__file__).with_name('transport.php')),'estrabao-lab-62-restore-web:/tmp/nav-transport.php'],check=True)
    subprocess.run(['docker','--host=unix:///var/run/docker.sock','exec','-u','0','estrabao-lab-62-restore-web','chmod','644','/tmp/nav-transport.php'],check=True)
    def transport(route):
        request = route.request
        parsed = urlsplit(request.url)
        if parsed.scheme not in ['http','https'] or parsed.netloc != '127.0.0.1:18080':
            route.abort()
            return
        payload = {'method':request.method,'path':parsed.path + ('?' + parsed.query if parsed.query else ''),'headers':request.all_headers()}
        if request.post_data_buffer is not None:
            payload['body'] = base64.b64encode(request.post_data_buffer).decode()
        raw = subprocess.check_output(['docker','--host=unix:///var/run/docker.sock','exec','-i','estrabao-lab-62-restore-web','php','/tmp/nav-transport.php'],input=json.dumps(payload).encode())
        response = json.loads(raw)
        headers = {}
        for line in response['headers'].splitlines():
            if ':' in line:
                key,value=line.split(':',1)
                if key.lower() not in ['content-length','transfer-encoding','connection','content-encoding']:
                    headers[key.strip()]=value.strip()
        route.fulfill(status=response['status'],headers=headers,body=base64.b64decode(response['body']))
    sys.path.insert(0,str(ROOT / 'tests/lab/editorial'))
    from common import Session
    def login(role):
        native = Session('nav_' + role, role)
        context = browser.new_context(user_agent='EstrabaoSyntheticLab/63')
        context.route('**/*', transport)
        raw = subprocess.check_output(['docker','--host=unix:///var/run/docker.sock','exec','estrabao-lab-62-restore-web','cat','/tmp/estrabao63-http/' + native.name]).decode()
        for line in raw.splitlines():
            if line.startswith('#HttpOnly_'):
                line=line[len('#HttpOnly_'):]
            if not line or line.startswith('#'):continue
            parts=line.split('\t')
            if len(parts)==7:
                context.add_cookies([{'name':parts[5],'value':parts[6],'url':'http://127.0.0.1:18080'}])
        return context, context.new_page()
    editor_context, editor = login('admin')
    editor.goto(BASE + '/submissions#active')
    editor.wait_for_selector('.listPanel__item--submission:visible')
    check('editor receives navigation configuration', editor.evaluate('!!window.estrabaoReviewNavigation'))
    row = editor.locator('.listPanel__item--submission:visible').first
    # Native expander is the button after the view link.
    row.locator('button.expander').click()
    link = row.locator('a.estrabaoReviewNavigation')
    link.wait_for()
    submission_id = row.locator('.listPanel__item--submission__id').inner_text().strip()
    check('expanded row links to its own submission', link.get_attribute('href').endswith('submissionId=' + submission_id))
    check('accessible name includes submission id', submission_id in link.get_attribute('aria-label'))
    row.locator('button.expander').click()
    row.locator('button.expander').click()
    editor.wait_for_timeout(200)
    check('reopening menu creates one action only', row.locator('a.estrabaoReviewNavigation').count() == 1)
    # Malformed identities must fail closed instead of pointing to another record.
    row.locator('.listPanel__item--submission__id').evaluate("el => el.textContent='2&submissionId=99'")
    editor.wait_for_timeout(200)
    check('malformed row id removes action', row.locator('a.estrabaoReviewNavigation').count() == 0)
    row.locator('.listPanel__item--submission__id').evaluate("(el,id) => el.textContent=id", submission_id)
    link.wait_for()
    # Re-fetch after filtering/tab rendering, rather than retaining stale row ids.
    editor.reload()
    editor.wait_for_selector('.listPanel__item--submission:visible')
    editor.locator('.listPanel__item--submission:visible').first.locator('button.expander').click()
    editor.locator('a.estrabaoReviewNavigation:visible').first.wait_for()
    check('action returns after list reload', editor.locator('a.estrabaoReviewNavigation:visible').count() == 1)
    author_context, author = login('author')
    author.goto(BASE + '/submissions#myQueue')
    author.wait_for_selector('.listPanel__item--submission:visible')
    check('author does not receive navigation configuration', not author.evaluate('!!window.estrabaoReviewNavigation'))
    check('author has no triage action', author.locator('a.estrabaoReviewNavigation').count() == 0)
    denied = Session('navdeny','author').get('/index.php/estrabao-lab/estrabaoAdmission/review')
    check('native direct author access remains denied', 'page_estrabao_review' not in denied['body'])
    native_editor = Session('nav_stages')
    records = json.loads(native_editor.get('/index.php/estrabao-lab/api/v1/_submissions?count=100')['body'])['items']
    completed = next(x for x in records if int(x['submissionProgress']) == 0)
    pending = next(x for x in records if int(x['submissionProgress']) == 4)
    page = native_editor.get('/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=' + str(completed['id']))
    check('completed submission has explicit stage explanation', 'Esta submissão não está nessa etapa' in page['body'])
    check('completed submission offers no release form', 'id="estrabaoApaReviewForm"' not in page['body'])
    page = native_editor.get('/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=' + str(pending['id']))
    check('pending submission still opens triage', 'page_estrabao_review' in page['body'] and 'reviewNotPending' not in page['body'] and 'Esta submissão não está nessa etapa' not in page['body'])
    editor.goto(BASE + '/estrabaoAdmission/review?submissionId=' + str(pending['id']))
    identity = editor.locator('#estrabaoCurrentSubmission')
    identity.wait_for()
    check('review identifies selected submission', identity.inner_text().strip().endswith(str(pending['id'])))
    check('review shows submission title', bool(editor.locator('.estrabaoReviewTitle').inner_text().strip()))
    check('review shows bound manuscript name', 'Não foi possível identificar' not in editor.locator('section[aria-labelledby="estrabaoCurrentSubmission"]').inner_text())
    check('lookup is secondary and collapsed', not editor.locator('.estrabaoReviewLookup').evaluate('el => el.open') and not editor.locator('#estrabaoSubmissionId').is_visible())
    editor.locator('.estrabaoReviewLookup summary').click()
    check('another submission lookup remains available', editor.locator('#estrabaoSubmissionId').is_visible())
    editor.screenshot(path='/tmp/estrabao-stage65-identity.png')
    # Confirm that the contextual action really navigates, with no typed URL/id.
    editor.goto(BASE + '/submissions#active')
    row = editor.locator('.listPanel__item--submission:visible').first
    row.wait_for()
    if not row.locator('.listPanel__itemExpandedActions').count():
        row.locator('button.expander').click()
    target = row.locator('a.estrabaoReviewNavigation')
    target.wait_for()
    href = target.get_attribute('href')
    target.click()
    editor.wait_for_selector('.page_estrabao_review')
    check('native row action opens its own review page', editor.url == href)
    editor.screenshot(path='/tmp/estrabao-stage64-review.png')
    def gate(value):
        code = '$p="/var/www/html/config.inc.php";$s=file_get_contents($p);$s=preg_replace("/^admission_gate[ \t]*=.*$/m","admission_gate = ".$argv[1],$s,-1,$n);if($n!==1){exit(1);}if(file_put_contents($p,$s)===false){exit(1);}'
        subprocess.run(['docker','--host=unix:///var/run/docker.sock','exec','-u','0','estrabao-lab-62-restore-web','php','-r',code,value],check=True)
    try:
        gate('Off')
        editor.goto(BASE + '/submissions#active')
        editor.wait_for_selector('.listPanel__item--submission:visible')
        check('gate disabled omits navigation configuration', not editor.evaluate('!!window.estrabaoReviewNavigation'))
        check('gate disabled omits triage action', editor.locator('a.estrabaoReviewNavigation').count() == 0)
    finally:
        gate('On')
    browser.close()

out = ROOT / 'docs/evidence/stage65'
out.mkdir(parents=True, exist_ok=True)
(out / 'navigation-results.json').write_text(json.dumps(results, indent=2) + '\n')
