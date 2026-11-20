"""Quiesced, synthetic-only backup/restore rehearsal. Never a production utility."""
import hashlib, io, json, re, sys, tarfile
from pathlib import Path, PurePosixPath
from setup import docker, BASE, WEB_IMAGE
SNAPSHOT = BASE / 'backup'
COMPONENTS = ['database.sql', 'files.tar', 'public.tar', 'config.inc.php', 'state.json']

def sha(b):
    return hashlib.sha256(b).hexdigest()

def target(case):
    if case not in ['source', 'restore']:
        raise ValueError('Synthetic case required')
    web = 'estrabao-lab-62-' + case + '-web'
    db = 'estrabao-lab-62-' + case + '-db'
    for name in [web, db]:
        c = json.loads(docker('inspect', name))[0]
        if c['HostConfig']['NetworkMode'] != 'estrabao-lab-62' or c['HostConfig']['PortBindings'] or c['HostConfig'].get('Binds'):
            raise ValueError('Isolation required')
    if not json.loads(docker('network', 'inspect', 'estrabao-lab-62'))[0]['Internal']:
        raise ValueError('Internal network required')
    return (web, db)

def quiet(case):
    web, db = target(case)
    if json.loads(docker('inspect', web))[0]['State']['Running']:
        raise ValueError('Application must be stopped for consistent snapshot/restore')
    return (web, db)

def dump(case):
    _, db = target(case)
    return docker('exec', db, 'sh', '-c', 'exec mariadb-dump --single-transaction --skip-comments --skip-dump-date --order-by-primary --default-character-set=utf8mb4 -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"')

def inventory(data, root):
    if len(data) > 64 * 1024 * 1024:
        raise ValueError('Archive limit')
    entries = {}
    with tarfile.open(fileobj=io.BytesIO(data), mode='r:') as tf:
        for t in tf:
            p = PurePosixPath(t.name)
            if p.is_absolute() or '..' in p.parts or (not p.parts) or (p.parts[0] != root) or (not (t.isfile() or t.isdir())) or (t.name in entries) or (len(entries) >= 4096):
                raise ValueError('Unsafe backup member')
            if not 0 <= t.size <= 16 * 1024 * 1024:
                raise ValueError('Member limit')
            entries[t.name] = {'sha256': sha(tf.extractfile(t).read()) if t.isfile() else None, 'uid': t.uid, 'gid': t.gid, 'mode': t.mode, 'size': t.size}
    return entries

def verify(folder=SNAPSHOT):
    folder = Path(folder)
    if folder.is_symlink() or not folder.is_dir() or folder.stat().st_mode & 0o077:
        raise ValueError('Private backup directory required')
    mp = folder / 'manifest.json'
    if mp.is_symlink() or not mp.is_file() or mp.stat().st_size > 4 * 1024 * 1024 or mp.stat().st_mode & 0o077:
        raise ValueError('Invalid backup manifest')
    m = json.loads(mp.read_text())
    if not m.get('quiesced') or (m.get('ojs'), m.get('plugin'), m.get('engine')) != ('3.3.0.20', '0.39.0.0', '0.44.0'):
        raise ValueError('Unsupported snapshot versions or quiescence state')
    if m.get('schema') != 1 or m.get('lab') != 'estrabao-lab-62' or set(m.get('components', {})) != set(COMPONENTS):
        raise ValueError('Unsupported backup')
    for n, info in m['components'].items():
        p = folder / n
        if p.is_symlink() or not p.is_file() or p.stat().st_mode & 0o077 or (p.stat().st_size > 64 * 1024 * 1024):
            raise ValueError('Invalid private component')
        b = p.read_bytes()
        if len(b) != info['size'] or sha(b) != info['sha256']:
            raise ValueError('Backup checksum mismatch')
    for name, root in [('files.tar', 'files'), ('public.tar', 'public')]:
        if inventory((folder / name).read_bytes(), root) != m['inventory'][root]:
            raise ValueError('Backup inventory mismatch')
    return m

