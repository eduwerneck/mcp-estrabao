"""Sequential integration runner for the existing internal synthetic lab only.
Does not provision, install or upgrade. Default suites are read-only/reversible.
--with-apa-regression adds the targeted APA suites; --with-submission-cycle creates fresh synthetic drafts and records APA review/
completion only in the internal lab; no external publication or messages.
"""
import json,os,subprocess,sys
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:
 os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_INTEGRATED_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-27'))
ROOT.mkdir(parents=True,exist_ok=True);ROOT.chmod(0o700)
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':False,'status':'running'}))
def command(args,**kw):return subprocess.check_output(args,**kw)
names=['estrabao-lab-15-web','estrabao-lab-15-db','estrabao-lab-15-browser']
containers=json.loads(command(['docker','inspect',*names]))
checks={}
web=next(c for c in containers if c['Name']=='/estrabao-lab-15-web')
for c in containers:
 name=c['Name'].lstrip('/')
 nets=list(c['NetworkSettings']['Networks'])
 if not nets and c['HostConfig']['NetworkMode'] in ['container:'+web['Id'],'container:estrabao-lab-15-web']:
  nets=list(web['NetworkSettings']['Networks'])
 checks[name+'_running']=c['State']['Running']
 checks[name+'_internal_network_only']=bool(nets) and all(json.loads(command(['docker','network','inspect',n]))[0]['Internal'] for n in nets)
 checks[name+'_no_public_ports']=all(b.get('HostIp') in ['127.0.0.1','::1'] for bindings in (c['HostConfig'].get('PortBindings')or{}).values() for b in bindings or [])
