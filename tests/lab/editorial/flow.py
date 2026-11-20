"""New author wizard 1–4, native multipart upload/revision, private desk and release."""
from common import *
from documents import prepare
if (folder / 'summary.json').exists():
    raise RuntimeError('Archive previous private stage63 results before creating a new run')
import hashlib, html as markup
copy_helper(Path(__file__).with_name('inspect.php'), 'editorial-inspect.php')
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
check('native author creates a new draft at step 2', sid > state['baseline_max_id'] and inspect()['progress'] == 2)
prepare()
r = upload('abnt')
check('author uploads DOCX by native authenticated multipart', r['status'] is True and len(inspect()['files']) == 1)
original = inspect()['files'][0]
check('server retains uploaded synthetic DOCX bytes', original['sha256'] == hashlib.sha256((folder / 'abnt.docx').read_bytes()).hexdigest())
f = a.form(step(2), 'submitStep2Form')
a.post(f['action'], f['data'])
check('native upload step advances to metadata', inspect()['progress'] == 3)
r = save_metadata()
check('author saves metadata and advances to confirmation', inspect()['progress'] == 4)
check('native author record retained', inspect()['authors_count'] > 0)
check('ABNT cannot receive editorial APA approval', 'value="approve"' not in b.view()['body'])
finish(a, 'review')
check('ABNT is blocked at native final confirmation', inspect()['progress'] == 4 and inspect()['date_submitted'] is None)
page = b.draft()
initial_draft = text_area(page, 'estrabaoDeskBody')
(folder / 'initial-desk.txt').write_text(initial_draft)
check('desk names APA correction and does not accept ABNT', 'APA' in initial_draft and 'ABNT' in initial_draft)
check('desk identifies missing English and Spanish metadata', 'Metadados em inglês' in initial_draft and 'Metadados em espanhol' in initial_draft)
check('desk includes title or manuscript guideline corrections', 'título' in initial_draft.lower() and 'manuscrito' in initial_draft.lower())
check('generating desk does not mutate document', inspect()['files'][0] == original)
check('desk does not generate an APA release', inspect()['ledgers']['review']['events'] == 0)
f = b.form(page, 'estrabaoEditorialForm')
pending = {k: v for k, v in f['data'].items() if k.startswith('editorialStates[')}
check('twelve human checklist categories begin pending', len(pending) == 12 and set(pending.values()) == {'pending'})
data = {**f['data'], 'editorialReviewed': '1', 'editorialStates[metadata]': 'adjustments', 'editorialStates[funding]': 'reviewed', 'editorialStates[ai]': 'reviewed'}
data = {k: v for k, v in data.items() if not k.startswith('editorialStates[') or k in pending}
r = b.post(f['action'], data)
check('editor records guideline adjustments separately', 'Conferência editorial registrada.' in r['body'])
check('non-APA checklist cannot override ABNT', 'value="approve"' not in b.view()['body'])
stale = b.form(b.draft(), 'estrabaoEditorialForm')
old_editorial = inspect()['ledgers']['editorial']
r = upload('apa', original['submission_file_id'])
check('author submits corrected Word as native revision', r['status'] is True)
corrected = inspect()['files'][0]
check('revision has a new file ID with same logical submission file', corrected['file_id'] != original['file_id'] and corrected['submission_file_id'] == original['submission_file_id'])
check('corrected file bytes match submitted DOCX', corrected['sha256'] == hashlib.sha256((folder / 'apa.docx').read_bytes()).hexdigest())
save_metadata(True)
check('author replaces uppercase metadata title', inspect()['title']['pt_BR'] == 'Paisagem e ensino: estudo sintético')
check('OJS bibliography preserves Enter between references', '\n' in inspect()['citations_raw'] and inspect()['citations_raw'].startswith('Silva, A.'))
r = b.post(stale['action'], {**stale['data'], 'editorialReviewed': '1'})
check('old checklist form cannot approve corrected analysis', inspect()['ledgers']['editorial'] == old_editorial)
page = b.draft()
revised_draft = text_area(page, 'estrabaoDeskBody')
check('correction removes proven ABNT from new desk', 'ABNT' not in revised_draft)
check('new desk removes now-filled English Spanish metadata failures', 'Metadados em inglês:' not in revised_draft and 'Metadados em espanhol:' not in revised_draft)
check('correction invalidates old non-APA checklist', 'id="estrabaoEditorialStale"' in page['body'])
check('old checklist history remains present', inspect()['ledgers']['editorial'] == old_editorial)
check('corrected references become explicitly reviewable', 'value="approve"' in b.view()['body'])
check('correction does not auto-release', 'Há liberação editorial válida' not in b.view()['body'])
finish(a, 'review')
check('corrected article waits for explicit human APA review', inspect()['progress'] == 4)
f = b.form(page, 'estrabaoEditorialForm')
data = {**f['data'], 'editorialReviewed': '1', 'editorialStates[metadata]': 'reviewed'}
r = b.post(f['action'], data)
check('editor records current corrected metadata review', 'Conferência editorial registrada.' in r['body'])
check('checklist review alone does not release APA', 'Há liberação editorial válida' not in b.view()['body'])
check('author cannot view private desk or checklist', 'estrabaoEditorialForm' not in a.view()['body'] and 'estrabaoApaReviewForm' not in a.view()['body'])
r = b.approve()
check('explicit human APA decision enables confirmation', 'Há liberação editorial válida' in r['body'])
finish(a, 'review', False)
check('human release preserves native CSRF requirement', inspect()['progress'] == 4)
f = b.form(b.view(), 'estrabaoApaReviewForm')
b.post(f['action'], {**f['data'], 'reviewAction': 'revoke'})
finish(a, 'review')
check('revoked human release prevents completion', inspect()['progress'] == 4)
b.approve()
before = inspect()
finish(a, 'review')
after = inspect()
check('reviewed corrected article concludes native wizard', after['progress'] == 0 and after['date_submitted'] is not None)
check('conclusion is submission receipt not publication', after['date_published'] is None and after['status'] == 1)
check('review and completion preserve corrected file bytes', after['files'] == before['files'])
check('APA and non-APA histories survive native conclusion', after['ledgers'] == before['ledgers'])
check('other synthetic submissions remain unchanged', after['baseline_submissions'] == setup['baseline_submissions'])
(folder / 'summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results), 'native_author_steps': [1, 2, 3, 4], 'native_multipart_upload_and_revision': True, 'direct_database_draft_or_file_insertion': False, 'full_apa_certification': False, 'published': False, 'production_accessed': False, 'oracle_vm_accessed': False, 'external_desk_sent': False}, indent=2))
