"""Prepare NEW named stage61 synthetic installation after artifact build."""
import sys
from provision import provision, BASE, ROOT, docker
from package import install
case = sys.argv[1]
provision(case)
install(case, 'release-current' if case == 'fresh' else 'release-previous')
web = 'estrabao-lab-61-' + case + '-web'
docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/seed.php')
(BASE / case / 'state.json').write_bytes(docker('exec', web, 'cat', '/tmp/lab-state.json'))
