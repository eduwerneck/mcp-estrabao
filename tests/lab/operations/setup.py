"""Create only NEW synthetic OJS containers; no ports, no production configuration.
Run from repository root. Secrets/logs remain in private scratch, never stdout.
"""
import json, os, secrets, subprocess, time, re, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[3]
BASE = Path('/workspace/scratch/estrabao-lab-62')
DOCKER = ['docker', '--host=unix:///var/run/docker.sock']
ENV = {k: v for k, v in os.environ.items() if k not in ['DOCKER_HOST', 'DOCKER_CONTEXT', 'DOCKER_TLS', 'DOCKER_TLS_VERIFY', 'DOCKER_CERT_PATH']}
ENV['DOCKER_HOST'] = 'unix:///var/run/docker.sock'
WEB_IMAGE = 'pkpofficial/ojs@sha256:87198f196f9f396ae0342a21dba6b9a9cf7afe35c58576758ee37998adf2c9e1'
DB_IMAGE = 'mariadb@sha256:c7d726a1c0d29e486a1ab4ef0c76c75431a11428e76c50e99ba32106cea04114'

def docker(*args, input=None):
    p = subprocess.run(DOCKER + list(args), input=input, stdout=subprocess.PIPE, stderr=subprocess.PIPE, env=ENV)
    if p.returncode:
        raise RuntimeError('Docker operation failed: ' + str(args[:2]))
    return p.stdout