def snapshot():
    web, _ = quiet('source')
    SNAPSHOT.mkdir(mode=0o700)
    for n in ['files', 'public']:
        (SNAPSHOT / (n + '.tar')).write_bytes(docker('cp', web + ':/var/www/' + ('html/public' if n == 'public' else 'files'), '-'))
    (SNAPSHOT / 'database.sql').write_bytes(dump('source'))
    docker('cp', web + ':/var/www/html/config.inc.php', str(SNAPSHOT / 'config.inc.php'))
    (SNAPSHOT / 'state.json').write_bytes((BASE / 'source/state.json').read_bytes())
    for n in COMPONENTS:
        (SNAPSHOT / n).chmod(0o600)
    m = {'schema': 1, 'lab': 'estrabao-lab-62', 'quiesced': True, 'ojs': '3.3.0.20', 'plugin': '0.39.0.0', 'engine': '0.44.0', 'components': {n: {'sha256': sha((SNAPSHOT / n).read_bytes()), 'size': (SNAPSHOT / n).stat().st_size} for n in COMPONENTS}, 'inventory': {n: inventory((SNAPSHOT / (n + '.tar')).read_bytes(), n) for n in ['files', 'public']}}
    (SNAPSHOT / 'manifest.json').write_text(json.dumps(m, indent=2))
    (SNAPSHOT / 'manifest.json').chmod(0o600)
    verify()
    return m

def copy_owned(web, dest, name, data, uid, gid, mode):
    buf = io.BytesIO()
    with tarfile.open(fileobj=buf, mode='w') as tf:
        t = tarfile.TarInfo(name)
        t.uid = uid
        t.gid = gid
        t.mode = mode
        t.size = len(data)
        tf.addfile(t, io.BytesIO(data))
    docker('cp', '-a', '-', web + ':' + dest, input=buf.getvalue())

def restore(folder=SNAPSHOT):
    m = verify(folder)
    folder = Path(folder)
    web, db = quiet('restore')
    source_web, _ = target('source')
    source_mounts = {x['Name'] for x in json.loads(docker('inspect', source_web))[0]['Mounts']}
    target_mounts = {x['Name'] for x in json.loads(docker('inspect', web))[0]['Mounts']}
    if not source_mounts.isdisjoint(target_mounts):
        raise ValueError('Restoration cannot share source volumes')
    docker('exec', '-i', db, 'sh', '-c', 'exec mariadb --default-character-set=utf8mb4 -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"', input=(folder / 'database.sql').read_bytes())
    for name, dest in [('files', '/var/www'), ('public', '/var/www/html')]:
        absolute = dest + '/' + name
        docker('run', '--pull=never', '--rm', '--network', 'none', '--volumes-from', web, '--entrypoint', 'sh', WEB_IMAGE, '-c', 'find ' + absolute + ' -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +')
        docker('run', '--pull=never', '--rm', '-i', '--network', 'none', '--volumes-from', web, '--entrypoint', 'tar', WEB_IMAGE, '-xpf', '-', '-C', dest, input=(folder / (name + '.tar')).read_bytes())
    source_config = (folder / 'config.inc.php').read_text()
    target_config = (BASE / 'restore/config.inc.php').read_text()
    section = '(?ms)^\\[database\\]\\n.*?(?=^\\[|\\Z)'
    replacement = re.search(section, target_config).group()
    config = re.sub(section, lambda _: replacement, source_config)
    p = BASE / 'restore/config.inc.php'
    p.write_text(config)
    p.chmod(0o600)
    copy_owned(web, '/var/www/html', 'config.inc.php', config.encode(), 0, 101, 0o440)
    (BASE / 'restore/state.json').write_bytes((folder / 'state.json').read_bytes())
    copy_owned(web, '/tmp', 'lab-state.json', (folder / 'state.json').read_bytes(), 100, 101, 0o600)
    c = json.loads((BASE / 'restore/credentials.json').read_text())
    old = json.loads((BASE / 'source/credentials.json').read_text())
    for k in ['admin_username', 'admin_password', 'author_username', 'author_password']:
        c[k] = old[k]
    (BASE / 'restore/credentials.json').write_text(json.dumps(c))
    docker('start', web)
    return m
if __name__ == '__main__':
    action = sys.argv[1]
    if action == 'snapshot':
        m = snapshot()
    elif action == 'verify':
        m = verify()
    elif action == 'restore':
        m = restore()
    else:
        raise ValueError('Unknown action')
    print(json.dumps({'action': action, 'verified': True, 'component_count': len(m['components']), 'private_members': len(m['inventory']['files'])}))
