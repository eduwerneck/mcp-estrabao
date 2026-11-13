# Read-only smoke test against the existing synthetic OJS lab.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_CREDIT_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-23'));root.mkdir(parents=True,exist_ok=True);results=[]
def state():return subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True);assert ok,name;results.append({'case':name,'passed':bool(ok)})
b=Browser();b.login('admin');before=state()
try:
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=1')
 b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit();')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoDeskBody")'):break
  time.sleep(.1)
 b.js('document.querySelector("details").open=true;')
 check('CRediT manual verification has a translated internal group',b.js('return document.querySelector("details").textContent.includes("CRediT: conferir taxonomia, atribuição a todos os autores e veracidade")'))
 check('missing heading is not accused of containing an empty declaration',b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("não texto de contribuições")'))
 check('manual verification stays outside author letter',b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("CRediT: conferir taxonomia")'))
 check('no editorial state change',before==state())
 b.js('document.querySelector("details").scrollIntoView()');b.screenshot('credit-review-panel')
finally:b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
