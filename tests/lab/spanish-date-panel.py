"""Spanish special-date markers in isolated OJS; fresh synthetic drafts only."""
import os,json,subprocess,time,base64
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_SPANISH_DATE_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-59'));ROOT.mkdir(parents=True,exist_ok=True);ROOT.chmod(0o700)
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':False,'status':'running'}))
def command(args,**kw):return subprocess.check_output(args,**kw)
names=['estrabao-lab-15-web','estrabao-lab-15-db','estrabao-lab-15-browser'];containers=json.loads(command(['docker','inspect',*names]));checks={};web=next(c for c in containers if c['Name']=='/estrabao-lab-15-web')
for c in containers:
 name=c['Name'].lstrip('/');nets=list(c['NetworkSettings']['Networks'])
 if not nets and c['HostConfig']['NetworkMode'] in ['container:'+web['Id'],'container:estrabao-lab-15-web']:nets=list(web['NetworkSettings']['Networks'])
 checks[name+'_running']=c['State']['Running'];checks[name+'_internal_network_only']=bool(nets) and all(json.loads(command(['docker','network','inspect',n]))[0]['Internal']for n in nets)
 checks[name+'_no_public_ports']=all(b.get('HostIp') in ['127.0.0.1','::1']for bindings in (c['HostConfig'].get('PortBindings')or{}).values()for b in bindings or [])
checks['sendmail_neutralized']='sendmail_path=/bin/true'in (web['Config'].get('Cmd')or[])
checks['readonly_sources_match_checkout']=all(any(m['Destination']==dest and not m['RW'] and Path(m['Source']).resolve()==(HERE.parents[1]/rel).resolve()for m in web['Mounts'])for dest,rel in [('/opt/estrabao/src','src'),('/var/www/html/plugins/generic/estrabao','plugins/generic/estrabao')])
if not all(checks.values()):raise RuntimeError('Isolated lab preflight failed')
for name in ['preflight.php','spanish-date-fixture.php','state-query.php','seed.php']:command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
preflight=json.loads(command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/preflight.php']));(ROOT/'preflight.json').write_text(json.dumps({'container_checks':checks,**preflight},indent=2))
command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
from browser import Browser
results=[];b=None;a=None

def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
def state():return json.loads(command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))
def fixture(language,mode):return command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/spanish-date-fixture.php',language,mode,marker])
def inspect(language):return json.loads(fixture(language,'inspect'))
def view(language):
 i=ids[{'pt':'review','en':'revoke','es':'replace'}[language]];b.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}');return i

def row(i):return next(r for r in state()if int(r['submission_id'])==i)
def finish(i,valid=True):
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 return a.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];const f=document.querySelector("#submitStep4Form");const p=new URLSearchParams(new FormData(f));if(!arguments[0])p.set("csrfToken","invalid-synthetic");fetch(f.action,{method:"POST",body:p}).then(async r=>done(await r.text()));','args':[valid]})
def draft():
 b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit()')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoDeskBody")'):return
  time.sleep(.1)
 raise AssertionError('Missing multilingual draft')
def approve():
 b.js('let f=document.querySelector("#estrabaoApaReviewForm");f.querySelector("[name=apaReviewed]").checked=true;f.querySelector("[value=approve]").click()')
 for _ in range(40):
  if 'Decisão editorial registrada.'in b.text():return
  time.sleep(.1)
 raise AssertionError('Review not recorded')
