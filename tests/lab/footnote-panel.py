# Optional OJS 3.3.0.20 synthetic lab. Never run against a journal deployment.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_FOOTNOTE_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-20'));root.mkdir(parents=True,exist_ok=True)
results=[]
def fixture(mode):subprocess.run(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/footnote-fixture.php',mode],check=True)
def state():return subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php'])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True);assert ok,name;results.append({'case':name,'passed':bool(ok)})
b=Browser();b.login('admin');before=state()
def generate():
 b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=1')
 b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit();')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoDeskBody")'):break
  time.sleep(.1)
 b.js('document.querySelector("details").open=true;')
try:
 fixture('3');generate()
 check('three linked notes have three internal call positions',b.js('return document.querySelectorAll("#estrabaoFootnoteReview li").length===3'))
 check('instructions translated and show two visual lines',b.js('return document.querySelector("#estrabaoFootnoteReview").textContent.includes("duas linhas visuais") && !document.querySelector("#estrabaoFootnoteReview").textContent.includes("##")'))
 check('current manuscript download offered',b.js('return !!document.querySelector("#estrabaoFootnoteReview a[href*=download]")'))
 check('unknown visual lines not accused in letter',b.js('return !document.querySelector("#estrabaoDeskBody").value.includes("duas linhas")'))
 b.call('POST','/goog/cdp/execute',{'cmd':'Browser.grantPermissions','params':{'origin':'http://127.0.0.1:18080','permissions':['clipboardReadWrite','clipboardSanitizedWrite']}})
 b.js('document.querySelector("#estrabaoCopyDesk").click()');time.sleep(.2)
 clip=b.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];navigator.clipboard.readText().then(done);','args':[]})
 check('copied text excludes internal note checklist','posição de leitura'not in clip and 'duas linhas visuais'not in clip)
 b.js('document.querySelector("#estrabaoFootnoteReview").scrollIntoView()');b.screenshot('footnote-checklist')
 fixture('4');generate();check('four confirmed notes produce a correction',b.js('return document.querySelector("#estrabaoDeskBody").value.includes("confirmadas 4 notas")'))
 fixture('endnotes');generate();check('endnotes warning is internal',b.js('return document.querySelector("#estrabaoFootnoteReview").textContent.includes("indicadores de notas de fim") && !document.querySelector("#estrabaoDeskBody").value.includes("notas de fim")'))
 fixture('0');generate();check('zero notes without other indicators has no pending checklist',b.js('return !document.querySelector("#estrabaoFootnoteReview")'))
 check('review and copying do not change submission state',before==state())
finally:
 fixture('restore');b.call('DELETE','')
(root/'results.json').write_text(json.dumps(results,indent=2))
