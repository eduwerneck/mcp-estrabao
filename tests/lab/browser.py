import json,subprocess,time,base64,os
from pathlib import Path
ROOT=Path(os.environ.get('ESTRABAO_LAB_DIR','/workspace/scratch/estrabao-lab-15'))
def wd(method,path,data=None):
 p={'method':method,'path':path}
 if data is not None:p['data']=data
 out=subprocess.check_output(['docker','exec','-i','estrabao-lab-15-web','php','/tmp/http-bridge.php'],input=json.dumps(p).encode())
 value=json.loads(out)['value']
 if isinstance(value,dict) and 'error'in value:raise RuntimeError(value['error']+': '+value.get('message','')[:500])
 return value
class Browser:
 def __init__(self,session=None):
  self.id=session or wd('POST','/session',{'capabilities':{'alwaysMatch':{'browserName':'chrome','goog:chromeOptions':{'args':['--headless=new','--no-sandbox','--disable-dev-shm-usage','--window-size=1365,1000','--no-proxy-server']}}}})['sessionId']
 def call(self,m,p,d=None):return wd(m,'/session/'+self.id+p,d)
 def get(self,url):return self.call('POST','/url',{'url':url})
 def js(self,script,*args):return self.call('POST','/execute/sync',{'script':script,'args':list(args)})
 def text(self):return self.js('return document.body.innerText')
 def screenshot(self,name):p=ROOT/(name+'.png');p.write_bytes(base64.b64decode(self.call('GET','/screenshot')));return str(p)
 def login(self,role):
  c=json.loads((ROOT/'credentials.json').read_text());self.get('http://127.0.0.1:18080/index.php/index/login')
  self.js('document.querySelector("[name=username]").value=arguments[0];document.querySelector("[name=password]").value=arguments[1];document.querySelector("[name=password]").form.submit();',c[role+'_username'],c[role+'_password'])
  for _ in range(30):
   url=self.call('GET','/url')
   if '/login' not in url:return url
   time.sleep(.2)
  raise RuntimeError('login did not leave login page')
