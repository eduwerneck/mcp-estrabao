"""Real-browser installation/upgrade, exclusively new stage-61 synthetic containers."""
import json, sys, time, re
from pathlib import Path
from provision import BASE, ROOT, docker
from package import install
sys.path.insert(0, str(ROOT / 'tests/lab'))
import browser
case = sys.argv[1]
if case not in ['fresh', 'upgrade']:
    raise ValueError('Unknown synthetic case')
web = 'estrabao-lab-61-' + case + '-web'
folder = BASE / case
browser.ROOT = folder
results = []

def check(name, ok):
    print(('PASS ' if ok else 'FAIL ') + case + ': ' + name, flush=True)
    results.append({'case': name, 'passed': bool(ok)})
    (folder / (sys.argv[2] + '-results.json' if len(sys.argv) > 2 else 'results.json')).write_text(json.dumps(results, indent=2))
    if not ok:
        raise AssertionError(name)

def wd(method, path, data=None):
    p = {'method': method, 'path': path}
    if data is not None:
        p['data'] = data
    value = json.loads(docker('exec', '-i', web, 'php', '/tmp/http-bridge.php', input=json.dumps(p).encode()))['value']
    if isinstance(value, dict) and 'error' in value:
        raise RuntimeError(value['error'] + ': ' + value.get('message', '')[:200])
    return value
browser.wd = wd
docker('cp', str(Path(__file__).with_name('inspect.php')), web + ':/tmp/install-inspect.php')

def inspect(mode='inspect'):
    return json.loads(docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/install-inspect.php', mode))

def gate(on=True, engine=True):
    p = folder / 'config.inc.php'
    s = p.read_text()
    s = re.sub('^admission_gate\\s*=.*$', 'admission_gate = ' + ('On' if on else 'Off'), s, flags=re.M)
    s = re.sub('^engine_bootstrap\\s*=.*$', 'engine_bootstrap = ' + ('/opt/estrabao-engine/src/bootstrap.php' if engine else '/opt/absent-engine.php'), s, flags=re.M)
    p.write_text(s)
    docker('cp', str(p), web + ':/var/www/html/config.inc.php')
state = json.loads((folder / 'state.json').read_text())
ids = {k: v['id'] for k, v in state['submissions'].items()}
base = 'http://127.0.0.1:18080/index.php/estrabao-lab'

def wait(b, script):
    for _ in range(60):
        if b.js(script):
            return
        time.sleep(0.1)
    raise AssertionError('Browser condition timed out')

def view(b, kind='review'):
    b.get(base + '/estrabaoAdmission/review?submissionId=' + str(ids[kind]))

def approve(b):
    b.js('let f=document.querySelector("#estrabaoApaReviewForm");f.querySelector("[name=apaReviewed]").checked=true;f.querySelector("button[value=approve]").click()')
    wait(b, 'return document.body.innerText.includes("Decisão editorial registrada.")')

def finish(a, kind, valid=True):
    a.get(base + '/submission/wizard/4?submissionId=' + str(ids[kind]))
    wait(a, 'return !!document.querySelector("#submitStep4Form")')
    return a.call('POST', '/execute/async', {'script': 'const done=arguments[arguments.length-1];const f=document.querySelector("#submitStep4Form");const p=new URLSearchParams(new FormData(f));if(!arguments[0])p.set("csrfToken","invalid");fetch(f.action,{method:"POST",body:p}).then(async r=>done(await r.text()));', 'args': [valid]})

def progress(kind):
    return next((s['submission_progress'] for s in inspect()['submissions'] if int(s['submission_id']) == int(ids[kind])))

def generate(b):
    b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit()')
    wait(b, 'return !!document.querySelector("#estrabaoEditorialForm")')