def provision(case):
    if case not in ('source', 'restore'):
        raise ValueError('Synthetic cases only')
    network = json.loads(docker('network', 'inspect', 'estrabao-lab-62'))[0]
    if not network['Internal']:
        raise RuntimeError('Internal network required')
    folder = BASE / case
    folder.mkdir(mode=0o700)
    db = 'estrabao-lab-62-' + case + '-db'
    web = 'estrabao-lab-62-' + case + '-web'
    for name in [db, web]:
        if subprocess.run(DOCKER + ['inspect', name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=ENV).returncode == 0:
            raise RuntimeError('Refusing existing container')
    c = {k: secrets.token_hex(18) for k in ['admin_password', 'author_password', 'database_password', 'root_password']}
    c.update(admin_username='lab62admin', author_username='lab62author')
    (folder / 'credentials.json').write_text(json.dumps(c))
    (folder / 'credentials.json').chmod(0o600)
    env = 'MARIADB_DATABASE=estrabao_synthetic\nMARIADB_USER=synthetic\nMARIADB_PASSWORD=' + c['database_password'] + '\nMARIADB_ROOT_PASSWORD=' + c['root_password'] + '\n'
    (folder / 'db.env').write_text(env)
    (folder / 'db.env').chmod(0o600)
    docker('run', '--pull=never', '-d', '--name', db, '--network', 'estrabao-lab-62', '--env-file', str(folder / 'db.env'), DB_IMAGE)
    for _ in range(60):
        p = subprocess.run(DOCKER + ['exec', db, 'sh', '-c', 'mariadb-admin ping -uroot -p"$MARIADB_ROOT_PASSWORD" --silent'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=ENV)
        if p.returncode == 0:
            break
        time.sleep(0.5)
    else:
        raise RuntimeError('New synthetic DB unavailable')
    docker('run', '--pull=never', '-d', '--name', web, '--network', 'estrabao-lab-62', '--user', '100:101', '--security-opt', 'no-new-privileges:true', '-e', 'PHP_CLI_SERVER_WORKERS=4', '--entrypoint', 'php', WEB_IMAGE, '-d', 'sendmail_path=/bin/true', '-S', '0.0.0.0:18080', '-t', '/var/www/html')
    docker('exec', '--user', '0', web, 'sh', '-c', 'chown -R 100:101 /var/www/files /var/www/html/cache /var/www/html/public; chmod 700 /var/www/files')
    config = docker('exec', web, 'cat', '/var/www/html/config.TEMPLATE.inc.php').decode()
    config = re.sub('^base_url\\s*=.*$', 'base_url = "http://127.0.0.1:18080"', config, flags=re.M)
    config = re.sub('^\\s*;?\\s*smtp\\s*=.*$', 'smtp = Off', config, flags=re.M)
    config = re.sub('^\\s*;?\\s*allow_url_fopen\\s*=.*$', 'allow_url_fopen = Off', config, flags=re.M)
    (folder / 'config.inc.php').write_text(config)
    (folder / 'config.inc.php').chmod(0o600)
    docker('cp', str(folder / 'config.inc.php'), web + ':/var/www/html/config.inc.php')
    docker('exec', '--user', '0', web, 'chown', '100:101', '/var/www/html/config.inc.php')
    answers = ['pt_BR', 'en_US,es_ES', 'utf-8', 'utf8', '/var/www/files', c['admin_username'], c['admin_password'], c['admin_password'], 'admin@synthetic.invalid', 'mysqli', db, 'synthetic', c['database_password'], 'estrabao_synthetic', 'lab62.synthetic.invalid', 'n', 'y', '']
    log = docker('exec', '-i', '-w', '/var/www/html', web, 'php', '-d', 'sendmail_path=/bin/true', 'tools/install.php', input='\n'.join(answers).encode())
    (folder / 'install.log').write_bytes(log)
    (folder / 'install.log').chmod(0o600)
    if b'Successfully installed version 3.3.0.20' not in log:
        raise RuntimeError('Installer did not confirm target version; inspect private log')
    config = docker('exec', web, 'cat', '/var/www/html/config.inc.php').decode()
    config = re.sub('^allowed_hosts\\s*=.*$', 'allowed_hosts = \'["127.0.0.1"]\'', config, flags=re.M)
    config += '\n[estrabao]\nadmission_gate = Off\nengine_bootstrap = /opt/estrabao-engine/src/bootstrap.php\nreview_dir = /var/www/files/estrabao-review\n'
    (folder / 'config.inc.php').write_text(config)
    docker('cp', str(folder / 'config.inc.php'), web + ':/var/www/html/config.inc.php')
    docker('exec', '--user', '0', web, 'chown', 'root:101', '/var/www/html/config.inc.php')
    docker('exec', '--user', '0', web, 'chmod', '440', '/var/www/html/config.inc.php')
    docker('exec', web, 'mkdir', '-m', '700', '/var/www/files/estrabao-review')
    docker('cp', str(folder / 'credentials.json'), web + ':/tmp/lab-credentials.json')
    for script in ['seed.php', 'http-bridge.php', 'state-query.php', 'replace.php']:
        docker('cp', str(ROOT / 'tests/lab' / script), web + ':/tmp/' + script)
        docker('exec', '--user', '0', web, 'chown', 'root:101', '/tmp/' + script)
        docker('exec', '--user', '0', web, 'chmod', '640', '/tmp/' + script)
    docker('exec', '--user', '0', web, 'chown', '100:101', '/tmp/lab-credentials.json')
    docker('exec', '--user', '0', web, 'chmod', '600', '/tmp/lab-credentials.json')
    print(json.dumps({'case': case, 'ojs': '3.3.0.20', 'internal_only': True, 'new_database': True}))
if __name__ == '__main__':
    provision(sys.argv[1])
    web = 'estrabao-lab-62-' + sys.argv[1] + '-web'
    sys.path.insert(0, str(ROOT / 'tools'))
    import release
    package = Path('/workspace/scratch/estrabao-lab-61/release-current')
    release.verify(package)
    for component, dest in [('plugin', '/var/www/html/plugins/generic'), ('engine', '/opt')]:
        docker('cp', str(package / (component + '.tar.gz')), web + ':/tmp/' + component + '.tar.gz')
        docker('exec', '--user', '0', web, 'tar', '-xzf', '/tmp/' + component + '.tar.gz', '-C', dest)
    docker('exec', '-w', '/var/www/html', web, 'php', 'lib/pkp/tools/installPluginVersion.php', 'plugins/generic/estrabao/version.xml')
    docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/seed.php')
    (BASE / sys.argv[1] / 'state.json').write_bytes(docker('exec', web, 'cat', '/tmp/lab-state.json'))
