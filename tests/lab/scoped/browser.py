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
    subprocess.run(['docker','--host=unix:///var/run/docker.sock','cp',str(ROOT / 'tests/lab/navigation/transport.php'),'estrabao-lab-62-restore-web:/tmp/nav-transport.php'],check=True)
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
    ids = json.loads(Path('/workspace/scratch/estrabao-lab-63/scope-ids.json').read_text())
    target=str(ids['target'])
    code='$p="/var/www/html/config.inc.php";$s=file_get_contents($p);$s=str_replace("[estrabao]","[estrabao]\npost_submission_triage = On\ntest_submission_id = ".$argv[1]."\ntest_recipient = scope-only@synthetic.invalid",$s);file_put_contents($p,$s);'
    subprocess.run(['docker','exec','-u','0','estrabao-lab-62-restore-web','cp','-p','/var/www/html/config.inc.php','/tmp/estrabao-scoped-browser.config'],check=True)
    subprocess.run(['docker','exec','-u','0','estrabao-lab-62-restore-web','php','-r',code,target],check=True)
    try:
        context,page=login('admin')
        page.goto(BASE+'/submissions#archive')
        page.wait_for_selector('.listPanel__item--submission:visible')
        assert page.evaluate('window.estrabaoReviewNavigation.testSubmissionId')==target
        rows=page.locator('.listPanel__item--submission:visible')
        selected=None;other=None
        for row in rows.all():
            value=row.locator('.listPanel__item--submission__id').inner_text().strip()
            if value==target:selected=row
            elif other is None:other=row
        assert selected is not None and other is not None
        selected.locator('button.expander').click()
        selected.locator('a.estrabaoReviewNavigation').wait_for()
        check('dashboard exposes triage only on selected test row',selected.locator('a.estrabaoReviewNavigation').count()==1)
        other.locator('button.expander').click()
        page.wait_for_timeout(100)
        check('other dashboard row has no triage action',other.locator('a.estrabaoReviewNavigation').count()==0)
        page.screenshot(path='/tmp/estrabao-stage68-scoped-dashboard.png')
    finally:
        subprocess.run(['docker','exec','-u','0','estrabao-lab-62-restore-web','cp','-p','/tmp/estrabao-scoped-browser.config','/var/www/html/config.inc.php'],check=True)
    browser.close()
out=ROOT/'docs/evidence/stage68'
(out/'scoped-browser-results.json').write_text(json.dumps(results,indent=2)+'\n')
