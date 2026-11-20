"""Install verified artifacts only into explicitly named stage-61 synthetic containers."""
import importlib.util, json, sys
from pathlib import Path
from provision import docker, ROOT, BASE
spec = importlib.util.spec_from_file_location('release', ROOT / 'tools/release.py')
r = importlib.util.module_from_spec(spec)
spec.loader.exec_module(r)

def install(case, release):
    if case not in ('fresh', 'upgrade'):
        raise ValueError('Synthetic target required')
    directory = BASE / release
    info = r.verify(directory)
    web = 'estrabao-lab-61-' + case + '-web'
    native = json.loads(docker('inspect', web))[0]
    if native['HostConfig']['PortBindings'] or native['HostConfig'].get('Binds') or native['HostConfig']['NetworkMode'] != 'estrabao-lab-61':
        raise RuntimeError('Isolation preflight failed')
    docker('exec', web, 'sh', '-c', 'rm -rf /var/www/html/plugins/generic/estrabao /opt/estrabao-engine')
    for n in ['plugin.tar.gz', 'engine.tar.gz']:
        docker('cp', str(directory / n), web + ':/tmp/' + n)
    docker('exec', web, 'tar', '-xzf', '/tmp/plugin.tar.gz', '-C', '/var/www/html/plugins/generic')
    docker('exec', web, 'tar', '-xzf', '/tmp/engine.tar.gz', '-C', '/opt')
    output = docker('exec', '-w', '/var/www/html', web, 'php', 'lib/pkp/tools/installPluginVersion.php', 'plugins/generic/estrabao/version.xml')
    (BASE / case / ('register-' + release + '.log')).write_bytes(output)
    m = json.loads((directory / 'manifest.json').read_text())
    for row in m['files']:
        target = '/var/www/html/plugins/generic/' + row['path'] if row['component'] == 'plugin' else '/opt/' + row['path']
        if r.digest(docker('exec', web, 'cat', target)) != row['sha256']:
            raise RuntimeError('Installed file mismatch')
    print(json.dumps({'case': case, 'installed_verified_files': len(m['files']), 'plugin': info['plugin'], 'engine': info['engine']}))
    return info
if __name__ == '__main__':
    install(*sys.argv[1:])
