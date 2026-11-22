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

prepare()
check('native author creates draft', inspect()['progress'] == 2)
r = upload('abnt')
check('native multipart upload succeeds', r['status'] is True)
f = a.form(step(2), 'submitStep2Form'); a.post(f['action'], f['data'])
metadata_page = step(3)['body']
(folder/'automatic-metadata.html').write_text(metadata_page)
check('reference field is visible in homologation', 'citationsRaw' in metadata_page)
check('funding field is visible in homologation', '-agencies' in metadata_page or 'id="agencies"' in metadata_page)
save_metadata()
check('draft reaches confirmation', inspect()['progress'] == 4)
finish(a, 'review', False)
check('invalid CSRF cannot complete or reject', inspect()['progress'] == 4)
confirmation = a.form(step(4), 'submitStep4Form')
r = a.post(confirmation['action'], confirmation['data'])
check('native confirmation reports completion', json.loads(r['body'])['status'] is True)
current = inspect()
check('submission is received then declined', current['progress'] == 0 and current['status'] == 4 and current['date_submitted'] is not None)
page = a.get(base + '/submission/step/5?submissionId=' + str(sid))
check('author sees automatic rejection and letter', 'Submissão rejeitada' in json.loads(page['body'])['content'] and 'Financiamento nos metadados' in json.loads(page['body'])['content'])
page = b.view()
check('editor sees automatic decision', 'Submissão rejeitada' in page['body'] and 'triagem de conformidade' in page['body'])
check('editor cannot release already declined submission', 'estrabaoApaReviewForm' not in page['body'])
copy_helper(Path(__file__).with_name('inspect.php'), 'automatic-inspect.php')
status = json.loads(docker('exec','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/automatic-inspect.php',str(sid)))
check('one native decline decision belongs to configured editor', status['decisions'] == 1 and status['editor_matches'])
check('native email log contains automatic letter', status['mail_logged'] and status['mail_state'] == 'sent')
# A repeat confirmation must not create another decision or another automatic message.
a.post(confirmation['action'], confirmation['data'])
repeat = json.loads(docker('exec','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/automatic-inspect.php',str(sid)))
check('repeat confirmation is idempotent', repeat == status)
# Native second draft, with an unavailable analyzer: receive and call the editor, never reject.
page = a.get(base + '/submission/step/1'); raw = json.loads(page['body'])['content']
f = Forms(raw).forms['submitStep1Form']
data = {**f['data'], 'locale':'pt_BR','sectionId':str(setup['section_id']),'userGroupId':str(setup['author_group_id']),'privacyConsent':'1',**{n:'1' for n in re.findall('name="(checklist-\\d+)"',raw)}}
r=json.loads(a.post(f['action'],data)['body'])
redirect=next(x['data'] for x in r['events'] if x['name']=='redirectRequested')
sid=int(re.search('submissionId=(\\d+)',redirect)[1]);ids['review']=sid
state={'submission_id':sid,'baseline_max_id':sid-1};(folder/'state.json').write_text(json.dumps(state))
docker('cp',str(folder/'state.json'),web+':/tmp/estrabao63-state.json');docker('exec','--user','0',web,'chown','100:101','/tmp/estrabao63-state.json')
upload('apa');f=a.form(step(2),'submitStep2Form');a.post(f['action'],f['data']);save_metadata(True)
code='$p="/var/www/html/config.inc.php";$s=file_get_contents($p);$s=preg_replace("/^engine_bootstrap.*$/m","engine_bootstrap = /unavailable-synthetic-bootstrap.php",$s);file_put_contents($p,$s);'
docker('exec','--user','0',web,'php','-r',code)
try:
    finish(a,'review')
    pending=inspect()
    check('unavailable engine receives without automatic rejection',pending['progress']==0 and pending['status']==1)
    page=a.get(base+'/submission/step/5?submissionId='+str(sid))
    check('author sees editorial pending after analyzer failure','aguardando conferência editorial' in json.loads(page['body'])['content'])
    pending_state=json.loads(docker('exec','-e','ESTRABAO_SYNTHETIC_LAB=1',web,'php','/tmp/automatic-inspect.php',str(sid)))
    check('editor receives native task after analyzer failure',pending_state['editor_tasks'] > 0 and pending_state['decisions'] == 0)
finally:
    # Restore the exact private config captured before the unavailable-engine rehearsal.
    docker('exec','--user','0',web,'php','-r','$p="/var/www/html/config.inc.php";$s=file_get_contents($p);$o=file_get_contents("/tmp/estrabao-stage66-config.original");preg_match("/^engine_bootstrap.*$/m",$o,$m);$s=preg_replace("/^engine_bootstrap.*$/m",$m[0],$s);file_put_contents($p,$s);')
out=ROOT/'docs/evidence/stage66';out.mkdir(parents=True,exist_ok=True)
(out/'automatic-results.json').write_text(json.dumps(results,indent=2)+'\n')
