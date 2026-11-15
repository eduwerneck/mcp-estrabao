# Read-only smoke test for the isolated synthetic OJS lab.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_AI_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-26'));root.mkdir(parents=True,exist_ok=True);results=[]
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
 check('AI checklist translated and requires use or non-use in manuscript',b.js('let p=document.querySelector("#estrabaoAiReview");return !!p && p.textContent.includes("declarar no manuscrito uso ou não uso") && !p.textContent.includes("##")'))
 check('does not require OJS duplication',b.js('return document.querySelector("#estrabaoAiReview").textContent.includes("Não é necessário repetir em Comentários ao Editor")'))
 check('absence of markers is not automatic accusation',b.js('return document.querySelector("#estrabaoAiReview").textContent.includes("ausência de marcador não comprova ausência") && !document.querySelector("#estrabaoDeskBody").value.includes("Declaração de IA:")'))
 check('guidance remains outside letter and does not change state',before==state() and b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("Esta ficha não detecta uso real")'))
 b.js('document.querySelector("#estrabaoAiReview").scrollIntoView()');b.screenshot('ai-review-panel')
finally:b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
