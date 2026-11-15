# Read-only smoke suite for the isolated synthetic OJS lab.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_ETHICS_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-25'));root.mkdir(parents=True,exist_ok=True);results=[]
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
 check('translated internal ethics checklist',b.js('let p=document.querySelector("#estrabaoEthicsReview");return !!p && p.textContent.includes("Confirme a aplicabilidade") && !p.textContent.includes("##")'))
 check('missing markers are not proof of absence or exemption',b.js('return document.querySelector("#estrabaoEthicsReview").textContent.includes("não comprova ausência de declaração, nem dispensa ética")'))
 check('checklist stays outside author letter',b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("Confirme a aplicabilidade") && !document.querySelector("#estrabaoDeskBody").value.includes("número do parecer")'))
 check('checklist does not change editorial state',before==state())
 b.js('document.querySelector("#estrabaoEthicsReview").scrollIntoView()');b.screenshot('ethics-review-panel')
finally:b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
