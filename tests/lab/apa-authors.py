"""Isolated synthetic OJS checks for author lists or citation forms (--citations, --institutions, --secondary, --models, --matching, --years or --etal, --dates, --longworks). Restores fixture bytes."""
import json,os,subprocess,sys
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:
 os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_APA_AUTHORS_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-39'if '--longworks'in sys.argv else '/workspace/scratch/estrabao-lab-37'if '--dates'in sys.argv else '/workspace/scratch/estrabao-lab-36'if '--etal'in sys.argv else '/workspace/scratch/estrabao-lab-35'if '--years'in sys.argv else '/workspace/scratch/estrabao-lab-34'if '--matching'in sys.argv else '/workspace/scratch/estrabao-lab-33'if '--models'in sys.argv else '/workspace/scratch/estrabao-lab-32'if '--secondary'in sys.argv else '/workspace/scratch/estrabao-lab-31'if '--institutions'in sys.argv else '/workspace/scratch/estrabao-lab-30'if '--citations'in sys.argv else '/workspace/scratch/estrabao-lab-29'))
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
for name in ['footnote-fixture.php','article-date-fixture.php','state-query.php']:
 command(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])

from browser import Browser
import time
results=[]
def fixture(mode):command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/footnote-fixture.php',mode])
def state():return command(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
statefile=json.loads(command(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))
id=statefile['submissions']['abnt']['id'];before=state();original_hash=command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/footnote-fixture.php','hash']);b=Browser();a=Browser()
try:
 b.login('admin');a.login('author')
 plan=[('citation-many','primeiro sobrenome seguido de et al.'),('citation-period','et al. com ponto após al')]if '--citations'in sys.argv else [('compound','sem ponto e vírgula'),('abbreviated','19 primeiros autores, reticências e o último, sem &')]
 if '--institutions'in sys.argv:plan=[('institution-abnt','posicionar a data após a autoria'),('institution-doi','https://doi.org/')]
 if '--secondary'in sys.argv:plan=[('secondary-apud','não utilizar apud'),('secondary-missing','conferir as chaves autor–ano')]
 if '--models'in sys.argv:plan=[('model-book','retirar o itálico da edição'),('model-chapter','separadores dos editores'),('model-article','página final não pode anteceder')]
 if '--matching'in sys.argv:plan=[('matching-compound-style','usar & entre dois autores'),('matching-compound-missing','conferir as chaves autor–ano')]
 if '--years'in sys.argv:plan=[('year-suffix','alinhar o ano e seu sufixo'),('year-suffix-late','alinhar o ano e seu sufixo'),('year-order','ordenar cronologicamente'),('work-order','ordenar alfabeticamente as obras')]
 if '--etal'in sys.argv:plan=[('etal-collision','desambiguar a citação abreviada'),('etal-prefix-collision','desambiguar a citação abreviada'),('etal-single-rest','pelo menos dois autores')]
 if '--dates'in sys.argv:plan=[('special-style','usar & entre dois autores'),('special-missing','conferir as chaves autor–ano'),('special-apud','não utilizar apud')]
 if '--longworks'in sys.argv:plan=[('longwork-title','aplicar itálico ao título no modelo declarado'),('longwork-description','retirar o itálico da descrição'),('longwork-report','retirar o itálico da descrição'),('longwork-source','retirar o itálico da instituição')]
 for mode,expected in plan:
  fixture(mode)
  b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId='+str(id))
  check(mode+' incompatible reference cannot be released','A submissão não foi aceita'in b.text()and b.js('return !document.querySelector("button[value=approve]")'))
  b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit();')
  for _ in range(40):
   if b.js('return !!document.querySelector("#estrabaoDeskBody")'):break
   time.sleep(.1)
  body=b.js('return document.querySelector("#estrabaoDeskBody").value')
  check(mode+' desk draft has specific correction',expected in body)
  check(mode+' draft keeps private bibliography out',all(s not in body for s in ['van der Meer','García Márquez','de Silva','Silva','Souza','Lima','Título sintético','Instituto de Estudos Sintéticos','10.1234/synthetic','Livro sintético','Capítulo sintético','Revista Sintética','B. Editor','Universidade Exemplo','Repositório Exemplo','Obra sintética']))
  check(mode+' does not label entire bibliography ABNT',('padrões ABNT reconhecidos'in body)==(mode=='institution-abnt'))
  b.js('document.querySelector("#estrabaoDeskDraftTitle").scrollIntoView()')
  import base64
  (ROOT/(mode+'.png')).write_bytes(base64.b64decode(b.call('GET','/screenshot')))
  a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId='+str(id))
  for _ in range(40):
   if a.js('return !!document.querySelector("#submitStep4Form")'):break
   time.sleep(.1)
  response=a.call('POST','/execute/async',{'script':"let done=arguments[arguments.length-1];let f=document.querySelector('#submitStep4Form');fetch(f.action,{method:'POST',body:new URLSearchParams(new FormData(f))}).then(async r=>done(await r.text()));",'args':[]})
  check(mode+' wizard rejects valid form and retains state',before==state()and 'submitStep4Form'in json.loads(response).get('content',''))
  # Native validation reports errors through notifications rather than the response content.
  a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId='+str(id))
  for _ in range(40):
   if a.js('return !!document.querySelector("#submitStep4Form")'):break
   time.sleep(.1)
  a.js('document.querySelector("[name=submitFormButton]").click()')
  for _ in range(40):
   if a.js('return Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).some(x=>x.textContent.trim()==="OK")'):break
   time.sleep(.1)
  a.js('Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.textContent.trim()==="OK").click()')
  author_expected={'compound':'ponto e vírgula','abbreviated':'19 primeiros','citation-many':'primeiro sobrenome seguido de et al.','citation-period':'et al. com ponto após al.','institution-abnt':'a data está após a fonte','institution-doi':'https://doi.org/','secondary-apud':'A Estrabão não aceita apud','secondary-missing':'fonte efetivamente consultada','model-book':'edição entre parênteses não fica em itálico','model-chapter':'Na editoria do capítulo','model-article':'página final não pode anteceder','matching-compound-style':'Usar & entre dois autores','matching-compound-missing':'sem referência correspondente','year-suffix':'Conferir o ano e seu sufixo','year-suffix-late':'Conferir o ano e seu sufixo','year-order':'ordem cronológica','work-order':'ordem alfabética de autoria','etal-collision':'A forma abreviada coincide','etal-prefix-collision':'A forma abreviada coincide','etal-single-rest':'pelo menos dois autores','special-style':'Usar & entre dois autores','special-missing':'sem referência correspondente','special-apud':'A Estrabão não aceita apud','longwork-title':'o título da obra fica em itálico','longwork-description':'não fica em itálico','longwork-report':'não fica em itálico','longwork-source':'não fica em itálico' }[mode]
  for _ in range(40):
   if author_expected in a.text():break
   time.sleep(.1)
  check(mode+' author receives concrete correction',author_expected in a.text()and before==state())
  (ROOT/(mode+'-author.png')).write_bytes(base64.b64decode(a.call('GET','/screenshot')))
  fixture('restore')
 if '--institutions'in sys.argv or '--secondary'in sys.argv or '--models'in sys.argv or '--matching'in sys.argv or '--years'in sys.argv or '--etal'in sys.argv or '--dates'in sys.argv or '--longworks'in sys.argv:
  positive_modes=['longwork-repository','longwork-unpublished','longwork-correct-report','longwork-partial']if '--longworks'in sys.argv else ['special-undated','special-press','special-divergent','special-suffix']if '--dates'in sys.argv else ['etal-expanded-correct','etal-full-correct','etal-initials-review','etal-duplicate-review']if '--etal'in sys.argv else ['year-correct','year-authorship','year-suffix-ambiguous','year-partial-suffix']if '--years'in sys.argv else ['matching-compound-correct','matching-compound-partial']if '--matching'in sys.argv else ['model-correct'if '--models'in sys.argv else 'secondary-correct'if '--secondary'in sys.argv else 'institution-alias']
  for positive_mode in positive_modes:
   fixture(positive_mode)
   b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId='+str(id))
   check(positive_mode+' remains reviewable',b.js('return !!document.querySelector("button[value=approve]")'))
   check(positive_mode+' has no editorial release','Há liberação editorial válida'not in b.text())
   a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId='+str(id))
   for _ in range(40):
    if a.js('return !!document.querySelector("#submitStep4Form")'):break
    time.sleep(.1)
   response=a.call('POST','/execute/async',{'script':"let done=arguments[arguments.length-1];let f=document.querySelector('#submitStep4Form');fetch(f.action,{method:'POST',body:new URLSearchParams(new FormData(f))}).then(async r=>done(await r.text()));",'args':[]})
   check(positive_mode+' cannot complete without human review',before==state()and 'submitStep4Form'in json.loads(response).get('content',''))
finally:
 fixture('restore');b.call('DELETE','');a.call('DELETE','')
check('original workflow state restored',before==state())
check('original manuscript bytes restored',original_hash==command(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/footnote-fixture.php','hash']))
(ROOT/'results.json').write_text(json.dumps(results,indent=2))
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(results),'engine':preflight['runtime']['engine'],'plugin':preflight['runtime']['installed_plugin'],'production_accessed':False,'fixture_restored':True},indent=2))
