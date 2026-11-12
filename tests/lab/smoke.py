# Optional real-OJS smoke suite. Requires a freshly seeded isolated local lab.
from browser import *
admin=Browser((ROOT/'admin-session.txt').read_text());author=Browser((ROOT/'author-session.txt').read_text());state=json.loads((ROOT/'state.json').read_text());results=[]
def check(name,condition):
 results.append({'case':name,'passed':bool(condition)});print(('PASS 'if condition else 'FAIL ')+name,flush=True)
 if not condition:raise AssertionError(name)
def db():return {int(r['submission_id']):dict(r,submission_progress=int(r['submission_progress'])) for r in json.loads(subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))}
def review(kind):
 i=state['submissions'][kind]['id'];admin.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}');return i
def action(action):
 admin.js('let b=document.querySelector("button[name=reviewAction][value="+arguments[0]+"]");if(arguments[0]==="approve")b.form.querySelector("[name=apaReviewed]").checked=true;b.click()',action)
 for _ in range(30):
  if 'Decisão editorial registrada.'in admin.text():return
  time.sleep(.1)
 raise AssertionError('decision not saved: '+admin.text()[-400:])
def finish(i,valid=True):
 author.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
 for _ in range(30):
  if author.js('return !!document.querySelector("#submitStep4Form")'):break
  time.sleep(.1)
 return author.call('POST','/execute/async',{'script':'''let done=arguments[arguments.length-1];let f=document.querySelector('#submitStep4Form');let p=new URLSearchParams(new FormData(f));if(!arguments[0])p.set('csrfToken','invalid-synthetic-token');fetch(f.action,{method:'POST',body:p}).then(async r=>done({status:r.status,body:await r.text()})).catch(e=>done({error:String(e)}));''','args':[valid]})
i=review('abnt');check('ABNT has no release button',admin.js('return !document.querySelector("button[value=approve]")'));r=finish(i);check('direct POST ABNT blocked before completion',db()[i]['submission_progress']==4 and db()[i]['date_submitted']is None)
i=review('review');check('real translations loaded','##plugins.generic.estrabao'not in admin.text());action('approve');check('release active in UI','Há liberação editorial válida'in admin.text());admin.screenshot('editorial-release');r=finish(i);print('completion response',r['body'][:400],flush=True);check('reviewed version completes in real OJS',db()[i]['submission_progress']==0 and db()[i]['date_submitted']is not None)
i=review('revoke');action('approve');action('revoke');r=finish(i);check('revoked release blocks completion',db()[i]['submission_progress']==4 and db()[i]['date_submitted']is None)
i=review('csrf');action('approve');r=finish(i,False);check('human release does not bypass native CSRF',db()[i]['submission_progress']==4 and db()[i]['date_submitted']is None)
author.get('http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId=4');check('author cannot access editorial review','name="reviewAction"'not in author.js('return document.documentElement.outerHTML') and 'Baixar a versão atual'not in author.text())
(ROOT/'e2e-results.json').write_text(json.dumps(results,indent=2));print('cases',len(results),flush=True)

# Reuse only helpers; do not rerun the already completed submission case.
from browser import *
admin=Browser((ROOT/'admin-session.txt').read_text());author=Browser((ROOT/'author-session.txt').read_text());state=json.loads((ROOT/'state.json').read_text());results=json.loads((ROOT/'e2e-results.json').read_text())
def check(n,v):
 print(('PASS 'if v else 'FAIL ')+n,flush=True);assert v,n;results.append({'case':n,'passed':bool(v)});(ROOT/'e2e-results.json').write_text(json.dumps(results,indent=2))
