"""Human DOC route in isolated OJS; fresh synthetic drafts only."""
import os,json,subprocess,time,base64
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_DOC_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-56'));ROOT.mkdir(parents=True,exist_ok=True);ROOT.chmod(0o700)
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
for name in ['preflight.php','doc-fixture.php','state-query.php','seed.php']:command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
preflight=json.loads(command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/preflight.php']));(ROOT/'preflight.json').write_text(json.dumps({'container_checks':checks,**preflight},indent=2))
command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
from browser import Browser
results=[];b=None;a=None

def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
def state():return json.loads(command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))
def fixture(mode,kind='review'):return command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/doc-fixture.php',mode,kind])
def inspect(kind='review'):return json.loads(fixture('inspect',kind))
def view(kind='review'):
 i=ids[kind];b.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}');return i

def payload():return b.js('return Array.from(new FormData(document.querySelector("#estrabaoApaReviewForm")).entries())')
def post(entries,browser=None):
 return (browser or b).call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];fetch(arguments[0],{method:"POST",body:new URLSearchParams(arguments[1])}).then(async r=>done({status:r.status,text:await r.text()}));','args':[saveurl,entries]})
def finish(i,valid=True):
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 return a.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];const f=document.querySelector("#submitStep4Form");const p=new URLSearchParams(new FormData(f));if(!arguments[0])p.set("csrfToken","invalid-synthetic");fetch(f.action,{method:"POST",body:p}).then(async r=>done(await r.text()));','args':[valid]})
def row(i):return next(r for r in state()if int(r['submission_id'])==i)
def approve():
 b.js('let f=document.querySelector("#estrabaoApaReviewForm");f.querySelector("[name=apaReviewed]").checked=true;f.querySelector("[name=docOpened]").checked=true;f.querySelector("[value=approve]").click()')
 for _ in range(40):
  if 'Decisão editorial registrada.'in b.text():return
  time.sleep(.1)
 raise AssertionError('DOC review not recorded')
