"""New author wizard 1–4, native multipart upload/revision, private desk and release."""
import sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1] / 'editorial'))
from common import *
from documents import prepare
import hashlib, html as markup
copy_helper(Path(__file__).resolve().parents[1] / 'editorial/inspect.php', 'editorial-inspect.php')
setup = json.loads(docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/editorial-inspect.php', 'prepare'))
a = Session('flowauthor', 'author')
b = Session('floweditor')
page = a.get(base + '/submission/step/1')
raw = json.loads(page['body'])['content']
f = Forms(raw).forms['submitStep1Form']
data = {**f['data'], 'locale': 'pt_BR', 'sectionId': str(setup['section_id']), 'userGroupId': str(setup['author_group_id']), 'privacyConsent': '1', **{n: '1' for n in re.findall('name="(checklist-\\d+)"', raw)}}
r = json.loads(a.post(f['action'], data)['body'])
redirect = next((x['data'] for x in r['events'] if x['name'] == 'redirectRequested'))
sid = int(re.search('submissionId=(\\d+)', redirect)[1])
ids['review'] = sid
state = {'submission_id': sid, 'baseline_max_id': max((int(s['submission_id']) for s in setup['baseline_submissions']))}
(folder / 'state.json').write_text(json.dumps(state))
docker('cp', str(folder / 'state.json'), web + ':/tmp/estrabao63-state.json')
docker('exec', '--user', '0', web, 'chown', '100:101', '/tmp/estrabao63-state.json')

def inspect():
    return json.loads(docker('exec', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/editorial-inspect.php', 'inspect'))

def step(n):
    return {'body': json.loads(a.get(base + '/submission/step/' + str(n) + '?submissionId=' + str(sid))['body'])['content']}

def save_metadata(correct=False):
    f = a.form(step(3), 'submitStep3Form')
    title = 'Paisagem e ensino: estudo sintético' if correct else 'TÍTULO SINTÉTICO EM CAIXA ALTA'
    refs = 'Silva, A. (2020). Paisagem sintética. Editora Exemplo.\nSouza, B. (2021). Ensino sintético. Editora Exemplo.' if correct else 'SILVA, Ana. Paisagem sintética. São Paulo: Editora Exemplo, 2020.'
    data = {**f['data'], 'title': {'pt_BR': title}, 'subtitle': {'pt_BR': 'Ensaio do fluxo editorial'}, 'abstract': {'pt_BR': 'Contexto: estudo sintético. Métodos: ensaio de software. Resultados: fluxo controlado. Conclusão: dados fictícios.'}, 'citationsRaw': refs}
    if correct:
        data['keywords'] = {'pt_BR-keywords': ['paisagem', 'ensino', 'ensaio', 'sintético'], 'en_US-keywords': ['landscape', 'teaching', 'rehearsal', 'synthetic'], 'es_ES-keywords': ['paisaje', 'enseñanza', 'ensayo', 'sintético']}
        data['title'].update(en_US='Landscape and teaching: synthetic study', es_ES='Paisaje y enseñanza: estudio sintético')
        data['abstract'].update(en_US='Context: synthetic study. Methods: software rehearsal. Results: controlled flow. Conclusion: fictional data.', es_ES='Contexto: estudio sintético. Métodos: ensayo de software. Resultados: flujo controlado. Conclusión: datos ficticios.')
    data = {k: v for k, v in data.items() if not k.startswith(('title[', 'subtitle[', 'abstract['))}
    return json.loads(a.post(f['action'], data)['body'])

def upload(kind, revised=None):
    stem = base + '/$$$call$$$/wizard/file-upload/file-upload-wizard/'
    params = '?submissionId=' + str(sid) + '&stageId=1&fileStage=2'
    if revised:
        params += '&revisedFileId=' + str(revised) + '&revisionOnly=1'
    r = json.loads(a.get(stem + 'display-file-upload-form' + params)['body'])
    f = next(iter(Forms(r['content']).forms.values()))
    fields = {**f['data'], 'genreId': str(setup['genre_id'])}
    if revised:
        fields['revisedFileId'] = str(revised)
    payload = {'session': a.name, 'method': 'POST', 'path': stem + 'upload-file' + params, 'data': fields, 'file': kind}
    response = json.loads(docker('exec', '-i', '-e', 'ESTRABAO_SYNTHETIC_LAB=1', web, 'php', '/tmp/editorial-http.php', input=json.dumps(payload).encode()))
    return json.loads(response['body'])

def text_area(page, name):
    return markup.unescape(re.search('<textarea[^>]*id="' + name + '"[^>]*>(.*?)</textarea>', page['body'], re.S)[1])

# Scope is configuration, never a request parameter. Keep a private exact backup for restoration.
docker('exec','--user','0',web,'cp','-p','/var/www/html/config.inc.php','/tmp/estrabao-scope-config.original')
code='$p="/var/www/html/config.inc.php";$s=file_get_contents($p);$s=preg_replace("/^post_submission_triage.*$/m","",$s);$s=preg_replace("/^automation_editor_id.*$/m","",$s);$s=str_replace("[estrabao]","[estrabao]\npost_submission_triage = On\nautomation_editor_id = 1\ntest_submission_id = ".$argv[1]."\ntest_recipient = scope-only@synthetic.invalid",$s);file_put_contents($p,$s);'
docker('exec','--user','0',web,'php','-r',code,str(sid))
copy_helper(Path(__file__).with_name('inspect.php'),'scoped-inspect.php')
def inspect_scope(id):
    return json.loads(docker('exec','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/scoped-inspect.php',str(id)))
try:
    prepare();upload('abnt');f=a.form(step(2),'submitStep2Form');a.post(f['action'],f['data'])
    check('target retains bibliography field', 'citationsRaw' in step(3)['body'])
    save_metadata();target=sid
    finish(a,'review')
    current=inspect_scope(target)
    check('selected submission receives one automatic decline',current['status']==4 and current['decisions']==1 and current['marker'])
    check('only configured test recipient receives automatic letter',current['controlled_recipient'])
    check('test message subject is marked',current['test_subject'])
    page=b.view()
    check('editor can read selected test result','Submissão rejeitada' in page['body'])
    # New native draft outside scope. No direct draft/file insertion.
    raw=json.loads(a.get(base+'/submission/step/1')['body'])['content'];f=Forms(raw).forms['submitStep1Form']
    data={**f['data'],'locale':'pt_BR','sectionId':str(setup['section_id']),'userGroupId':str(setup['author_group_id']),'privacyConsent':'1',**{n:'1' for n in re.findall('name="(checklist-\\d+)"',raw)}}
    r=json.loads(a.post(f['action'],data)['body']);sid=int(re.search('submissionId=(\\d+)',next(x['data'] for x in r['events'] if x['name']=='redirectRequested'))[1]);ids['review']=sid
    upload('abnt');f=a.form(step(2),'submitStep2Form');a.post(f['action'],f['data'])
    check('other submission keeps native field configuration','citationsRaw' not in step(3)['body'])
    save_metadata()
    finish(a,'review')
    other=inspect_scope(sid)
    check('other submission completes normally',other['status']==1 and other['progress']==0)
    check('other submission has no plugin decision marker or letter',other['decisions']==0 and not other['marker'] and not other['test_subject'])
    page=b.view()
    check('direct editorial route cannot bypass scope','page_estrabao_review' not in page['body'])
    (folder/'scope-ids.json').write_text(json.dumps({'target':target,'other':sid}))
    out=ROOT/'docs/evidence/stage68';out.mkdir(parents=True,exist_ok=True)
    (out/'scoped-results.json').write_text(json.dumps(results,indent=2)+'\n')
finally:
    docker('exec','--user','0',web,'cp','-p','/tmp/estrabao-scope-config.original','/var/www/html/config.inc.php')
