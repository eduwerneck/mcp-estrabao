# Synthetic lab only; client-side edits are discarded without saving.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_TITLE_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-27/title-guidance'));root.mkdir(parents=True,exist_ok=True);results=[]
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
def state():return subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
a=Browser();b=Browser();a.login('author');b.login('admin');before=state()
def wizard():
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId=1');a.js('document.querySelector("a[name=step-3]").click()')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep3Form input[name^=title]")'):return
  time.sleep(.1)
 raise RuntimeError('metadata step missing')
try:
 wizard();original=a.js('return document.querySelector("#submitStep3Form input[name^=title]").value')
 check('legacy title and subtitle show translated guidance',a.js('return document.querySelectorAll("#submitStep3Form .estrabaoTitleGuidance").length===2 && document.querySelector(".estrabaoTitleGuidance").textContent.includes("Preserve siglas")'))
 check('guidance is linked accessibly to both inputs',a.js('return Array.from(document.querySelectorAll("#submitStep3Form input[name^=title],#submitStep3Form input[name^=subtitle]")).every(x=>(x.getAttribute("aria-describedby")||"").split(" ").some(id=>document.getElementById(id)?.classList.contains("estrabaoTitleGuidance")))'))
 a.js('let x=document.querySelector("#submitStep3Form input[name^=title]");x.value="DNA e RNA: estudo sintético";x.dispatchEvent(new Event("input",{bubbles:true}));x.blur()')
 check('typed acronym text is not rewritten',a.js('return document.querySelector("#submitStep3Form input[name^=title]").value==="DNA e RNA: estudo sintético"'))
 wizard();check('discarded client edit was not automatically saved',a.js('return document.querySelector("#submitStep3Form input[name^=title]").value')==original)
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/workflow/index/2/1');b.js('document.querySelector("#publication-button").click()')
 for _ in range(40):
  if b.js('return !!document.querySelector("#titleAbstract-title-control-pt_BR")'):break
  time.sleep(.1)
 check('publication form has title and subtitle guidance',b.text().count('Não há conversão automática do texto.')==2)
 check('title inspection does not change workflow state',before==state())
finally:a.call('DELETE','');b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
