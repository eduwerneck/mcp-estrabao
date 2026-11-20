#!/usr/bin/env python3
"""Deterministic, offline release artifacts from a committed Git tree. No deployment."""
import argparse, gzip, hashlib, io, json, re, subprocess, tarfile
from pathlib import Path, PurePosixPath
import xml.etree.ElementTree as ET
ROOT = Path(__file__).resolve().parents[1]

def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT)

def digest(b):
    return hashlib.sha256(b).hexdigest()

def encode(x):
    return (json.dumps(x, ensure_ascii=False, sort_keys=True, indent=2) + '\n').encode()

def archive(files, timestamp=946684800):
    buf = io.BytesIO()
    with gzip.GzipFile(fileobj=buf, mode='wb', mtime=0, filename='') as gz:
        with tarfile.open(fileobj=gz, mode='w', format=tarfile.USTAR_FORMAT) as tf:
            for name, data in sorted(files.items()):
                t = tarfile.TarInfo(name)
                t.size = len(data)
                t.mode = 420
                t.mtime = timestamp
                t.uid = t.gid = 0
                t.uname = t.gname = ''
                tf.addfile(t, io.BytesIO(data))
    return buf.getvalue()

def build(ref, out):
    commit = git('rev-parse', '--verify', '--end-of-options', ref + '^{commit}').decode().strip()
    rows = git('ls-tree', '-rz', '--full-tree', commit, '--', 'src', 'contracts', 'plugins/generic/estrabao').split(b'\x00')
    components = {'plugin': {}, 'engine': {}}
    records = []
    for row in filter(None, rows):
        meta, raw = row.split(b'\t', 1)
        mode, kind, oid = meta.decode().split()
        name = raw.decode()
        if mode not in ['100644', '100755'] or kind != 'blob':
            raise ValueError('Non-regular product entry')
        if any((p.startswith('.') for p in PurePosixPath(name).parts)) or PurePosixPath(name).suffix not in ['.php', '.json', '.xml', '.po', '.tpl', '.js']:
            raise ValueError('Unexpected product file')
        data = git('cat-file', 'blob', oid)
        if name.startswith('plugins/generic/estrabao/'):
            component = 'plugin'
            target = 'estrabao/' + name.split('plugins/generic/estrabao/', 1)[1]
        elif name.startswith(('src/', 'contracts/')):
            component = 'engine'
            target = 'estrabao-engine/' + name
        else:
            raise ValueError('Unexpected source root')
        components[component][target] = data
        records.append({'source': name, 'component': component, 'path': target, 'size': len(data), 'sha256': digest(data)})
    plugin = ET.fromstring(components['plugin']['estrabao/version.xml']).findtext('release')
    engine = re.search(b"const ENGINE_VERSION = '([^']+)'", components['engine']['estrabao-engine/src/Reporting/Report.php'])[1].decode()
    epoch = int(git('show', '-s', '--format=%ct', commit))
    manifest = {'source_epoch': epoch, 'schema_version': 1, 'source_commit': commit, 'plugin_version': plugin, 'engine_version': engine, 'ojs_target': '3.3.0.20', 'install_mode': 'manual_two_components', 'files': records}
    out = Path(out)
    out.mkdir(parents=True, exist_ok=True)
    names = ['plugin.tar.gz', 'engine.tar.gz', 'manifest.json', 'SHA256SUMS']
    if any(((out / n).exists() or (out / n).is_symlink() for n in names)):
        raise ValueError('Release output already exists')
    for component in ['plugin', 'engine']:
        (out / (component + '.tar.gz')).write_bytes(archive(components[component], epoch))
    manifest['archives'] = {n: digest((out / n).read_bytes()) for n in names[:2]}
    (out / 'manifest.json').write_bytes(encode(manifest))
    (out / 'SHA256SUMS').write_text(''.join((digest((out / n).read_bytes()) + '  ' + n + '\n' for n in names[:3])))
    return verify(out)

