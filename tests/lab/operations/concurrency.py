"""Actual concurrent native HTTP requests and external flock, non-root OJS."""
from http_client import *
import concurrent.futures, subprocess, threading

def pair(jobs):
    barrier = threading.Barrier(len(jobs))

    def run(fn):
        barrier.wait()
        return fn()
    with concurrent.futures.ThreadPoolExecutor(max_workers=len(jobs)) as pool:
        return list(pool.map(run, jobs))

def ledger(prefix, kind='review'):
    return '/var/www/files/estrabao-review/' + prefix + '-' + str(state['journal_id']) + '-' + str(ids[kind]) + '.json'

def read(path):
    return json.loads(docker('exec', web, 'cat', path))

def lock_test(path, fn):
    code = '$h=fopen($argv[1],"c+b");if(!flock($h,LOCK_EX)){exit(1);}echo "READY\\n";fflush(STDOUT);fgetc(STDIN);'
    proc = subprocess.Popen(DOCKER + ['exec', '-i', web, 'php', '-r', code, path + '.lock'], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=ENV)
    try:
        if proc.stdout.readline() != b'READY\n':
            raise RuntimeError('Lock holder unavailable')
        return fn()
    finally:
        proc.communicate(b'q', timeout=10)
config_gate(True)
check('web runtime UID is unprivileged', docker('exec', web, 'id', '-u').strip() == b'100')
perms = json.loads(docker('exec', web, 'php', '-r', "echo json_encode(['code'=>is_writable('/var/www/html/plugins/generic/estrabao/version.xml'),'engine'=>is_writable('/opt/estrabao-engine/src/bootstrap.php'),'config'=>is_writable('/var/www/html/config.inc.php'),'files'=>is_writable('/var/www/files'),'cache'=>is_writable('/var/www/html/cache'),'public'=>is_writable('/var/www/html/public')]);"))
check('service cannot write code engine or config', not any((perms[k] for k in ['code', 'engine', 'config'])))
check('service writes runtime directories', all((perms[k] for k in ['files', 'cache', 'public'])))
b = Session('editor1')
c = Session('editor2')
a = Session('author', 'author')
forms = [w.form(w.view(), 'estrabaoApaReviewForm') for w in [b, c]]
pair([lambda: b.post(forms[0]['action'], {**forms[0]['data'], 'reviewAction': 'approve', 'apaReviewed': '1'}), lambda: c.post(forms[1]['action'], {**forms[1]['data'], 'reviewAction': 'approve', 'apaReviewed': '1'})])
path = ledger('review')
first = read(path)
check('two simultaneous approvals append exactly one event', len(first['events']) == 1)
forms = [w.form(w.draft(), 'estrabaoEditorialForm') for w in [b, c]]
pair([lambda: b.post(forms[0]['action'], {**forms[0]['data'], 'editorialReviewed': '1'}), lambda: c.post(forms[1]['action'], {**forms[1]['data'], 'editorialReviewed': '1'})])
editorial = ledger('editorial')
before = read(editorial)
check('two simultaneous checklist saves append one event', len(before['events']) == 1)
check('checklist race preserves APA ledger', read(path) == first)
for prefix, formid, marker in [('review', 'estrabaoApaReviewForm', 'Decisão editorial registrada.'), ('editorial', 'estrabaoEditorialForm', 'Conferência editorial registrada.')]:
    f = b.form(b.view() if prefix == 'review' else b.draft(), formid)
    data = {**f['data'], **({'reviewAction': 'revoke'} if prefix == 'review' else {'editorialReviewed': '1'})}
    target = ledger(prefix)
    before = read(target)
    response = lock_test(target, lambda: b.post(f['action'], data))
    check(prefix + ' external lock refuses write', read(target) == before and marker not in response['body'])
for target in [path, editorial]:
    check('private ledger and lock are 0600: ' + Path(target).name, docker('exec', web, 'stat', '-c', '%a', target, target + '.lock').split() == [b'600', b'600'])
f = b.form(b.view('replace'), 'estrabaoApaReviewForm')
data = {**f['data'], 'reviewAction': 'approve', 'apaReviewed': '1'}
pair([lambda: b.post(f['action'], data), lambda: docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/replace.php')])
check('concurrent server file change invalidates old approval', 'Há liberação editorial válida' not in b.view('replace')['body'])
finish(a, 'replace')
check('changed file cannot complete from raced approval', progress('replace') == 4)
f = b.form(b.draft(), 'estrabaoEditorialForm')
before = read(editorial)
php = "chdir('/var/www/html');require 'tools/bootstrap.inc.php';new CommandLineTool([]);$s=Services::get('submission')->get((int)$argv[1]);$p=$s->getCurrentPublication();$p->setData('title','Título sintético alterado','es_ES');DAORegistry::getDAO('PublicationDAO')->updateObject($p);"
pair([lambda: b.post(f['action'], {**f['data'], 'editorialReviewed': '1'}), lambda: docker('exec', web, 'php', '-r', php, str(ids['review']))])
page = b.draft()
check('concurrent metadata change leaves checklist stale', 'id="estrabaoEditorialStale"' in page['body'])
check('metadata change retains independent APA release', 'Há liberação editorial válida' in page['body'])
check('metadata race retains history prefix', read(editorial)['events'][:len(before['events'])] == before['events'])
f = b.form(page, 'estrabaoEditorialForm')
r = b.post(f['action'], {**f['data'], 'editorialReviewed': '1'})
check('fresh snapshot receives new explicit human review', 'Conferência editorial registrada.' in r['body'])
r = finish(a, 'abnt')
check('ABNT remains blocked in non-root multiworker runtime', progress('abnt') == 4)
(folder / 'concurrency-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'uid': 100, 'gid': 101, 'php_workers': 4, 'actual_parallel_requests': True, 'joint_upload_admission_transaction_certified': False}, indent=2))
