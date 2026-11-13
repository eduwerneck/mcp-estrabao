# Read-only synthetic lab smoke test. Does not enable fields or edit metadata.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_FUNDING_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-24'));root.mkdir(parents=True,exist_ok=True);results=[]
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
 check('funding comparison has translated internal guidance',b.js('let p=document.querySelector("#estrabaoFundingReview");return !!p && p.textContent.includes("comparação de fomento está inconclusiva") && !p.textContent.includes("##")'))
 check('disabled or editorial-only fields are not called omissions',b.js('return document.querySelector("#estrabaoFundingReview").textContent.includes("não comprovam omissão")'))
 check('internal funding guidance is excluded from letter',b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("comparação de fomento está inconclusiva")'))
 check('reading and generating do not change editorial state',before==state())
 b.js('document.querySelector("details").scrollIntoView()');b.screenshot('funding-comparison-panel')
finally:b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
