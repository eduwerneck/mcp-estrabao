"""Synthetic private editorial checklist, isolated OJS only; restores metadata and ledger bytes."""
import os,json,subprocess,time,base64
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_EDITORIAL_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-55'));ROOT.mkdir(parents=True,exist_ok=True);ROOT.chmod(0o700)
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
for name in ['preflight.php','editorial-fixture.php','state-query.php']:command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
preflight=json.loads(command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/preflight.php']));(ROOT/'preflight.json').write_text(json.dumps({'container_checks':checks,**preflight},indent=2))
command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
def fixture(mode):return command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/editorial-fixture.php',mode])
def inspect():return json.loads(fixture('inspect'))
def state():return command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
from browser import Browser
results=[]
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
sid=json.loads(command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))['submissions']['abnt']['id'];before=state();original=inspect();fixture('backup');b=None;a=None
def generate(expect_form=True):
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId='+str(sid));b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit()')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoEditorialForm")' if expect_form else 'return !!document.querySelector("#estrabaoDeskBody")'):return
  time.sleep(.1)
 raise AssertionError('Editorial form missing')
def payload():return b.js('return Array.from(new FormData(document.querySelector("#estrabaoEditorialForm")).entries())')
def post(browser,entries,url=None):
 if url is None:url=b.js('return document.querySelector("#estrabaoEditorialForm").action')
 return browser.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];fetch(arguments[0],{method:"POST",body:new URLSearchParams(arguments[1])}).then(async r=>done({status:r.status,text:await r.text()}));','args':[url,entries]})
def save():
 b.js('let f=document.querySelector("#estrabaoEditorialForm");f.querySelector("[name=editorialReviewed]").checked=true;f.requestSubmit()')
 for _ in range(40):
  if 'Conferência editorial registrada.'in b.text():return
  time.sleep(.1)
 raise AssertionError('Editorial save did not confirm')
try:
 b=Browser();a=Browser();b.login('admin');a.login('author');generate()
 check('private form has twelve categories and translated labels',b.js('return document.querySelectorAll("#estrabaoEditorialForm select").length===12') and 'Registro da conferência editorial'in b.text() and '##plugins.generic.estrabao.editorial'not in b.text())
 check('automatic report does not prefill human review',b.js('return Array.from(document.querySelectorAll("#estrabaoEditorialForm select")).every(x=>x.value==="pending")'))
 check('not applicable restricted to conditional categories',b.js('return !document.querySelector("#estrabaoEditorial_metadata option[value=not_applicable]") && !!document.querySelector("#estrabaoEditorial_funding option[value=not_applicable]")'))
 check('checklist does not offer APA approval for ABNT',b.js('return !document.querySelector("button[value=approve]")'))
 b.js('document.querySelector("#estrabaoEditorial_metadata").value="reviewed";document.querySelector("#estrabaoEditorial_abstracts").value="adjustments";document.querySelector("#estrabaoEditorial_funding").value="not_applicable"')
 save();first=inspect()
 check('record persists selected human states',first['events'][-1]['states']['metadata']=='reviewed' and first['events'][-1]['states']['abstracts']=='adjustments' and first['events'][-1]['states']['funding']=='not_applicable')
 check('record includes operator time and analysis identity',first['events'][-1]['reviewer_id']>0 and len(first['events'][-1]['fingerprint'])==64 and first['events'][-1]['recorded_at'].endswith('Z') and first['private_mode'])
 check('APA ledger is untouched',first['apa_sha256']==original['apa_sha256'])
 check('ABNT remains blocked after checklist save',b.js('return !document.querySelector("button[value=approve]")') and 'A submissão não foi aceita'in b.text())
 generate();check('fresh generation recovers review for same data',b.js('return !!document.querySelector("#estrabaoEditorialCurrent") && document.querySelector("#estrabaoEditorial_metadata").value==="reviewed"'))
 url=b.js('return document.querySelector("#estrabaoEditorialForm").action');good=payload()+[['editorialReviewed','1']]
 for label,entries in [('missing declaration',[x for x in good if x[0]!='editorialReviewed']),('invalid CSRF',[['csrfToken','invalid'] if x[0]=='csrfToken' else x for x in good]),('invalid state',[['editorialStates[metadata]','approve']if x[0]=='editorialStates[metadata]'else x for x in good]),('forged fingerprint',[['editorialFingerprint','0'*64]if x[0]=='editorialFingerprint'else x for x in good])]:
  r=post(b,entries);check(label+' refused without ledger write','Conferência não salva'in r['text'] and inspect()['ledger_sha256']==first['ledger_sha256'])
 r=post(a,good,url);check('author cannot save private checklist','Conferência editorial registrada.'not in r['text'] and inspect()['ledger_sha256']==first['ledger_sha256'])
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId='+str(sid));check('author cannot view private checklist','estrabaoEditorialForm'not in a.js('return document.documentElement.outerHTML'))
 r=post(b,good);second=inspect();check('next record appends rather than overwriting',len(second['events'])==len(first['events'])+1 and second['events'][:-1]==first['events'])
 r=post(b,good);check('stale tab cannot replay record','Conferência não salva'in r['text'] and inspect()['ledger_sha256']==second['ledger_sha256'])
 generate();stale=payload()+[['editorialReviewed','1']];fixture('change');r=post(b,stale);check('metadata change rejects stale confirmation','Conferência não salva'in r['text'] and inspect()['ledger_sha256']==second['ledger_sha256'])
 generate();check('changed analysis starts pending with retained history',b.js('return !!document.querySelector("#estrabaoEditorialStale") && Array.from(document.querySelectorAll("#estrabaoEditorialForm select")).every(x=>x.value==="pending")'))
 save();third=inspect();check('new analysis records a distinct binding',third['events'][-1]['fingerprint']!=second['events'][-1]['fingerprint'])
 fixture('reset-metadata');generate();check('earlier matching event does not replace latest different analysis',b.js('return !!document.querySelector("#estrabaoEditorialStale") && document.querySelector("#estrabaoEditorial_metadata").value==="pending"'))
 b.js('document.querySelector("#estrabaoEditorialHistory").open=true;document.querySelector("#estrabaoEditorialReview").scrollIntoView()');(ROOT/'editorial-history.png').write_bytes(base64.b64decode(b.call('GET','/screenshot')))
 check('history shows states without manuscript identity values',b.js('return document.querySelector("#estrabaoEditorialHistory").textContent.includes("Conferido pelo editor") && !/Metadato sintético|orcid.org|sha256/.test(document.querySelector("#estrabaoEditorialHistory").textContent)'))
 fixture('corrupt');generate(False);check('broken checklist storage preserves draft and APA view',b.js('return !!document.querySelector("#estrabaoDeskBody") && !document.querySelector("#estrabaoEditorialForm")') and 'registro de conferência está indisponível'in b.text())
 check('recording did not change workflow state',before==state())
finally:
 fixture('restore')
 if b is not None:b.call('DELETE','')
 if a is not None:a.call('DELETE','')
restored=inspect();check('original metadata and ledger bytes restored',original==restored);check('original workflow records restored',before==state())
(ROOT/'results.json').write_text(json.dumps(results,indent=2));(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(results),'engine':preflight['runtime']['engine'],'plugin':preflight['runtime']['installed_plugin'],'fixture_restored':True,'production_accessed':False,'external_messages_sent':False},indent=2))
