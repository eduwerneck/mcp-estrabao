"""Cross-UID privacy and killed pre-commit ledger writer in isolated target."""
from http_client import *
import subprocess
private = json.loads(docker('exec', '--user', '65534:65534', web, 'php', '-r', "echo json_encode(['config'=>is_readable('/var/www/html/config.inc.php'),'private_files'=>is_readable('/var/www/files'),'review'=>is_writable('/var/www/files/estrabao-review')]);"))
check('other UID cannot read private configuration', not private['config'])
check('other UID cannot traverse manuscript directory', not private['private_files'])
check('other UID cannot write private review directory', not private['review'])
path = '/var/www/files/estrabao-review/review-' + str(state['journal_id']) + '-' + str(ids['review']) + '.json'
before = docker('exec', web, 'cat', path)
code = 'chdir(\'/var/www/html\');require \'tools/bootstrap.inc.php\';new CommandLineTool([]);require \'plugins/generic/estrabao/AdmissionReviewStore.inc.php\';(new EstrabaoAdmissionReviewStore())->transaction((int)$argv[1],(int)$argv[2],function(&$l){$e=end($l[\'events\']);$e[\'sequence\']=count($l[\'events\'])+1;$e[\'action\']=\'revoke\';$l[\'events\'][]=$e;echo "READY\\n";fflush(STDOUT);if(fgetc(STDIN)===\'k\'){exec(\'kill -9 \'.getmypid());}throw new RuntimeException(\'Synthetic abort\');});'
p = subprocess.Popen(DOCKER + ['exec', '-i', web, 'php', '-r', code, str(state['journal_id']), str(ids['review'])], stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=ENV)
try:
    if p.stdout.readline() != b'READY\n':
        raise RuntimeError('Synthetic writer did not acquire lock')
    p.communicate(b'k', timeout=10)
    check('killed pre-commit writer preserves exact ledger bytes', p.returncode != 0 and docker('exec', web, 'cat', path) == before)
finally:
    if p.poll() is None:
        p.communicate(b'q', timeout=10)
code = "chdir('/var/www/html');require 'tools/bootstrap.inc.php';new CommandLineTool([]);require 'plugins/generic/estrabao/AdmissionReviewStore.inc.php';(new EstrabaoAdmissionReviewStore())->transaction((int)$argv[1],(int)$argv[2],function(&$l){echo 'unlocked';});"
check('OS releases dead writer lock for next transaction', docker('exec', web, 'php', '-r', code, str(state['journal_id']), str(ids['review'])).strip() == b'unlocked')
(folder / 'permissions-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'cross_uid': 65534, 'pre_commit_process_kill_only': True, 'power_loss_atomicity_certified': False}, indent=2))
