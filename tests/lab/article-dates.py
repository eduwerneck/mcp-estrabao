# Synthetic OJS lab only. Every database mutation is restored in finally.
import json,subprocess,time,os
from pathlib import Path
from browser import Browser
root=Path(os.environ.get('ESTRABAO_DATE_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-22'));root.mkdir(parents=True,exist_ok=True);results=[]
def fixture(mode):return subprocess.check_output(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/article-date-fixture.php',mode])
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True);assert ok,name;results.append({'case':name,'passed':bool(ok)})
b=Browser();admin=Browser();admin.login('admin');before=fixture('state');url='http://127.0.0.1:18080/index.php/estrabao-lab/article/view/2'
try:
 fixture('published');state=fixture('state');b.get(url)
 check('anonymous article shows real submission calendar date',b.js('let t=document.querySelector(".estrabao-submitted-date time");return !!t && t.dateTime==="2026-01-02" && t.textContent.trim()==="2026-01-02"'))
 check('submitted date precedes published in the same native box',b.js('let p=document.querySelector(".item.published");return p.firstElementChild.classList.contains("estrabao-submitted-date") && p.textContent.indexOf("Submetido em")<p.textContent.indexOf("Publicado")'))
 check('original published date is retained',b.js('return document.querySelector(".item.published").textContent.includes("2026-02-04")'))
 check('one date only',b.js('return document.querySelectorAll(".estrabao-submitted-date").length===1'))
 b.js('document.querySelector(".item.published").scrollIntoView()');b.screenshot('article-submitted-date')
 check('display does not write workflow dates or status',state==fixture('state'))
 b.call('POST','/goog/cdp/execute',{'cmd':'Emulation.setScriptExecutionDisabled','params':{'value':True}});b.get(url)
 check('date is server rendered without JavaScript','datetime="2026-01-02"' in b.call('GET','/source'))
 b.call('POST','/goog/cdp/execute',{'cmd':'Emulation.setScriptExecutionDisabled','params':{'value':False}})
 fixture('missing');b.get(url);check('missing submitted date leaves publication intact',b.js('return !document.querySelector(".estrabao-submitted-date") && !!document.querySelector(".item.published")'))
 fixture('incoherent');b.get(url);check('incoherent submitted date is omitted',b.js('return !document.querySelector(".estrabao-submitted-date")'))
 fixture('preview');admin.get(url);check('unpublished preview has no public submitted indicator',admin.js('return !document.querySelector(".estrabao-submitted-date")'))
finally:
 fixture('restore');b.call('DELETE','');admin.call('DELETE','')
check('fixture restores original workflow records',before==fixture('state'))
(root/'results.json').write_text(json.dumps(results,indent=2))
