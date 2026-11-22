import sys,json,re
from pathlib import Path
ROOT=Path(__file__).resolve().parents[3]
sys.path.insert(0,str(ROOT/'tests/lab/editorial'))
from common import *
from documents import prepare
copy_helper(ROOT/'tests/lab/editorial/inspect.php','editorial-inspect.php')
setup=json.loads(docker('exec','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/editorial-inspect.php','prepare'))
code='require "tools/bootstrap.inc.php";new CommandLineTool([]);$j=Application::getContextDAO()->getByPath("estrabao-lab");$j->setData("supportedFormLocales",["pt_BR"]);$j->setData("supportedSubmissionLocales",["pt_BR"]);$j->setData("citations","request");$j->setData("agencies","request");Application::getContextDAO()->updateObject($j);'
docker('exec','-w','/var/www/html',web,'php','-r',code)
a=Session('singlefund','author')
try:
    raw=json.loads(a.get(base+'/submission/step/1')['body'])['content'];f=Forms(raw).forms['submitStep1Form'];data={**f['data'],'locale':'pt_BR','sectionId':str(setup['section_id']),'userGroupId':str(setup['author_group_id']),'privacyConsent':'1',**{n:'1' for n in re.findall('name="(checklist-\\d+)"',raw)}}
    r=json.loads(a.post(f['action'],data)['body']);redirect=next(x['data'] for x in r['events'] if x['name']=='redirectRequested');sid=int(re.search('submissionId=(\\d+)',redirect)[1])
    # Native file uploader, then native metadata page.
    prepare();stem=base+'/$$$call$$$/wizard/file-upload/file-upload-wizard/';params='?submissionId='+str(sid)+'&stageId=1&fileStage=2'
    r=json.loads(a.get(stem+'display-file-upload-form'+params)['body']);f=next(iter(Forms(r['content']).forms.values()))
    payload={'session':a.name,'method':'POST','path':stem+'upload-file'+params,'data':{**f['data'],'genreId':str(setup['genre_id'])},'file':'apa'}
    docker('exec','-i','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/editorial-http.php',input=json.dumps(payload).encode())
    raw=json.loads(a.get(base+'/submission/step/2?submissionId='+str(sid))['body'])['content'];f=Forms(raw).forms['submitStep2Form'];a.post(f['action'],f['data'])
    raw=json.loads(a.get(base+'/submission/step/3?submissionId='+str(sid))['body'])['content']
    assert 'agencies' in raw and 'citationsRaw' in raw
    Path('/workspace/scratch/estrabao-lab-63/single-metadata.html').write_text(raw)
    print('Captured native single-locale metadata form')
finally:
    code='require "tools/bootstrap.inc.php";new CommandLineTool([]);$j=Application::getContextDAO()->getByPath("estrabao-lab");$j->setData("supportedFormLocales",["pt_BR","en_US","es_ES"]);$j->setData("supportedSubmissionLocales",["pt_BR","en_US","es_ES"]);$j->setData("citations",1);$j->setData("agencies",1);Application::getContextDAO()->updateObject($j);'
    docker('exec','-w','/var/www/html',web,'php','-r',code)
