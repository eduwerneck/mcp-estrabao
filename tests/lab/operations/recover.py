"""Native backup integrity/restore to a NEW isolated database and volumes."""
import json, sys, shutil
from pathlib import Path
import backup
from setup import docker, BASE
results = []

def check(name, ok):
    print(('PASS ' if ok else 'FAIL ') + name, flush=True)
    results.append({'case': name, 'passed': bool(ok)})
    (BASE / 'recovery-results.json').write_text(json.dumps(results, indent=2))
    if not ok:
        raise AssertionError(name)
source = backup.target('source')[0]
target = backup.target('restore')[0]
check('source remains stopped for consistent backup', not json.loads(docker('inspect', source))[0]['State']['Running'])
backup.verify()
check('complete backup verifies', True)
docker('stop', target)
before = backup.dump('restore')
corrupt = BASE / 'corrupt-backup'
shutil.copytree(backup.SNAPSHOT, corrupt)
(corrupt / 'database.sql').write_bytes(b'corrupt-synthetic')
try:
    backup.restore(corrupt)
    raise AssertionError('Corrupt snapshot accepted')
except ValueError:
    pass
check('corrupt backup refused before changing target database', backup.dump('restore') == before)
backup.restore()
check('restored native database exactly matches quiesced SQL', backup.dump('restore') == (backup.SNAPSHOT / 'database.sql').read_bytes())
m = backup.verify()
for name, path in [('files', '/var/www/files'), ('public', '/var/www/html/public')]:
    restored = docker('cp', target + ':' + path, '-')
    check(name + ' restored with bytes ownership and modes', backup.inventory(restored, name) == m['inventory'][name])
source_info = json.loads(docker('inspect', source))[0]
target_info = json.loads(docker('inspect', target))[0]
check('restoration uses different private volumes', set((x['Name'] for x in source_info['Mounts'])).isdisjoint((x['Name'] for x in target_info['Mounts'])))
check('restored application still runs as UID 100', docker('exec', target, 'id', '-u').strip() == b'100')
(BASE / 'recovery-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'source_quiesced': True, 'target_new_database_and_volumes': True, 'production_accessed': False}, indent=2))