web=next(c for c in containers if c['Name']=='/estrabao-lab-15-web')
checks['web_sendmail_override']= 'sendmail_path=/bin/true' in (web['Config'].get('Cmd')or[])
checks['mounts_match_checkout']=all(any(m['Destination']==dest and Path(m['Source']).resolve()==(HERE.parents[1]/relative).resolve() for m in web['Mounts']) for dest,relative in [('/opt/estrabao/src','src'),('/var/www/html/plugins/generic/estrabao','plugins/generic/estrabao')])
checks['readonly_engine_and_plugin']=all(any(m['Destination']==dest and not m['RW'] for m in web['Mounts']) for dest in ['/opt/estrabao/src','/var/www/html/plugins/generic/estrabao'])
if not all(checks.values()):raise RuntimeError('Synthetic lab isolation preflight failed: '+str([k for k,v in checks.items()if not v]))
command(['docker','cp',str(HERE/'preflight.php'),'estrabao-lab-15-web:/tmp/estrabao-preflight.php'])
preflight=json.loads(command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/estrabao-preflight.php']))
(ROOT/'preflight.json').write_text(json.dumps({'container_checks':checks,**preflight},indent=2))
for name in ['footnote-fixture.php','article-date-fixture.php','state-query.php']:
 command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
# Clear language cache only in this confirmed isolated instance.
command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
def state():return command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
before=state();suites=[]
# Each suite creates its own sessions. Fixtures restore bytes/records in finally.
plan=[('desk-panel.py','ESTRABAO_DESK_EVIDENCE_DIR'),('apa-mixed.py','ESTRABAO_APA_EVIDENCE_DIR'),
 ('footnote-panel.py','ESTRABAO_FOOTNOTE_EVIDENCE_DIR'),('article-dates.py','ESTRABAO_DATE_EVIDENCE_DIR'),
 ('credit-panel.py','ESTRABAO_CREDIT_EVIDENCE_DIR'),('funding-panel.py','ESTRABAO_FUNDING_EVIDENCE_DIR'),
 ('ethics-panel.py','ESTRABAO_ETHICS_EVIDENCE_DIR'),('ai-panel.py','ESTRABAO_AI_EVIDENCE_DIR'),
 ('title-guidance.py','ESTRABAO_TITLE_EVIDENCE_DIR'),('reference-guidance.py','ESTRABAO_REFERENCE_EVIDENCE_DIR')]
if '--with-apa-regression' in sys.argv:
 for label,args in [('authors',[]),('citations',['--citations']),('institutions',['--institutions']),('secondary',['--secondary']),('models',['--models']),('matching',['--matching']),('years',['--years']),('etal',['--etal']),('dates',['--dates']),('longworks',['--longworks']),('web',['--web']),('digital',['--digital']),('revisions',['--revisions']),('ordering',['--ordering']),('quotes',['--quotes']),('narratives',['--narratives'])]:
  plan.append(('apa-authors.py','ESTRABAO_APA_AUTHORS_EVIDENCE_DIR',label,args))
for suite in plan:
 script,var=suite[:2];label=suite[2] if len(suite)>2 else Path(script).stem;args=suite[3] if len(suite)>3 else []
 directory=ROOT/label;directory.mkdir(exist_ok=True)
 env=os.environ.copy();env[var]=str(directory);env['PYTHONPATH']=str(HERE)
 with (directory/'run.log').open('w')as log:
  process=subprocess.run([sys.executable,str(HERE/script),*args],env=env,stdout=log,stderr=subprocess.STDOUT)
 if process.returncode:raise RuntimeError('Suite failed: '+script+'; inspect its private run.log')
 data=json.loads((directory/'results.json').read_text())
 if not data or not all(r['passed']for r in data):raise RuntimeError('Missing or failed results: '+script)
 suites.append({'suite':script,'arguments':args,'checks':len(data),'passed':True})
 print('PASS '+label+' ('+str(len(data))+')',flush=True)
if before!=state():raise RuntimeError('Workflow records changed across integration suites')
summary={'overall_passed':'--with-submission-cycle' not in sys.argv,'requested_fresh_cycle':'--with-submission-cycle' in sys.argv,
 'base_commit':command(['git','rev-parse','HEAD'],cwd=HERE).decode().strip(),
 'runtime':preflight['runtime'],'suite_count':len(suites),'browser_checks':sum(s['checks']for s in suites),
 'suites':suites,'workflow_state_preserved':True,'fixture_mode':'synthetic_only',
 'production_accessed':False,'external_messages_sent':False,'apa_regression_requested':'--with-apa-regression' in sys.argv,
 'limitations':['default_theme_only','no_fresh_wizard_completion_in_this_runner','no_operational_production_homologation']}
(ROOT/'summary.json').write_text(json.dumps(summary,indent=2))
print('PASS integration total '+str(summary['browser_checks']),flush=True)

# Optional final cycle creates five NEW synthetic drafts; it never resets old submissions.
if '--with-submission-cycle' in sys.argv:
 import shutil
 cycle=ROOT/'submission-cycle';cycle.mkdir(exist_ok=True);cycle.chmod(0o700)
 web_state=command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json'])
 old_records=json.loads(before)
 try:
  for name in ['seed.php','replace.php']:
   command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
  command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/seed.php'])
  (cycle/'state.json').write_bytes(command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))
  credentials=Path(os.environ.get('ESTRABAO_LAB_DIR','/workspace/scratch/estrabao-lab-15'))/'credentials.json'
  shutil.copyfile(credentials,cycle/'credentials.json');(cycle/'credentials.json').chmod(0o600)
  env=os.environ.copy();env['ESTRABAO_LAB_DIR']=str(cycle);env['PYTHONPATH']=str(HERE)
  with (cycle/'run.log').open('w')as log:
   process=subprocess.run([sys.executable,str(HERE/'admission-cycle.py')],env=env,stdout=log,stderr=subprocess.STDOUT)
  if process.returncode:raise RuntimeError('Fresh wizard cycle failed; inspect its private run.log')
  data=json.loads((cycle/'cycle-results.json').read_text())
  if not data or not all(r['passed']for r in data):raise RuntimeError('Fresh wizard assertions failed')
  now={r['submission_id']:r for r in json.loads(state())}
  if not all(now[r['submission_id']]==r for r in old_records):raise RuntimeError('Original workflow records changed during fresh cycle')
  summary['overall_passed']=True
  summary['fresh_cycle_checks']=len(data);summary['fresh_cycle_passed']=all(r['passed']for r in data)
  summary['original_workflow_records_preserved']=True
  summary['limitations'].remove('no_fresh_wizard_completion_in_this_runner')
  summary['browser_checks']+=len(data)
  (ROOT/'summary.json').write_text(json.dumps(summary,indent=2))
  print('PASS fresh wizard cycle ('+str(len(data))+')',flush=True)
 finally:
  command(['docker','exec','-i','estrabao-lab-15-web','sh','-c','cat > /tmp/lab-state.json'],input=web_state)
  (cycle/'credentials.json').unlink(missing_ok=True)
