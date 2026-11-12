# Optional local OJS 3.3.0.20 staging suite; synthetic fixtures only.
import os,json,time,subprocess,base64
from pathlib import Path
from browser import Browser
ROOT=Path(os.environ.get('ESTRABAO_DESK_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-17'));ROOT.mkdir(parents=True,exist_ok=True);b=Browser();b.login('admin');a=Browser();a.login('author');results=[]
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True);assert ok,name;results.append({'case':name,'passed':bool(ok)})
def generate(url='http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=1'):
 b.get(url);b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.requestSubmit();')
 for _ in range(40):
  if b.js('return !!document.querySelector("#estrabaoDeskBody")'):return
  time.sleep(.1)
 raise AssertionError('draft missing: '+b.text()[-300:])
def getdb():return json.loads(subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))
before=getdb();generate();check('real OJS renders draft and translations','##plugins.generic.estrabao.desk'not in b.text())
check('source is current OJS publication',b.js('return document.querySelector("#estrabaoDeskBody").value.includes("campo de título em espanhol")'))
check('ABNT letter does not grant admission',b.js('return document.querySelector("#estrabaoDeskBody").value.includes("padrões ABNT reconhecidos") && !document.querySelector("button[value=approve]")'))
check('pending editorial checks stay outside author body',b.js('return !!document.querySelector("details") && !document.querySelector("#estrabaoDeskBody").value.includes("deskPending")'))
b.js('document.querySelector("details").open=true;document.querySelector("#estrabaoDeskBody").value+="\\nRevisão editorial sintética.";')
b.call('POST','/goog/cdp/execute',{'cmd':'Browser.grantPermissions','params':{'origin':'http://127.0.0.1:18080','permissions':['clipboardReadWrite','clipboardSanitizedWrite']}})
b.js('document.querySelector("#estrabaoCopyDesk").click()');time.sleep(.2)
clip=b.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];navigator.clipboard.readText().then(done).catch(e=>done("ERROR "+e));','args':[]})
check('copy includes reviewed subject and edited body',clip.startswith('Adequação às diretrizes editoriais\n\nPrezados') and 'Revisão editorial sintética.'in clip)
check('copy excludes internal panel, technical fields and hashes','Conferência interna do editor'not in clip and 'source_input_sha256'not in clip and 'pending_review'not in clip)
check('copy reports success','Assunto e texto copiados'in b.text())
b.js('document.querySelector("#estrabaoDeskDraftTitle").scrollIntoView();')
(ROOT/'draft-panel.png').write_bytes(base64.b64decode(b.call('GET','/screenshot')))
check('generation and copying do not change submission state',before==getdb())
# Force clipboard denial: leave body selectable, no success claim.
b.js('navigator.clipboard.writeText=()=>Promise.reject(new Error("synthetic denial"));document.querySelector("#estrabaoCopyDesk").click();');time.sleep(.2)
check('copy failure provides a usable manual fallback','copie manualmente'in b.text() and b.js('return document.activeElement.id==="estrabaoDeskBody"'))
# Direct GET cannot generate a draft.
b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/deskDraft?submissionId=1')
check('GET request cannot generate',b.js('return !document.querySelector("#estrabaoDeskBody")'))
# Invalid CSRF cannot generate; using a valid initial page fingerprint still fails.
b.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=1')
b.js('let s=document.querySelector("#estrabaoManuscriptType");s.value="article";s.form.querySelector("[name=csrfToken]").value="bad-synthetic";s.form.requestSubmit();');time.sleep(.3)
check('invalid CSRF cannot generate',b.js('return !document.querySelector("#estrabaoDeskBody")'))
a.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/deskDraft?submissionId=1')
check('author cannot access draft route',a.js('return !document.querySelector("#estrabaoDeskBody")') and 'Gerar devolutiva'not in a.text())
# Check query routing with the real form POST, then restore application mode.
try:
 subprocess.run(['docker','exec','estrabao-lab-15-web','php','-r',"$p='config.inc.php';$s=file_get_contents($p);file_put_contents($p,preg_replace('/^disable_path_info\\s*=.*$/m','disable_path_info = On',$s));"],check=True)
 generate('http://127.0.0.1:18080/index.php?journal=estrabao-lab&page=estrabaoAdmission&op=review&submissionId=1')
 check('query-based form generates real draft',b.js('return !!document.querySelector("#estrabaoDeskBody")'))
finally:
 subprocess.run(['docker','exec','estrabao-lab-15-web','php','-r',"$p='config.inc.php';$s=file_get_contents($p);file_put_contents($p,preg_replace('/^disable_path_info\\s*=.*$/m','disable_path_info = Off',$s));"],check=True)
(ROOT/'results.json').write_text(json.dumps(results,indent=2));print('checks',len(results),flush=True)

b.call('DELETE','');a.call('DELETE','')
