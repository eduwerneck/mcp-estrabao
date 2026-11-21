"""Synthetic DOCX inputs only. No uploaded/editorial documents are read."""
import zipfile, html
from pathlib import Path
from common import folder, docker, web
W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'

def p(text, bold=False, italic=False):
    props = ('<w:b/>' if bold else '') + ('<w:i/>' if italic else '')
    return '<w:p><w:r><w:rPr>' + props + '</w:rPr><w:t>' + html.escape(text) + '</w:t></w:r></w:p>'

def reference(name, year, title):
    return '<w:p><w:r><w:t>' + name + ' (' + year + '). </w:t></w:r><w:r><w:rPr><w:i/></w:rPr><w:t>' + title + '.</w:t></w:r><w:r><w:t xml:space="preserve"> Editora Exemplo.</w:t></w:r></w:p>'

def build(kind):
    body = p('TÍTULO SINTÉTICO EM CAIXA ALTA' if kind == 'abnt' else 'Paisagem e ensino: estudo sintético', True)
    for heading, text in [('Introdução', 'Texto sintético para testar o fluxo. Silva (2020) e Souza (2021) são referências sintéticas.'), ('Metodologia', 'Ensaio sintético de software, sem participantes ou dados reais.'), ('Resultados e Discussão', 'Este conteúdo não representa resultado de pesquisa real.'), ('Considerações Finais', 'A submissão sintética permite verificar as etapas do sistema.'), ('Créditos (CRediT)', 'Conceituação, metodologia e redação: autoria anonimizada.'), ('Financiamento', 'Esta pesquisa não recebeu financiamento.'), ('Ética', 'Estudo sintético sem coleta com seres humanos ou animais.'), ('Declaração de uso de inteligência artificial', 'Não foi utilizada inteligência artificial na elaboração deste manuscrito.')]:
        body += p(heading, True) + p(text)
    body += p('Referências', True)
    if kind == 'abnt':
        body += p('SILVA, Ana. Paisagem sintética. São Paulo: Editora Exemplo, 2020.') + p('SOUZA, Beatriz. Ensino sintético. São Paulo: Editora Exemplo, 2021.')
    else:
        body += reference('Silva, A.', '2020', 'Paisagem sintética') + reference('Souza, B.', '2021', 'Ensino sintético')
    target = folder / (kind + '.docx')
    with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as z:
        z.writestr('word/document.xml', '<w:document xmlns:w="' + W + '"><w:body>' + body + '</w:body></w:document>')
        z.writestr('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>')
        z.writestr('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>')
    target.chmod(384)
    return target

def prepare():
    docker('exec', web, 'mkdir', '-p', '/tmp/estrabao63-input')
    for kind in ['abnt', 'apa']:
        path = build(kind)
        docker('cp', str(path), web + ':/tmp/estrabao63-input/' + path.name)
        docker('exec', '--user', '0', web, 'chown', '100:101', '/tmp/estrabao63-input/' + path.name)
        docker('exec', '--user', '0', web, 'chmod', '600', '/tmp/estrabao63-input/' + path.name)
