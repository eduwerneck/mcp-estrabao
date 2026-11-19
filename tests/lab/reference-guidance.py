"""Advisory references UI in the isolated lab. Client edits are discarded; citation visibility restored."""
import json,os,subprocess,sys
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:
 os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_REFERENCE_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-43'))
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
if '--secondary'in sys.argv:command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
(ROOT/'preflight.json').write_text(json.dumps({'container_checks':checks,**preflight},indent=2))

command(['docker','cp',str(HERE/'state-query.php'),'estrabao-lab-15-web:/tmp/state-query.php'])
command(['docker','cp',str(HERE/'reference-metadata-fixture.php'),'estrabao-lab-15-web:/tmp/reference-metadata-fixture.php'])
command(['docker','exec','estrabao-lab-15-web','sh','-c','find cache -maxdepth 1 -type f -name "fc-locale*" -delete'])
from browser import Browser
import time,base64
results=[]
def setting(mode):return command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/reference-metadata-fixture.php',mode])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
def workflow():return command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
original=setting('state');before=workflow();a=None;b=None;enabled=False
selector='textarea[name="citationsRaw"],textarea#citationsRaw,textarea#citations-citationsRaw-control'
def wait_field(browser):
 for _ in range(50):
  if browser.js('return !!document.querySelector(arguments[0]) && !!document.querySelector(".estrabaoReferencesHelp")',selector):return
  time.sleep(.1)
 raise RuntimeError('References field or help missing')
def client_value(browser,value):
 browser.js('let f=document.querySelector(arguments[0]);f.value=arguments[1];f.dispatchEvent(new Event("input",{bubbles:true}));f.dispatchEvent(new Event("change",{bubbles:true}));f.blur();',selector,value)
 time.sleep(.25)
def status(browser):return browser.js('return document.querySelector(".estrabaoReferencesStatus").dataset.state')
try:
 setting('enable');enabled=True;exposed=setting('state');a=Browser();b=Browser();a.login('author');b.login('admin')
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId=1');a.js('document.querySelector("a[name=step-3]").click()');wait_field(a)
 saved=a.js('return document.querySelector(arguments[0]).value',selector)
 check('author sees translated one-reference-per-Enter guidance','usando Enter entre uma referência e outra'in a.text())
 check('references guidance is linked accessibly',a.js('let f=document.querySelector(arguments[0]);return (f.getAttribute("aria-describedby")||"").split(" ").filter(id=>document.getElementById(id)?.classList.contains("estrabaoReferencesHelp")||document.getElementById(id)?.classList.contains("estrabaoReferencesStatus")).length===2',selector))
 ref1='Silva, A. (2020). Obra sintética. Editora Exemplo.';ref2='Souza, B. (2021). Outra obra. Editora Exemplo.'
 joined=ref1+' '+ref2;client_value(a,joined)
 check('author gets advisory for concatenated references',status(a)=='warning' and 'Há indícios'in a.text())
 check('advisory does not rewrite or split author text',a.js('return document.querySelector(arguments[0]).value',selector)==joined)
 check('warning does not echo bibliography',a.js('return !document.querySelector(".estrabaoReferencesStatus").textContent.includes("Silva")'))
 a.js('document.querySelector(".estrabaoReferencesStatus").scrollIntoView()');(ROOT/'author-joined.png').write_bytes(base64.b64decode(a.call('GET','/screenshot')))
 for name,value in [('one Enter',ref1+'\n'+ref2),('blank line',ref1+'\n\n'+ref2),('wrapped long reference',ref1+' Texto longo.'*30)]:
  client_value(a,value);check(name+' does not trigger concatenation warning',status(a)=='no_signal')
 client_value(a,ref1+'\nhttps://synthetic.invalid/item');check('unknown continuation requests human review',status(a)=='review')
 client_value(a,ref1+' '+ref2)
 check('DOM updates do not duplicate help',a.js('return document.querySelectorAll(".estrabaoReferencesHelp").length===1'))
 check('client edits do not autosave metadata or citations',setting('state')==exposed)
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId=1');a.js('document.querySelector("a[name=step-3]").click()');wait_field(a)
 check('reload discards client edit',a.js('return document.querySelector(arguments[0]).value',selector)==saved)
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/workflow/index/2/1');b.js('document.querySelector("#publication-button").click()')
 for _ in range(50):
  if b.js('return Array.from(document.querySelectorAll("button,a")).some(x=>x.textContent.trim()==="Referências")'):break
  time.sleep(.1)
 b.js('Array.from(document.querySelectorAll("button,a")).find(x=>x.textContent.trim()==="Referências").click()');wait_field(b)
 check('publication metadata form shows reference guidance','usando Enter entre uma referência e outra'in b.text())
 editor_saved=b.js('return document.querySelector(arguments[0]).value',selector);client_value(b,joined)
 check('editor receives concatenation advisory',status(b)=='warning')
 check('editor text is preserved',b.js('return document.querySelector(arguments[0]).value',selector)==joined)
 b.js('document.querySelector(".estrabaoReferencesStatus").scrollIntoView()');(ROOT/'editor-joined.png').write_bytes(base64.b64decode(b.call('GET','/screenshot')))
 check('editor changes are not automatically persisted',setting('state')==exposed)
 check('advisory keeps submission workflow state',before==workflow())
finally:
 if a:a.call('DELETE','')
 if b:b.call('DELETE','')
 if enabled:setting('restore')
check('original citation settings metadata and parsed citations restored',setting('state')==original)
check('original workflow state preserved',before==workflow())
(ROOT/'results.json').write_text(json.dumps(results,indent=2))
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(results),'runtime':preflight['runtime'],'fixture_restored':True,'production_accessed':False,'metadata_autosaved':False,'advisory_only':True},indent=2))
