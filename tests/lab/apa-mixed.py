# Optional isolated OJS lab. Synthetic data only; restores file bytes in finally.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_APA_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-21'));root.mkdir(parents=True,exist_ok=True);results=[]
def fixture(mode):subprocess.run(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/footnote-fixture.php',mode],check=True)
def state():return subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True);assert ok,name;results.append({'case':name,'passed':bool(ok)})
b=Browser();b.login('admin');a=Browser();a.login('author');before=state()
try:
 fixture('mixed')
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=1')
 check('mixed APA head and legacy fields cannot be released',b.js('return !document.querySelector("button[value=approve]")') and 'A submissão não foi aceita'in b.text())
 b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit();')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoDeskBody")'):break
  time.sleep(.1)
 body=b.js('return document.querySelector("#estrabaoDeskBody").value')
 check('draft maps new diagnostics to corrections','sem ponto e vírgula'in body and 'rótulos v., n. e p./pp.'in body and 'https://doi.org/'in body)
 check('mixed pattern is not called an entirely ABNT bibliography','padrões ABNT reconhecidos'not in body)
 check('draft excludes reference content and DOI','Silva'not in body and '10.1234/synthetic'not in body)
 b.js('document.querySelector("#estrabaoDeskDraftTitle").scrollIntoView()');b.screenshot('apa-mixed-panel')
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId=1')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 response=a.call('POST','/execute/async',{'script':'''let done=arguments[arguments.length-1];let f=document.querySelector('#submitStep4Form');fetch(f.action,{method:'POST',body:new URLSearchParams(new FormData(f))}).then(async r=>done(await r.text()));''','args':[]})
 check('wizard blocks mixed reference with valid native form',before==state() and 'status' in response and 'false' in response)
finally:
 fixture('restore');b.call('DELETE','');a.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
