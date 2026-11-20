"""Authenticated native HTTP over private loopback; no browser or external ports."""
import json, sys, re, time, secrets, subprocess
from html.parser import HTMLParser
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'operations'))
from setup import ROOT, DOCKER, ENV, docker
BASE = Path('/workspace/scratch/estrabao-lab-63')
web = 'estrabao-lab-62-restore-web'
folder = BASE
# Verify the fixed, synthetic restored instance before issuing any native request.
for target in [web, 'estrabao-lab-62-restore-db']:
    container = json.loads(docker('inspect', target))[0]
    if not container['State']['Running'] or container['HostConfig']['NetworkMode'] != 'estrabao-lab-62' or container['HostConfig']['PortBindings'] or container['HostConfig'].get('Binds'):
        raise RuntimeError('Isolated running stage62 restore instance required')
if not json.loads(docker('network', 'inspect', 'estrabao-lab-62'))[0]['Internal']:
    raise RuntimeError('Internal network required')
application = json.loads(docker('inspect', web))[0]
if application['Config']['User'] != '100:101' or 'sendmail_path=/bin/true' not in application['Config']['Cmd']:
    raise RuntimeError('Non-root runtime and neutralized sendmail required')
if folder.stat().st_mode & 0o077 or (folder / 'credentials.json').stat().st_mode & 0o077:
    raise RuntimeError('Private synthetic credential storage required')
for attempt in range(60):
    ready = subprocess.run(DOCKER + ['exec', 'estrabao-lab-62-restore-db', 'sh', '-c', 'mariadb-admin ping -uroot -p"$MARIADB_ROOT_PASSWORD" --silent'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=ENV)
    if ready.returncode == 0:
        break
    time.sleep(0.5)
else:
    raise RuntimeError('Synthetic database did not become ready')
state = {}
ids = {}
base = '/index.php/estrabao-lab'
results = []

def check(name, ok):
    print(('PASS ' if ok else 'FAIL ') + name, flush=True)
    results.append({'case': name, 'passed': bool(ok)})
    (folder / 'results.json').write_text(json.dumps(results, indent=2))
    if not ok:
        raise AssertionError(name)

def copy_helper(source, target):
    docker('cp', str(source), web + ':/tmp/' + target)
    docker('exec', '--user', '0', web, 'chown', 'root:101', '/tmp/' + target)
    docker('exec', '--user', '0', web, 'chmod', '640', '/tmp/' + target)
copy_helper(Path(__file__).with_name('http.php'), 'editorial-http.php')

class Forms(HTMLParser):

    def __init__(self, body):
        super().__init__()
        self.forms = {}
        self.current = None
        self.select = None
        self.feed(body)

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'form':
            self.current = {'action': a.get('action'), 'data': {}}
            self.forms[a.get('id', '')] = self.current
        if self.current is None:
            return
        if tag == 'input' and a.get('name') and (a.get('type') not in ['button', 'submit']):
            if a.get('type') in ['checkbox', 'radio'] and 'checked' not in a:
                return
            self.current['data'][a['name']] = a.get('value', '')
        if tag == 'select':
            self.select = a.get('name')
        if tag == 'option' and self.select and ('selected' in a):
            self.current['data'][self.select] = a.get('value', '')

    def handle_endtag(self, tag):
        if tag == 'form':
            self.current = None
        if tag == 'select':
            self.select = None

class Session:

    def __init__(self, name, role='admin'):
        self.name = name + '_' + secrets.token_hex(4)
        c = json.loads((folder / 'credentials.json').read_text())
        page = self.get('/index.php/index/login')
        forms = Forms(page['body']).forms
        f = next((x for x in forms.values() if 'username' in x['data']))
        self.post(f['action'], {**f['data'], 'username': c[role + '_username'], 'password': c[role + '_password']})

    def call(self, method, path, data=None):
        if path.startswith('http://127.0.0.1:18080'):
            path = path[len('http://127.0.0.1:18080'):]
        out = docker('exec', '-i', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/editorial-http.php', input=json.dumps({'session': self.name, 'method': method, 'path': path, 'data': data or {}}).encode())
        return json.loads(out)

    def get(self, path):
        return self.call('GET', path)

    def post(self, path, data):
        return self.call('POST', path, data)

    def view(self, kind='review'):
        return self.get(base + '/estrabaoAdmission/review?submissionId=' + str(ids[kind]))

    def form(self, page, name):
        return Forms(page['body']).forms[name]

    def approve(self, kind='review'):
        f = self.form(self.view(kind), 'estrabaoApaReviewForm')
        return self.post(f['action'], {**f['data'], 'reviewAction': 'approve', 'apaReviewed': '1'})

    def draft(self, kind='review'):
        f = self.form(self.view(kind), '')
        return self.post(f['action'], {**f['data'], 'manuscriptType': 'article'})

def finish(session, kind, valid=True):
    page = session.get(base + '/submission/step/4?submissionId=' + str(ids[kind]))
    content = json.loads(page['body'])['content']
    f = Forms(content).forms['submitStep4Form']
    data = dict(f['data'])
    if not valid:
        data['csrfToken'] = 'invalid-synthetic'
    return session.post(f['action'], data)