original_state=command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']);original_records=state()
try:
 command(['docker','cp',str(HERE.parent/'fixtures/legacy/synthetic.doc'),'estrabao-lab-15-web:/tmp/estrabao-synthetic.doc'])
 command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/seed.php'])
 fresh=json.loads(command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']));ids={k:int(v['id'])for k,v in fresh['submissions'].items()}
 command(['docker','exec','-i','estrabao-lab-15-web','sh','-c','cat > /tmp/estrabao-doc-fresh-ids.json'],input=json.dumps(list(ids.values())).encode())
 for kind in ['review','revoke','replace','csrf']:fixture('install',kind)
 b=Browser();a=Browser();b.login('admin');a.login('author');i=view();initial=inspect()
 check('recognized DOC has human route and explicit declarations',b.js('return !!document.querySelector("#estrabaoDocReview") && !!document.querySelector("[name=docOpened]") && !!document.querySelector("[name=apaReviewed]")'))
 check('DOC scope explains no extraction no conversion and ABNT veto','não teve o texto extraído'in b.text() and 'Não é necessário converter'in b.text() and 'ABNT, apud'in b.text())
 check('DOC starts without release','Há liberação editorial válida'not in b.text())
 finish(i);check('DOC cannot complete without human review',row(i)['submission_progress']==4 and row(i)['date_submitted']is None)
 # The native POST response is checked directly; errors are not hidden by fetch.
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 a.js('document.querySelector("[name=submitFormButton]").click()')
 for _ in range(40):
  if a.js('return Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).some(x=>x.textContent.trim()==="OK")'):break
  time.sleep(.1)
 a.js('Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.textContent.trim()==="OK").click()')
 for _ in range(40):
  if 'A revista aceita arquivos .doc'in a.text():break
  time.sleep(.1)
 (ROOT/'author-pending.png').write_bytes(base64.b64decode(a.call('GET','/screenshot')))
 check('author sees accepted DOC and human review guidance','A revista aceita arquivos .doc'in a.text()and 'conferência humana'in a.text())
 view();saveurl=b.js('return document.querySelector("#estrabaoApaReviewForm").action');base=payload()
 for label,entries in [('opening declaration missing',base+[['apaReviewed','1'],['reviewAction','approve']]),('APA declaration missing',base+[['docOpened','1'],['reviewAction','approve']]),('invalid CSRF',[[k,('invalid'if k=='csrfToken'else v)]for k,v in base]+[['apaReviewed','1'],['docOpened','1'],['reviewAction','approve']])]:
  r=post(entries);check(label+' cannot write review','A decisão não foi registrada.'in r['text'] and inspect()['ledger_sha256']==initial['ledger_sha256'])
 good=base+[['apaReviewed','1'],['docOpened','1'],['reviewAction','approve']]
 r=post(good,a);check('author cannot save DOC review','Decisão editorial registrada.'not in r['text'] and inspect()['ledger_sha256']==initial['ledger_sha256'])
 download=b.js('return Array.from(document.querySelectorAll("a")).find(x=>x.textContent.includes("Baixar a versão atual")).href')
 def fetchfile(browser):return browser.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];fetch(arguments[0]).then(async r=>done({status:r.status,disposition:r.headers.get("content-disposition"),bytes:Array.from(new Uint8Array(await r.arrayBuffer())).slice(0,8)}));','args':[download]})
 r=fetchfile(b);check('editor downloads DOC with correct filename and binary signature',r['status']==200 and r['bytes']==[208,207,17,224,161,177,26,225] and 'manuscrito.doc'in (r['disposition']or''))
 check('author cannot download via private DOC route',fetchfile(a)['bytes']!=[208,207,17,224,161,177,26,225])
 view();approve();saved=inspect();check('DOC release records human mode privately',saved['modes']==['doc_manual_v1']and saved['private_mode'])
 check('human DOC release is shown with route in history','Há liberação editorial válida'in b.text()and 'Conferência humana de .doc'in b.text())
 r=post(good);check('old form replay is refused',inspect()['ledger_sha256']==saved['ledger_sha256'] and 'A decisão não foi registrada.'in r['text'])
 check('review preserves DOC source bytes',saved['sha256']==initial['sha256'])
 b.js('document.querySelector("#estrabaoDocReview").scrollIntoView()');(ROOT/'doc-review.png').write_bytes(base64.b64decode(b.call('GET','/screenshot')))
 finish(i,False);check('DOC release cannot bypass native CSRF',row(i)['submission_progress']==4)
 finish(i);check('explicitly reviewed DOC completes native wizard',row(i)['submission_progress']==0 and row(i)['date_submitted']is not None)
 i=view('revoke');approve();b.js('document.querySelector("button[value=revoke]").click()')
 for _ in range(40):
  if 'Decisão editorial registrada.'in b.text() and 'Há liberação editorial válida'not in b.text():break
  time.sleep(.1)
 finish(i);check('revoked DOC cannot complete',row(i)['submission_progress']==4)
 i=view('replace');approve();fixture('changed','replace');view('replace');check('new DOC version invalidates previous release','Há liberação editorial válida'not in b.text());finish(i);check('changed DOC still needs a new human review',row(i)['submission_progress']==4)
 i=view('csrf');fixture('encrypted','csrf');view('csrf');check('encrypted DOC cannot receive a release',b.js('return !!document.querySelector("#estrabaoDocReview") && !document.querySelector("button[value=approve]")') and 'Não foi possível identificar'in b.text());finish(i);check('protected DOC stays pending rather than accepted',row(i)['submission_progress']==4)
 i=view('abnt');check('DOC route does not weaken existing DOCX ABNT block',b.js('return !document.querySelector("button[value=approve]")')and 'Referências em ABNT não são aceitas'in b.text())
finally:
 command(['docker','exec','-i','estrabao-lab-15-web','sh','-c','cat > /tmp/lab-state.json'],input=original_state)
 command(['docker','exec','estrabao-lab-15-web','rm','-f','/tmp/estrabao-doc-fresh-ids.json'])
 if b:b.call('DELETE','')
 if a:a.call('DELETE','')
now={r['submission_id']:r for r in state()};check('all original workflow records preserved',all(now[r['submission_id']]==r for r in original_records))
check('original lab pointer restored',original_state==command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))
(ROOT/'results.json').write_text(json.dumps(results,indent=2));(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(results),'engine':preflight['runtime']['engine'],'plugin':preflight['runtime']['plugin_files'],'original_records_preserved':True,'fixture_mode':'fresh_synthetic_only','production_accessed':False,'external_messages_sent':False,'human_opening_semantics_not_certified':True},indent=2))
