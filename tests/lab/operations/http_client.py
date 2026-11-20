"""Authenticated native HTTP over private loopback; no browser or external ports."""
import json, sys, re, time, secrets
from html.parser import HTMLParser
from pathlib import Path
from setup import BASE, ROOT, DOCKER, ENV, docker
case = sys.argv[1]
if case not in ['source', 'restore']:
    raise ValueError('Synthetic case required')
web = 'estrabao-lab-62-' + case + '-web'
folder = BASE / case
state = json.loads((folder / 'state.json').read_text())
ids = {k: v['id'] for k, v in state['submissions'].items()}
base = '/index.php/estrabao-lab'
results = []

def check(name, ok):
    print(('PASS ' if ok else 'FAIL ') + name, flush=True)
    results.append({'case': name, 'passed': bool(ok)})
    (folder / (sys.argv[2] + '-results.json')).write_text(json.dumps(results, indent=2))
    if not ok:
        raise AssertionError(name)

def copy_helper(source, target):
    docker('cp', str(source), web + ':/tmp/' + target)
    docker('exec', '--user', '0', web, 'chown', 'root:101', '/tmp/' + target)
    docker('exec', '--user', '0', web, 'chmod', '640', '/tmp/' + target)
copy_helper(Path(__file__).with_name('http.php'), 'operations-http.php')
copy_helper(ROOT / 'tests/lab/install/inspect.php', 'operations-inspect.php')

def inspect():
    return json.loads(docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/operations-inspect.php'))

def config_gate(enabled):
    p = folder / 'config.inc.php'
    s = re.sub('^admission_gate\\s*=.*$', 'admission_gate = ' + ('On' if enabled else 'Off'), p.read_text(), flags=re.M)
    p.write_text(s)
    docker('cp', str(p), web + ':/var/www/html/config.inc.php')
    docker('exec', '--user', '0', web, 'chown', 'root:101', '/var/www/html/config.inc.php')
    docker('exec', '--user', '0', web, 'chmod', '440', '/var/www/html/config.inc.php')

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
        out = docker('exec', '-i', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/operations-http.php', input=json.dumps({'session': self.name, 'method': method, 'path': path, 'data': data or {}}).encode())
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

def progress(kind):
    return next((s['submission_progress'] for s in inspect()['submissions'] if int(s['submission_id']) == int(ids[kind])))
if __name__ == '__main__':
    config_gate(True)
    s = Session('probe')
    page = s.view()
    print('review forms', list(Forms(page['body']).forms))
    page = s.get(base + '/submission/wizard/4?submissionId=' + str(ids['review']))
    print('wizard forms', list(Forms(page['body']).forms))
    a = Session('probeauthor', 'author')
    page = a.get(base + '/submission/step/4?submissionId=' + str(ids['review']))
    print('step response keys', list(json.loads(page['body'])))
    print('step forms', list(Forms(json.loads(page['body'])['content']).forms))
    print('draft forms', list(Forms(s.draft()['body']).forms))
