"""Run one ephemeral browser on a NEW synthetic instance's private loopback."""
import json, sys
from provision import docker, BASE
case, action = sys.argv[1:]
if case not in ['fresh', 'upgrade'] or action not in ['start', 'stop']:
    raise ValueError('Synthetic case/action required')
web = 'estrabao-lab-61-' + case + '-web'
name = 'estrabao-lab-61-' + case + '-browser'
info = json.loads(docker('inspect', web))[0]
if info['HostConfig']['NetworkMode'] != 'estrabao-lab-61' or info['HostConfig']['PortBindings'] or info['HostConfig'].get('Binds'):
    raise RuntimeError('Isolation required')
if action == 'start':
    docker('run', '--pull=never', '-d', '--name', name, '--network', 'container:' + web, '--shm-size', '512m', '-e', 'SE_ENABLE_TRACING=false', '-e', 'SE_NODE_MAX_SESSIONS=2', '-e', 'SE_NODE_OVERRIDE_MAX_SESSIONS=true', 'selenium/standalone-chromium@sha256:b211549a4c35f11826bc1a7559b9f23ff055198517f8cb7d49ef74df01c5e38d')
else:
    info = json.loads(docker('inspect', name))[0]
    if info['HostConfig']['NetworkMode'] not in ['container:' + web, 'container:' + json.loads(docker('inspect', web))[0]['Id']]:
        raise RuntimeError('Unexpected browser namespace')
    docker('rm', '-fv', name)
print(case + ' browser ' + action)
