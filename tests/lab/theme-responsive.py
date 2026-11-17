"""Verify the user-reported default-theme profile only in the isolated synthetic lab.
Does not change theme options, CSS, locales, metadata or publication policy.
The synthetic article date/status fixture is restored in finally.
"""
import os,json,subprocess,time,base64,hashlib
from pathlib import Path
for key in ['DOCKER_CONTEXT','DOCKER_TLS','DOCKER_TLS_VERIFY','DOCKER_CERT_PATH']:
 os.environ.pop(key,None)
os.environ['DOCKER_HOST']='unix:///var/run/docker.sock'
from browser import Browser
HERE=Path(__file__).resolve().parent
ROOT=Path(os.environ.get('ESTRABAO_THEME_EVIDENCE_DIR','/workspace/scratch/estrabao-lab-28'));ROOT.mkdir(parents=True,exist_ok=True);ROOT.chmod(0o700)
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':False,'status':'running'}))
def cmd(args,**kw):return subprocess.check_output(args,**kw)
containers=json.loads(cmd(['docker','inspect','estrabao-lab-15-web','estrabao-lab-15-browser']))
web=next(c for c in containers if c['Name']=='/estrabao-lab-15-web')
for c in containers:
 if not c['State']['Running']:raise RuntimeError('Synthetic lab must be running')
 nets=list(c['NetworkSettings']['Networks'])
 if not nets and c['HostConfig']['NetworkMode']=='container:'+web['Id']:nets=list(web['NetworkSettings']['Networks'])
 if not nets or not all(json.loads(cmd(['docker','network','inspect',n]))[0]['Internal']for n in nets):raise RuntimeError('Internal lab network required')
 if any(b.get('HostIp')not in ['127.0.0.1','::1']for bindings in (c['HostConfig'].get('PortBindings')or{}).values()for b in bindings or []):raise RuntimeError('Public port binding is not allowed')
if not all(any(m['Destination']==dest and not m['RW'] and Path(m['Source']).resolve()==(HERE.parents[1]/relative).resolve()for m in web['Mounts'])for dest,relative in [('/opt/estrabao/src','src'),('/var/www/html/plugins/generic/estrabao','plugins/generic/estrabao')]):raise RuntimeError('Read-only checkout mounts required')
if 'sendmail_path=/bin/true'not in (web['Config'].get('Cmd')or[]):raise RuntimeError('Neutralized web email required')
for name in ['preflight.php','theme-profile.php','article-date-fixture.php']:
 cmd(['docker','cp',str(HERE/name),'estrabao-lab-15-web:/tmp/'+name])
def fixture(mode):return cmd(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/article-date-fixture.php',mode])
def profile():return json.loads(cmd(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/theme-profile.php']))
preflight=json.loads(cmd(['docker','exec','-e','ESTRABAO_SYNTHETIC_LAB=1','estrabao-lab-15-web','php','/tmp/preflight.php']))
p=profile();checks=[];metrics=[]
def check(name,ok):
 print(('PASS 'if ok else 'FAIL ')+name,flush=True)
 if not ok:raise AssertionError(name)
 checks.append({'case':name,'passed':bool(ok)})
check('lab matches reported default theme Noto Sans color and no uploaded stylesheet',p['theme_setting']=='default'and p['typography']=='notoSans'and p['header_color'].upper()=='#1E6292'and p['additional_css_present']is False)
manifest=json.loads((HERE.parents[1]/'docs/evidence/stage27/release-source-manifest.json').read_text())
check('product files still match integrated homologation',all(hashlib.sha256((HERE.parents[1]/name).read_bytes()).hexdigest()==value for name,value in manifest['sha256'].items()))
check('installed plugin and engine match homologated versions',preflight['runtime']['installed_plugin']=='0.12.0.0'and preflight['runtime']['engine']=='0.20.0')
# Resolve the same synthetic record used by the fixture instead of assuming an ID.
state=json.loads(cmd(['docker','exec','estrabao-lab-15-web','cat','/tmp/lab-state.json']))
id=state['submissions']['review']['id'];before=fixture('state');b=Browser()
try:
 fixture('published')
 for width,height in [(1440,1000),(1024,768),(768,1024),(390,844),(320,700)]:
  b.call('POST','/goog/cdp/execute',{'cmd':'Emulation.setDeviceMetricsOverride','params':{'width':width,'height':height,'deviceScaleFactor':1,'mobile':width<768}})
  b.get('http://127.0.0.1:18080/index.php/estrabao-lab/article/view/'+str(id))
  b.call('POST','/execute/async',{'script':'const done=arguments[arguments.length-1];document.fonts.ready.then(()=>done(true));','args':[]})
  data=b.js('''const date=document.querySelector('.estrabao-submitted-date');const box=document.querySelector('.item.published');const published=box?.querySelector('section:not(.estrabao-submitted-date)');const rect=x=>{const r=x.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height}};return {width:innerWidth,scrollWidth:document.documentElement.scrollWidth,date:date?rect(date):null,published:published?rect(published):null,count:document.querySelectorAll('.estrabao-submitted-date').length,first:box?.firstElementChild===date,iso:date?.querySelector('time')?.dateTime,publishedRetained:published?.textContent.includes('2026-02-04'),font:getComputedStyle(document.body).fontFamily,loadedFont:Array.from(document.fonts).some(f=>f.family.includes('Noto Sans')&&f.status==='loaded'),headerColor:getComputedStyle(document.querySelector('.pkp_structure_head')).backgroundColor};''')
  tag=str(width)+'x'+str(height);metrics.append({'viewport':tag,**data})
  check(tag+' native profile has loaded Noto Sans and header color',data['loadedFont']and'Noto Sans'in data['font']and data['headerColor']=='rgb(30, 98, 146)')
  check(tag+' submitted precedes published once in the same box',data['count']==1 and data['first']and data['iso']=='2026-01-02'and data['publishedRetained'])
  check(tag+' dates remain visible and do not overlap',data['date']['height']>0 and data['published']['height']>0 and data['date']['bottom']<=data['published']['top']+1)
  check(tag+' no horizontal overflow',data['width']==width and data['scrollWidth']<=width+1 and data['date']['left']>=-1 and data['date']['right']<=width+1)
  b.js('document.querySelector(".item.published").scrollIntoView({block:"center"})')
  (ROOT/('article-'+tag+'.png')).write_bytes(base64.b64decode(b.call('GET','/screenshot')))
 check('profile was not changed during visual tests',p==profile())
finally:
 fixture('restore');b.call('DELETE','')
check('synthetic workflow dates and status restored',before==fixture('state'))
(ROOT/'profile.json').write_text(json.dumps({'reported_profile':{'source':'user-provided screenshots and statement','theme':'Tema Padrão','font':'Noto Sans','header_color':'#1E6292','additional_css_reported':False,'actual_theme_version_not_observed':True},'lab_profile':p,'runtime':preflight['runtime'],'viewport_metrics':metrics},indent=2))
(ROOT/'results.json').write_text(json.dumps(checks,indent=2))
(ROOT/'summary.json').write_text(json.dumps({'overall_passed':True,'checks':len(checks),'viewport_count':len(metrics),'product_changed':False,'theme_settings_changed':False,'original_workflow_records_restored':True,'production_accessed':False,'limitations':['reported_profile_reproduced_in_lab_only','real_theme_files_and_version_not_inspected','real_infrastructure_not_homologated']},indent=2))