original_state=command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']);original_records=state()
try:
 b=Browser();a=Browser();b.login('admin');a.login('author')
 for marker in ['s.f.','en prensa']:
  command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/seed.php'])
  fresh=json.loads(command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']));ids={k:int(v['id'])for k,v in fresh['submissions'].items()}
  command(['docker','exec','-i','estrabao-lab-15-web','sh','-c','cat > /tmp/estrabao-spanish-date-fresh-ids.json'],input=json.dumps(list(ids.values())).encode())
  for language in ['pt','en','es']:
   label=language+' '+marker
   fixture(language,'bad');i=view(language);bad=inspect(language)
   check(label+' declared marker is parsed in the server locale',bad['source_available']and bad['segmentation_complete']and bad['matched']==1 and bad['special_date_citations']==1 and bad['language']==language)
   check(label+' narrative ampersand remains blocked',bad['decision']=='not_accepted'and b.js('return !document.querySelector("button[value=approve]")'))
   check(label+' private panel warns against inferred publication or year',b.js('return !!document.querySelector("#estrabaoSpecialDateReview")')and 'não comprovam publicação'in b.text()and 'não geram um ano'in b.text())
   finish(i);check(label+' blocked source cannot complete',row(i)['submission_progress']==4 and row(i)['date_submitted']is None)
   view(language);draft();check(label+' draft contains the proven citation correction','conjunção textual'in b.js('return document.querySelector("#estrabaoDeskBody").value'))
   fixture(language,'divergent');view(language);divergent=inspect(language)
   check(label+' divergence stays ambiguous with private guidance',divergent['matching_status']=='indeterminado'and divergent['decision']=='manual_review_required'and 'Há liberação editorial válida'not in b.text()and b.js('return !!document.querySelector("#estrabaoSpecialDateReview")'))
   fixture(language,'label');view(language);generic=inspect(language)
   check(label+' date label is not presumed to be an absent author',generic['matching_status']=='indeterminado'and generic['decision']=='manual_review_required'and b.js('return !!document.querySelector("#estrabaoSpecialDateReview")')and 'Há liberação editorial válida'not in b.text())
   fixture(language,'equivalent');view(language);equivalent=inspect(language)
   check(label+' lexical equivalent links without automatic release',equivalent['matching_status']=='conforme'and equivalent['decision']=='manual_review_required'and equivalent['matched']==1 and 'Há liberação editorial válida'not in b.text())
   fixture(language,'apud');view(language);apud=inspect(language)
   check(label+' apud remains blocked',apud['decision']=='not_accepted'and b.js('return !document.querySelector("button[value=approve]")'))
   fixture(language,'good');view(language);good=inspect(language)
   check(label+' corrected marker stays pending explicit review',good['matching_status']=='conforme'and good['decision']=='manual_review_required'and good['matched']==1 and 'Há liberação editorial válida'not in b.text())
   filename=language+'-'+('sf'if marker=='s.f.'else'press')+'-review.png';b.js('document.querySelector("#estrabaoSpecialDateReview").scrollIntoView()');(ROOT/filename).write_bytes(base64.b64decode(b.call('GET','/screenshot')))
   finish(i);check(label+' matching marker cannot finish without review',row(i)['submission_progress']==4 and row(i)['date_submitted']is None)
   view(language);approve();check(label+' human review activates the current release','Há liberação editorial válida'in b.text())
   fixture(language,'good');view(language);check(label+' new file ID invalidates review with unchanged bytes',good['sha256']==inspect(language)['sha256']and 'Há liberação editorial válida'not in b.text())
   finish(i);check(label+' stale release cannot complete',row(i)['submission_progress']==4)
   view(language);approve();finish(i,False);check(label+' release preserves native CSRF',row(i)['submission_progress']==4)
   finish(i);check(label+' explicitly reviewed current source completes',row(i)['submission_progress']==0 and row(i)['date_submitted']is not None)
finally:
 command(['docker','exec','-i','estrabao-lab-15-web','sh','-c','cat > /tmp/lab-state.json'],input=original_state)
 command(['docker','exec','estrabao-lab-15-web','rm','-f','/tmp/estrabao-spanish-date-fresh-ids.json'])
 if b:b.call('DELETE','')
 if a:a.call('DELETE','')
now={r['submission_id']:r for r in state()};check('original workflow records preserved',all(now[r['submission_id']]==r for r in original_records))
check('original lab pointer restored',original_state==command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))
(ROOT/'results.json').write_text(json.dumps(results,indent=2));(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(results),'engine':preflight['runtime']['engine'],'plugin':preflight['runtime']['plugin_files'],'fixture_mode':'fresh_synthetic_only','original_records_preserved':True,'production_accessed':False,'external_messages_sent':False,'full_apa_validation':False},indent=2))
