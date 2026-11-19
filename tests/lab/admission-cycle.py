"""Synthetic wizard cycle. Requires fresh seed and the integrated preflight.
Changes only newly created synthetic drafts; this is not a production utility.
"""
import json,subprocess,time
from browser import Browser,ROOT
results=[]
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 results.append({'case':name,'passed':bool(ok)})
def db():return {int(r['submission_id']):r for r in json.loads(subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))}
state=json.loads((ROOT/'state.json').read_text());ids={k:v['id']for k,v in state['submissions'].items()}
# Never reuse a completed or non-fresh dataset.
initial=db()
if not all(initial[i]['submission_progress']==4 and initial[i]['date_submitted'] is None for i in ids.values()):raise RuntimeError('Fresh synthetic drafts required')
b=Browser();a=Browser();b.login('admin');a.login('author')
def view(kind):
 i=ids[kind];b.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}');return i
def action(value):
 b.js('let x=document.querySelector("button[name=reviewAction][value="+arguments[0]+"]");if(arguments[0]==="approve")x.form.querySelector("[name=apaReviewed]").checked=true;x.click()',value)
 for _ in range(40):
  if 'Decisão editorial registrada.'in b.text():return
  time.sleep(.1)
 raise AssertionError('decision not recorded')
def finish(i,valid=True):
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 return a.call('POST','/execute/async',{'script':'''const done=arguments[arguments.length-1];const f=document.querySelector('#submitStep4Form');const p=new URLSearchParams(new FormData(f));if(!arguments[0])p.set('csrfToken','invalid-synthetic');fetch(f.action,{method:'POST',body:p}).then(async r=>done(await r.text()));''','args':[valid]})
try:
 i=view('abnt');check('ABNT cannot receive a release',b.js('return !document.querySelector("button[value=approve]")'))
 response=finish(i);check('ABNT POST cannot complete',db()[i]['submission_progress']==4 and db()[i]['date_submitted'] is None)
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(40):
  if a.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 a.js('document.querySelector("[name=submitFormButton]").click()')
 for _ in range(40):
  if a.js('return Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).some(x=>x.textContent.trim()==="OK")'):break
  time.sleep(.1)
 a.js('Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.textContent.trim()==="OK").click()')
 for _ in range(40):
  if 'Referências em ABNT não são aceitas'in a.text():break
  time.sleep(.1)
 check('native UI shows ABNT correction without completing', 'Referências em ABNT não são aceitas'in a.text() and db()[i]['submission_progress']==4)
 a.screenshot('current-author-abnt-feedback')
 # Replace the blocked draft with a real new server file, then require fresh human review.
 subprocess.run(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/replace.php','--correct-abnt'],check=True,stdout=subprocess.DEVNULL)
 def inspect_corrected():return json.loads(subprocess.check_output(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/replace.php','--inspect-corrected']))
 corrected=inspect_corrected();analysis=corrected['analysis']
 check('correction uses a new server file',int(corrected['file_id'])!=int(state['submissions']['abnt']['file_id']))
 check('combined years et al undated and narrative locator keys are all linked',corrected['available'] and analysis['parsed_references']==7 and analysis['recognized_citations']==7 and analysis['matched_citations']==7 and analysis['unparsed_citation_groups']==0 and analysis['citation_style_counts']['não conforme']==0)
 check('combined quote keeps location and technical word interval',analysis['direct_quote_counts']['candidate_count']==1 and analysis['direct_quote_counts']['missing_locator_candidates']==0 and any(s['word_interval']==[39,39] and s['locator_kind']=='pages' for s in analysis['citation_style_samples']))
 audit=corrected['style_audit']
 check('combined single author and coauthors have safe declared order',audit['author_list_order_status']=='conforme' and audit['author_list_pairs_checked']==2 and audit['alphabetical_order_status']=='conforme')
 view('abnt');check('corrected combined APA manuscript becomes reviewable',b.js('return !!document.querySelector("button[value=approve]")'))
 check('combined review gives private quote guidance without source text',b.js('return !!document.querySelector("#estrabaoDirectQuoteReview") && !document.querySelector("#estrabaoDirectQuoteReview").textContent.includes("trecho trecho")'))
 check('correction does not create an automatic release','Há liberação editorial válida'not in b.text())
 finish(i);check('corrected draft still requires human review',db()[i]['submission_progress']==4 and db()[i]['date_submitted'] is None)
 view('abnt');action('approve');check('new analysis can receive explicit human release','Há liberação editorial válida'in b.text());b.screenshot('corrected-combined-apa-review')
 finish(i);check('corrected reviewed manuscript completes wizard',db()[i]['submission_progress']==0 and db()[i]['date_submitted'] is not None)
 check('review and completion preserve corrected manuscript bytes',inspect_corrected()['sha256']==corrected['sha256'])
 i=view('review');action('approve');check('human APA review activates release','Há liberação editorial válida'in b.text());b.screenshot('current-editorial-release')
 finish(i);check('reviewed current version completes native wizard',db()[i]['submission_progress']==0 and db()[i]['date_submitted'] is not None)
 i=view('revoke');action('approve');action('revoke');finish(i);check('revoked release prevents completion',db()[i]['submission_progress']==4)
 i=view('csrf');action('approve');finish(i,False);check('release cannot bypass native CSRF',db()[i]['submission_progress']==4)
 i=view('replace');action('approve');check('replacement starts from active release','Há liberação editorial válida'in b.text())
 subprocess.run(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/replace.php'],check=True,stdout=subprocess.DEVNULL)
 view('replace');check('new server file invalidates old release','Há liberação editorial válida'not in b.text())
 download=b.js('return Array.from(document.querySelectorAll("a")).find(x=>x.textContent.includes("Baixar a versão atual")).href')
 def fetchfile(browser):return browser.call('POST','/execute/async',{'script':'''const done=arguments[arguments.length-1];fetch(arguments[0]).then(async r=>done({status:r.status,bytes:Array.from(new Uint8Array(await r.arrayBuffer())).slice(0,4)}));''','args':[download]})
 r=fetchfile(b);check('editor downloads current DOCX',r['status']==200 and r['bytes']==[80,75,3,4])
 a.get('http://127.0.0.1:18080/index.php/estrabao-lab');check('author cannot download via editorial route',fetchfile(a)['bytes']!=[80,75,3,4])
 finish(i);check('old release cannot complete changed version',db()[i]['submission_progress']==4)
 a.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}');check('author cannot open editorial review','name="reviewAction"'not in a.js('return document.documentElement.outerHTML'))
finally:
 b.call('DELETE','');a.call('DELETE','')
(ROOT/'cycle-results.json').write_text(json.dumps(results,indent=2))