i=state['submissions']['replace']['id'];url=f'http://127.0.0.1:18080/index.php/estrabao-lab/estrabaoAdmission/review?submissionId={i}'
admin.get(url);admin.js('document.querySelector("[name=apaReviewed]").checked=true;document.querySelector("button[value=approve]").click()');time.sleep(.3)
check('replacement scenario starts with active release','Há liberação editorial válida'in admin.text())
subprocess.run(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/replace.php'],check=True,stdout=subprocess.DEVNULL)
admin.get(url);check('new server file version invalidates release','Há liberação editorial válida'not in admin.text())
download=admin.js('return [...document.querySelectorAll("a")].find(a=>a.textContent.includes("Baixar a versão atual")).href')
r=admin.call('POST','/execute/async',{'script':'let done=arguments[arguments.length-1];fetch(arguments[0]).then(async r=>done({status:r.status,disposition:r.headers.get("content-disposition"),bytes:[...new Uint8Array(await r.arrayBuffer())].slice(0,4)}));','args':[download]})
check('authenticated download returns DOCX bytes',r['status']==200 and r['bytes']==[80,75,3,4] and 'manuscrito.docx'in r['disposition'])
author.get('http://127.0.0.1:18080/index.php/estrabao-lab');r=author.call('POST','/execute/async',{'script':'let done=arguments[arguments.length-1];fetch(arguments[0]).then(async r=>done({status:r.status,bytes:[...new Uint8Array(await r.arrayBuffer())].slice(0,4)}));','args':[download]})
check('author cannot use editorial download route',r['bytes']!=[80,75,3,4])
author.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
for _ in range(30):
 if author.js('return !!document.querySelector("#submitStep4Form")'):break
 time.sleep(.1)
r=author.call('POST','/execute/async',{'script':'let done=arguments[arguments.length-1];let f=document.querySelector("#submitStep4Form");fetch(f.action,{method:"POST",body:new URLSearchParams(new FormData(f))}).then(async r=>done(await r.text()));','args':[]})
db=json.loads(subprocess.check_output(['docker','exec','estrabao-lab-15-web','php','/tmp/state-query.php']))
check('changed version cannot complete with old release',next(r for r in db if r['submission_id']==i)['submission_progress']==4)
# Use the visible submission UI, including OJS's confirmation dialog.
i=state['submissions']['abnt']['id'];author.get(f'http://127.0.0.1:18080/index.php/estrabao-lab/submission/wizard/4?submissionId={i}')
for _ in range(30):
 if author.js('return !!document.querySelector("#submitStep4Form")'):break
 time.sleep(.1)
author.js('document.querySelector("[name=submitFormButton]").click()');time.sleep(.3)
print('dialog UI',author.text()[-900:],flush=True)
(ROOT/'dialog-inspect.txt').write_text(author.js('return document.documentElement.outerHTML'))
author.screenshot('author-confirmation-dialog')

from browser import *
a=Browser((ROOT/'author-session.txt').read_text());m=Browser((ROOT/'admin-session.txt').read_text());results=json.loads((ROOT/'e2e-results.json').read_text())
def check(n,v):
 print(('PASS 'if v else 'FAIL ')+n,flush=True);assert v,n;results.append({'case':n,'passed':bool(v)});(ROOT/'e2e-results.json').write_text(json.dumps(results,indent=2))
a.js('let b=[...document.querySelectorAll("button.pkpModalConfirmButton")].find(b=>b.textContent.trim()==="OK");if(!b)throw Error("OK missing");b.click();')
for _ in range(40):
 if 'Referências em ABNT não são aceitas'in a.text():break
 time.sleep(.15)
check('author sees ABNT correction after real UI submission','Referências em ABNT não são aceitas'in a.text())
print(a.text()[-2000:],flush=True);a.screenshot('author-abnt-feedback')
# Query-based routing and lookup preservation.
subprocess.run(['docker','exec','estrabao-lab-15-web','php','-r',"$p='config.inc.php';$s=file_get_contents($p);$s=preg_replace('/^disable_path_info\\s*=.*$/m','disable_path_info = On',$s);file_put_contents($p,$s);"],check=True)
m.get('http://127.0.0.1:18080/index.php?journal=estrabao-lab&page=estrabaoAdmission&op=review&submissionId=4')
check('query-based review route renders','Número da submissão 4'in m.text())
check('lookup preserves query router parameters',m.js('return document.querySelector("input[name=journal]")?.value==="estrabao-lab"&&document.querySelector("input[name=page]")?.value==="estrabaoAdmission"'))
m.js('let x=document.querySelector("#estrabaoSubmissionId");x.value="1";x.form.submit();');time.sleep(.3)
check('query-based lookup reaches ABNT record','Número da submissão 1'in m.text() and 'Referências em ABNT'in m.text())
m.screenshot('review-query-route')
subprocess.run(['docker','exec','estrabao-lab-15-web','php','-r',"$p='config.inc.php';$s=file_get_contents($p);$s=preg_replace('/^disable_path_info\\s*=.*$/m','disable_path_info = Off',$s);file_put_contents($p,$s);"],check=True)
print('cases total',len(results),flush=True)