def verify(directory):
    d = Path(directory)
    for n in ['manifest.json', 'plugin.tar.gz', 'engine.tar.gz', 'SHA256SUMS']:
        p = d / n
        if p.is_symlink() or not p.is_file() or p.stat().st_size > 16 * 1024 * 1024:
            raise ValueError('Invalid release file')
    m = json.loads((d / 'manifest.json').read_text())
    if m.get('schema_version') != 1 or m.get('install_mode') != 'manual_two_components' or m.get('ojs_target') != '3.3.0.20':
        raise ValueError('Unsupported manifest')
    if type(m.get('source_epoch')) is not int or not 0 < m['source_epoch'] < 2 ** 32:
        raise ValueError('Invalid source timestamp')
    if not re.fullmatch('[0-9a-f]{40}', m.get('source_commit', '')):
        raise ValueError('Invalid source commit')
    if not isinstance(m.get('files'), list) or not 1 <= len(m['files']) <= 256:
        raise ValueError('Manifest file count')
    if set(m.get('archives', {})) != {'plugin.tar.gz', 'engine.tar.gz'}:
        raise ValueError('Invalid archive inventory')
    for r in m['files']:
        component = r.get('component')
        name = r.get('path', '')
        source = r.get('source', '')
        parts = PurePosixPath(name).parts
        if component == 'plugin':
            valid = source.startswith('plugins/generic/estrabao/') and name == 'estrabao/' + source[len('plugins/generic/estrabao/'):]
        elif component == 'engine':
            valid = source.startswith(('src/', 'contracts/')) and name == 'estrabao-engine/' + source
        else:
            valid = False
        if not valid or name != str(PurePosixPath(name)) or any((p.startswith('.') for p in parts)) or (PurePosixPath(name).suffix not in ['.php', '.json', '.xml', '.po', '.tpl', '.js']):
            raise ValueError('Unsafe manifest path')
        if type(r.get('size')) is not int or not 0 <= r['size'] <= 2 * 1024 * 1024 or (not re.fullmatch('[0-9a-f]{64}', r.get('sha256', ''))):
            raise ValueError('Invalid member metadata')
    expected = {r['path']: r for r in m['files']}
    seen = set()
    if len(expected) != len(m['files']):
        raise ValueError('Duplicate manifest member')
    sums = ''.join((digest((d / n).read_bytes()) + '  ' + n + '\n' for n in ['plugin.tar.gz', 'engine.tar.gz', 'manifest.json']))
    if (d / 'SHA256SUMS').read_text() != sums:
        raise ValueError('Checksum mismatch')
    for component in ['plugin', 'engine']:
        n = component + '.tar.gz'
        if digest((d / n).read_bytes()) != m['archives'][n]:
            raise ValueError('Archive checksum mismatch')
        total = 0
        with gzip.GzipFile(fileobj=io.BytesIO((d / n).read_bytes())) as gz:
            unpacked = gz.read(16 * 1024 * 1024 + 1)
        if len(unpacked) > 16 * 1024 * 1024:
            raise ValueError('Expanded archive size limit')
        with tarfile.open(fileobj=io.BytesIO(unpacked), mode='r:') as tf:
            for t in tf:
                p = PurePosixPath(t.name)
                if not t.isfile() or p.is_absolute() or '..' in p.parts or (t.name in seen) or (t.name not in expected) or (expected[t.name]['component'] != component):
                    raise ValueError('Unsafe or unexpected archive member')
                if t.mtime != m['source_epoch'] or t.mode != 420 or t.uid != 0 or (t.gid != 0):
                    raise ValueError('Unexpected archive metadata')
                if t.size > 2 * 1024 * 1024 or total + t.size > 16 * 1024 * 1024:
                    raise ValueError('Archive size limit')
                total += t.size
                b = tf.extractfile(t).read()
                if len(b) != expected[t.name]['size'] or digest(b) != expected[t.name]['sha256']:
                    raise ValueError('Member mismatch')
                seen.add(t.name)
    if seen != set(expected):
        raise ValueError('Missing archive member')
    return {'verified': True, 'files': len(seen), 'source_commit': m['source_commit'], 'plugin': m['plugin_version'], 'engine': m['engine_version'], 'archives': m['archives']}
if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__)
    sub = p.add_subparsers(dest='command', required=True)
    b = sub.add_parser('build')
    b.add_argument('--ref', default='HEAD')
    b.add_argument('--out', required=True)
    v = sub.add_parser('verify')
    v.add_argument('directory')
    a = p.parse_args()
    print(json.dumps(build(a.ref, a.out) if a.command == 'build' else verify(a.directory), indent=2))
