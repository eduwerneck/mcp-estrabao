<?php
declare(strict_types=1);

function italicDocument(array $references, array $fragments = [], array $citations = ['Texto (Silva, 2020).']): array
{
    $doc = referenceDocument($citations, $references); $first = count($doc['paragraphs']) - count($references);
    foreach ($references as $i => $text) {
        $ranges = []; $bounds = [0, strlen($text)];
        foreach ($fragments[$i] ?? [] as $fragment) {
            $start = strpos($text, $fragment); if ($start === false) { throw new RuntimeException('Fragmento de teste ausente.'); }
            $ranges[] = [$start, $start + strlen($fragment)]; $bounds[] = $start; $bounds[] = $start + strlen($fragment);
        }
        sort($bounds); $bounds = array_values(array_unique($bounds)); $spans = [];
        for ($j = 1; $j < count($bounds); $j++) {
            $italic = false;
            foreach ($ranges as $range) { if ($bounds[$j - 1] >= $range[0] && $bounds[$j] <= $range[1]) { $italic = true; } }
            $spans[] = ['start' => $bounds[$j - 1], 'end' => $bounds[$j], 'italic' => $italic];
        }
        $doc['paragraphs'][$first + $i]['reference_formatting'] = ['available' => true, 'italic_spans' => $spans];
    }
    return $doc;
}
function apaResults(array $document, string $language = 'pt'): array
{
    return array_column(referenceChecks($document, $language), null, 'rule_id');
}
function apaAudit(array $document, string $language = 'pt'): array { return apaResults($document, $language)['references.apa7']['evidence']['style_audit']; }
function auditCodes(array $audit): array
{
    $codes = [];
    foreach ($audit['samples'] as $sample) { foreach ($sample['checks'] as $check) { $codes[$check['check']] = $check['status']; } }
    return $codes;
}
$book = 'Silva, A. (2020). Estudo da paisagem. Editora Exemplo.';
$article = 'Silva, A. (2020). Estudo da paisagem. Revista Sintética, 8(2), 10–20.';
$chapter = 'Silva, A. (2020). Estudo da paisagem. In B. Editor (Ed.), Livro sintético (pp. 10–20). Editora Exemplo.';
foreach ([
    'book' => 'SILVA, João. Estudo da paisagem. São Paulo: Editora Exemplo, 2020.',
    'mixed case surname' => 'Silva, João. Estudo da paisagem. São Paulo: Editora Exemplo, 2020.',
    'initials' => 'Silva, A. Estudo da paisagem. São Paulo: Editora Exemplo, 2020.',
    'journal' => 'SILVA, João. Estudo da paisagem. Revista Exemplo, v. 8, n. 2, p. 10–20, 2020.',
    'web' => 'SILVA, João. Estudo da paisagem. Portal Sintético. 2020. Disponível em: https://invalid.example/teste. Acesso em: 12 nov. 2026.',
    'book with DOI' => 'SILVA, João. Estudo da paisagem. São Paulo: Editora Exemplo, 2020. DOI:10.1234/synthetic',
] as $kind => $ref) {
    test('incompatibilidade composta semelhante a ABNT ' . $kind, function () use ($ref) {
        $results = apaResults(referenceDocument(['Texto (Silva, 2020).'], [$ref]));
        same('não conforme', $results['references.apa7']['status']);
        same(1, $results['references.apa7']['evidence']['style_audit']['abnt_like_entries']);
        same('não conforme', $results['references.apa7.recognized_formats']['status']);
        same('indeterminado', $results['references.citation_matching']['status']);
    });
}
test('sobrenome em caixa alta sozinho não determina ABNT nem reprovação APA', function () use ($book) {
    $doc = italicDocument([str_replace('Silva', 'SILVA', $book)], [['Estudo da paisagem']]);
    $results = apaResults($doc);
    same(0, $results['references.apa7']['evidence']['style_audit']['abnt_like_entries']);
    same('indeterminado', $results['references.apa7']['status']);
    same('indeterminado', auditCodes(apaAudit($doc))['personal_author_case']);
});
test('citação parentética com caixa alta isolada preserva incerteza', function () use ($book) {
    $doc = italicDocument([$book], [['Estudo da paisagem']], ['Texto (SILVA, 2020).']);
    $results = apaResults($doc); same('conforme', $results['references.citation_matching']['status']);
    same('indeterminado', $results['references.apa7.citation_format']['status']);
    same('indeterminado', $results['references.apa7']['status']);
});
foreach (['Título: história de 2020', 'Como estudar?', 'Um estudo com DOI no título'] as $title) {
    test('título com número, dois pontos ou interrogação não vira ABNT ' . $title, function () use ($title) {
        $ref = 'Silva, A. (2020). ' . $title . (substr($title, -1) === '?' ? ' ' : '. ') . 'Editora Exemplo.';
        $doc = italicDocument([$ref], [[$title]]); $audit = apaAudit($doc);
        same(0, $audit['abnt_like_entries']); same('conforme', $audit['recognized_formats_status']);
    });
}
test('rótulo jurídico não é submetido cegamente ao modelo de livro ABNT', function () {
    $ref = 'BRASIL, Constituição. Constituição Federal. Brasília: Editora, 1988.';
    $result = apaResults(referenceDocument([], [$ref])); same('indeterminado', $result['references.apa7']['status']);
    same(0, $result['references.apa7']['evidence']['style_audit']['abnt_like_entries']);
});
test('obra autônoma com layout reconhecido não recebe certificado de APA completa', function () use ($book) {
    $results = apaResults(italicDocument([$book], [['Estudo da paisagem']]));
    same('conforme', $results['references.apa7.recognized_formats']['status']);
    same('conforme', $results['references.apa7.citation_format']['status']);
    same('indeterminado', $results['references.apa7']['status']);
    same(false, $results['references.apa7']['evidence']['full_apa_validation']);
});
test('livro ou periódico sem volume não é adivinhado por texto sem itálico', function () use ($book) {
    $results = apaResults(italicDocument([$book])); same('indeterminado', $results['references.apa7']['status']);
    same('indeterminado', auditCodes($results['references.apa7']['evidence']['style_audit'])['work_type_and_italic']);
});
foreach ([
    'correct' => [['Revista Sintética', '8'], 'conforme'],
    'journal plain' => [['8'], 'não conforme'],
    'volume plain' => [['Revista Sintética'], 'não conforme'],
    'article title italic' => [['Estudo da paisagem', 'Revista Sintética', '8'], 'não conforme'],
    'issue italic' => [['Revista Sintética', '8(2)'], 'não conforme'],
] as $name => $case) {
    test('tipografia de artigo de periódico ' . $name, function () use ($article, $case) {
        $results = apaResults(italicDocument([$article], [$case[0]]));
        same($case[1], $results['references.apa7.recognized_formats']['status']);
        same($case[1] === 'conforme' ? 'indeterminado' : 'não conforme', $results['references.apa7']['status']);
        same('journal_article', $results['references.apa7']['evidence']['style_audit']['samples'][0]['type']);
    });
}
test('prefixo de páginas em artigo com volume reconhecido é incompatibilidade concreta', function () use ($article) {
    $ref = str_replace('10–20', 'pp. 10–20', $article);
    $audit = apaAudit(italicDocument([$ref], [['Revista Sintética', '8']]));
    same('não conforme', $audit['recognized_formats_status']); same('não conforme', auditCodes($audit)['journal_page_prefix']);
});
test('artigo com título interrogativo mantém tipo reconhecido', function () use ($article) {
    $ref = str_replace('Estudo da paisagem.', 'Como estudar?', $article);
    same('conforme', apaAudit(italicDocument([$ref], [['Revista Sintética', '8']]))['recognized_formats_status']);
});
foreach ([
    'correct' => [['Livro sintético'], 'conforme'],
    'book plain' => [[], 'não conforme'],
    'chapter italic' => [['Estudo da paisagem', 'Livro sintético'], 'não conforme'],
    'publisher italic' => [['Livro sintético', 'Editora Exemplo'], 'não conforme'],
] as $name => $case) {
    test('tipografia de capítulo ' . $name, function () use ($chapter, $case) {
        $audit = apaAudit(italicDocument([$chapter], [$case[0]]));
        same($case[1], $audit['recognized_formats_status']); same('chapter', $audit['samples'][0]['type']);
    });
}
test('editora singular e plural em capítulo seguem editoria reconhecida', function () use ($chapter) {
    $wrong = str_replace('(Ed.)', '(Eds.)', $chapter);
    $audit = apaAudit(italicDocument([$wrong], [['Livro sintético']])); same('não conforme', auditCodes($audit)['chapter_editor_label']);
    $right = str_replace('B. Editor (Ed.)', 'B. Editor & C. Outro (Eds.)', $chapter);
    same('conforme', apaAudit(italicDocument([$right], [['Livro sintético']]))['recognized_formats_status']);
});
test('layout de página com data completa é reconhecido sem validar existência da página', function () {
    $ref = 'Silva, A. (2020, May 2). Estudo da paisagem. Portal Sintético. https://invalid.example/teste';
    $audit = apaAudit(italicDocument([$ref], [['Estudo da paisagem']]));
    same('conforme', $audit['recognized_formats_status']); same('webpage_layout_candidate', $audit['samples'][0]['type']);
});
foreach (['n.d.', 's.d.', 'in press', 'no prelo'] as $date) {
    test('data especial não vira ABNT automaticamente ' . $date, function () use ($book, $date) {
        $ref = str_replace('2020', $date, $book); $audit = apaAudit(italicDocument([$ref], [['Estudo da paisagem']]));
        same(0, $audit['abnt_like_entries']); same('conforme', $audit['recognized_formats_status']);
    });
}
test('instituição em maiúsculas não é tratada como sobrenome pessoal ABNT', function () {
    $ref = 'OMS. (2020). Estudo da paisagem. Editora Exemplo.';
    $audit = apaAudit(italicDocument([$ref], [['Estudo da paisagem']]));
    same(0, $audit['abnt_like_entries']); same('indeterminado', $audit['recognized_formats_status']);
    same('indeterminado', auditCodes($audit)['group_author_role']);
});
foreach ([
    'proper parenthetical' => ['Texto (Silva & Souza, 2020).', 'conforme'],
    'text conjunction in parentheses' => ['Texto (Silva e Souza, 2020).', 'não conforme'],
    'narrative with conjunction' => ['Silva e Souza (2020) apresentam resultados.', 'conforme'],
    'narrative with ampersand' => ['Silva & Souza (2020) apresentam resultados.', 'não conforme'],
    'missing comma space' => ['Texto (Silva & Souza,2020).', 'não conforme'],
    'missing narrative space' => ['Silva e Souza(2020) apresentam resultados.', 'não conforme'],
] as $name => $case) {
    test('formato de citação separado de correspondência ' . $name, function () use ($case) {
        $ref = 'Silva, A., & Souza, B. (2020). Estudo da paisagem. Editora Exemplo.';
        $results = apaResults(italicDocument([$ref], [['Estudo da paisagem']], [$case[0]]));
        same('conforme', $results['references.citation_matching']['status']); same($case[1], $results['references.apa7.citation_format']['status']);
        same($case[1] === 'conforme' ? 'indeterminado' : 'não conforme', $results['references.apa7']['status']);
    });
}
test('citação sem referência pessoal reconhecida não recebe acusação de conjunção errada', function () {
    $doc = referenceDocument(['Texto (Instituição e Outra, 2020).'], ['Instituição e Outra. (2020). Relatório sintético.']);
    $results = apaResults($doc); same('indeterminado', $results['references.apa7.citation_format']['status']);
    same('indeterminado', $results['references.apa7']['status']);
});
foreach ([
    'https://doi.org/10.1234/synthetic' => 'conforme',
    'doi:10.1234/synthetic' => 'não conforme',
    'http://doi.org/10.1234/synthetic' => 'não conforme',
    'https://dx.doi.org/10.1234/synthetic' => 'não conforme',
] as $doi => $status) {
    test('formato de DOI informado ' . $doi, function () use ($doi, $status, $book) {
        $audit = apaAudit(italicDocument([$book . ' ' . $doi], [['Estudo da paisagem']]));
        same($status, auditCodes($audit)['doi_url_format']);
        same(false, strpos(json_encode($audit), '10.1234/synthetic') !== false);
    });
}
test('DOI ausente não reprova e ponto potencialmente integrante do identificador é incerto', function () use ($book) {
    same('conforme', apaAudit(italicDocument([$book], [['Estudo da paisagem']]))['recognized_formats_status']);
    $audit = apaAudit(italicDocument([$book . ' https://doi.org/10.1234/synthetic.'], [['Estudo da paisagem']]));
    same('indeterminado', auditCodes($audit)['identifier_punctuation']);
});
test('lista numerada com cabeça autor-data reconhecível é incompatível', function () use ($book) {
    $results = apaResults(referenceDocument(['Texto (Silva, 2020).'], ['[1] ' . $book]));
    same('não conforme', $results['references.apa7']['status']);
    same('não conforme', auditCodes($results['references.apa7']['evidence']['style_audit'])['numbered_reference_list']);
});
test('ordem alfabética não é ignorada quando há sobrenomes ASCII distintos', function () use ($book) {
    $first = str_replace('Silva', 'Zebra', $book); $second = str_replace('Silva', 'Alves', $book);
    $doc = italicDocument([$first, $second], [['Estudo da paisagem'], ['Estudo da paisagem']]);
    $audit = apaAudit($doc); same('não conforme', $audit['alphabetical_order_status']); same('não conforme', $audit['recognized_formats_status']);
    same([[6, 7]], $audit['order_samples']);
});
test('colação acentuada e autores iguais não recebem ordenação adivinhada', function () use ($book) {
    $doc = italicDocument([str_replace('Silva', 'Sá', $book), $book], [['Estudo da paisagem'], ['Estudo da paisagem']]);
    same('indeterminado', apaAudit($doc)['alphabetical_order_status']);
    same('indeterminado', apaAudit(italicDocument([$book, str_replace('2020', '2021', $book)], [['Estudo da paisagem'], ['Estudo da paisagem']]))['alphabetical_order_status']);
});
test('título bibliográfico em caixa alta integral recebe diagnóstico sem reescrita', function () use ($book) {
    $ref = str_replace('Estudo da paisagem', 'ESTUDO DA PAISAGEM', $book);
    $audit = apaAudit(italicDocument([$ref], [['ESTUDO DA PAISAGEM']]));
    same('não conforme', auditCodes($audit)['work_title_full_uppercase']);
    same(false, strpos(json_encode($audit), 'ESTUDO DA PAISAGEM') !== false);
});
foreach (['en' => 'References', 'es' => 'Referencias'] as $language => $heading) {
    test('auditoria da bibliografia também em ' . $language, function () use ($language, $heading) {
        $doc = countDocument([$heading, 'SILVA, João. Estudo da paisagem. São Paulo: Editora Exemplo, 2020.']);
        $results = apaResults($doc, $language); same('não conforme', $results['references.apa7']['status']);
        same('indeterminado', $results['references.citation_matching']['status']);
    });
}
function richReferenceBody(string $reference, array $fragments): string
{
    $doc = italicDocument([$reference], [$fragments]); $paragraph = $doc['paragraphs'][count($doc['paragraphs']) - 1]; $runs = '';
    foreach ($paragraph['reference_formatting']['italic_spans'] as $span) {
        $text = substr($reference, $span['start'], $span['end'] - $span['start']);
        $runs .= '<w:r><w:rPr><w:i w:val="' . ($span['italic'] ? '1' : '0') . '"/></w:rPr><w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1, 'UTF-8') . '</w:t></w:r>';
    }
    return '<w:p>' . $runs . '</w:p>';
}
function apaWordParts(string $reference, array $fragments): array
{
    return docxParts(docxBody(['Introdução', 'Texto (Silva, 2020).', 'Metodologia', 'Resultados e Discussão', 'Considerações Finais', 'Referências']) . richReferenceBody($reference, $fragments));
}
test('Word real combina runs e itálico sem perder alinhamento Unicode', function () use ($article) {
    withDocx(apaWordParts($article, ['Revista Sintética', '8']), function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same(true, $doc['available']); same(true, $doc['paragraphs'][6]['reference_formatting']['available']);
        $results = apaResults($doc); same('conforme', $results['references.apa7.recognized_formats']['status']);
    });
});
foreach (['direct' => '', 'character_style' => '<w:rStyle w:val="Italic"/>'] as $mode => $property) {
    test('itálico direto e por estilo de caractere ' . $mode, function () use ($mode, $property, $article) {
        $parts = apaWordParts($article, ['Revista Sintética', '8']);
        if ($mode === 'character_style') {
            $parts['word/styles.xml'] = '<w:styles xmlns:w="' . \Estrabao\Document\DocxReader::W . '">' . style('Italic', '<w:rPr><w:i/></w:rPr>', null, 'character') . '</w:styles>';
            $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="style" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
            $parts['word/document.xml'] = str_replace('<w:i w:val="1"/>', $property, $parts['word/document.xml']);
        }
        withDocx($parts, function ($path) { same('conforme', apaResults((new \Estrabao\Document\DocxReader())->read($path))['references.apa7.recognized_formats']['status']); });
    });
}
test('formatação direta desligada supera itálico herdado no periódico', function () use ($article) {
    $parts = apaWordParts($article, ['Revista Sintética', '8']);
    $parts['word/styles.xml'] = '<w:styles xmlns:w="' . \Estrabao\Document\DocxReader::W . '">' . style('Base', '<w:rPr><w:i/></w:rPr>', null, 'paragraph', true) . '</w:styles>';
    $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="style" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $parts['word/document.xml'] = str_replace('w:i w:val="1"', 'w:i w:val="0"', $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $audit = apaAudit((new \Estrabao\Document\DocxReader())->read($path));
        same('não conforme', auditCodes($audit)['journal_italic']);
    });
});
test('nota vinculada libera apenas o corpo para referências, sem promover as outras regras', function () use ($article) {
    $parts = footnoteParts(1); $body = apaWordParts($article, ['Revista Sintética', '8'])['word/document.xml'];
    $parts['word/document.xml'] = str_replace('<w:t>Texto (Silva, 2020).</w:t>', '<w:t>Texto (Silva, 2020).</w:t><w:footnoteReference w:id="1"/>', $body);
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same(false, $doc['available']); same([], $doc['paragraphs']); same(true, $doc['reference_body']['available']);
        $results = apaResults($doc); same('conforme', $results['references.citation_matching']['status']);
        same('conforme', $results['references.apa7.recognized_formats']['status']);
        same('linked_footnotes_main_body', $results['references.apa7']['evidence']['source_profile']);
        same('indeterminado', manuscriptLength($doc)['status']); same('indeterminado', structure($doc)[0]['status']);
    });
});
test('tabela anterior não oculta ABNT na cauda bibliográfica íntegra', function () {
    $ref = 'SILVA, João. Estudo da paisagem. São Paulo: Editora Exemplo, 2020.';
    $parts = docxParts(simpleVisualTable() . docxBody(['Referências', $ref]));
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path); same(false, $doc['available']);
        $results = apaResults($doc); same('não conforme', $results['references.apa7']['status']);
        same('verified_reference_tail', $results['references.apa7']['evidence']['source_profile']);
        same('indeterminado', $results['references.citation_matching']['status']);
    });
});
test('itálico no trecho final pode ser verificado sem fingir leitura completa do corpo', function () use ($article) {
    $parts = apaWordParts($article, ['Revista Sintética', '8']);
    $parts['word/document.xml'] = str_replace('<w:body>', '<w:body>' . simpleVisualTable(), $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $results = apaResults((new \Estrabao\Document\DocxReader())->read($path));
        same('conforme', $results['references.apa7.recognized_formats']['status']);
        same('indeterminado', $results['references.citation_matching']['status']);
        same(false, $results['references.apa7']['evidence']['full_document_text_available']);
    });
});
foreach (['table' => '<w:tbl/>', 'field' => '<w:p><w:r><w:fldChar/></w:r></w:p>', 'revision' => '<w:ins/>', 'control' => '<w:sdt/>'] as $kind => $node) {
    test('trecho bibliográfico incompleto não vira fonte confiável ' . $kind, function () use ($node) {
        $parts = docxParts(simpleVisualTable() . docxBody(['Referências', 'SILVA, João. Livro. São Paulo: Editora, 2020.']) . $node);
        withDocx($parts, function ($path) {
            $doc = (new \Estrabao\Document\DocxReader())->read($path); same(false, isset($doc['reference_tail']));
            same('indeterminado', apaResults($doc)['references.apa7']['status']);
        });
    });
}
test('vínculo de nota quebrado não autoriza perfil alternativo', function () {
    $parts = footnoteParts(1); unset($parts['word/footnotes.xml']);
    $parts['word/document.xml'] = str_replace('</w:body>', docxBody(['Referências', 'SILVA, João. Livro. São Paulo: Editora, 2020.']) . '</w:body>', $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path); same(false, isset($doc['reference_body'])); same(false, isset($doc['reference_tail']));
        same('indeterminado', apaResults($doc)['references.apa7']['status']);
    });
});
test('mensagens são acionáveis e não expõem o texto da referência', function () {
    $ref = 'PRIVATEAUTHOR, Nome. TÍTULO PRIVADO. Cidade: EDITORA PRIVADA, 2020.';
    $results = apaResults(referenceDocument(['Texto sem citação.'], [$ref]));
    $sample = $results['references.apa7']['evidence']['style_audit']['samples'][0];
    same('não conforme', $sample['status']); same(true, strlen($sample['checks'][0]['message']) > 20);
    $encoded = json_encode($results); same(false, strpos($encoded, 'PRIVATEAUTHOR') !== false); same(false, strpos($encoded, 'EDITORA PRIVADA') !== false);
});
test('CLI distingue incompatibilidade detectada de certificação APA completa', function () {
    $parts = docxParts(docxBody(['Referências', 'SILVA, João. Livro. São Paulo: Editora, 2020.']));
    withDocx($parts, function ($path) {
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/with-subtitles.json', $path, 'article', 'pt']);
        same(0, $code); same('', $err); $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
        same('0.11.0', $report['engine_version']); $checks = array_column($report['checks'], null, 'rule_id');
        same('3.0.0', $checks['references.apa7']['rule_version']); same('não conforme', $checks['references.apa7']['status']);
        same(false, $checks['references.apa7']['evidence']['full_apa_validation']); same(false, strpos($out, 'São Paulo') !== false);
    });
});
test('mês desconhecido ou dia inválido não recebe aprovação de formato de data', function () {
    foreach (['2020, Foo 2', '2020, February 30'] as $date) {
        $ref = 'Silva, A. (' . $date . '). Estudo da paisagem. Portal Sintético. https://invalid.example/teste';
        $audit = apaAudit(italicDocument([$ref], [['Estudo da paisagem']]));
        same('indeterminado', auditCodes($audit)['date_token_format']);
        same('indeterminado', $audit['recognized_formats_status']);
    }
});
test('cabeçalho Bibliografia permite detectar a divergência sem aceitar título editorial novo', function () {
    $doc = countDocument(['Bibliografia', 'SILVA, João. Livro. São Paulo: Editora, 2020.']);
    $results = apaResults($doc); same('não conforme', $results['references.apa7']['status']);
    same(true, $results['references.apa7']['evidence']['style_audit']['heading_review_required']);
});
test('cabeçalhos alternativos ou duplicados não produzem aprovação indevida', function () use ($book) {
    $doc = italicDocument([$book], [['Estudo da paisagem']]); $doc['paragraphs'][5]['text'] = 'Bibliografia';
    same('indeterminado', apaAudit($doc)['recognized_formats_status']);
    array_unshift($doc['paragraphs'], ['index'=>-1,'text'=>'Referências','outline'=>['known'=>true,'level'=>9],'format'=>['bold'=>false]]);
    same(false, apaAudit($doc)['coverage_complete']);
});
test('dados privados em pacotes recusados não chegam ao avaliador APA', function () {
    $parts = docxParts(docxBody(['Referências','SILVA, João. Livro. São Paulo: Editora, 2020.']));
    $parts['private.xml'] = '<!DOCTYPE x [<!ENTITY y SYSTEM "https://invalid.example/never-fetch">]><x>&y;</x>';
    withDocx($parts, function ($path) { same('indeterminado', apaResults((new \Estrabao\Document\DocxReader())->read($path))['references.apa7']['status']); });
});
test('limite de referências também protege a auditoria de estilo', function () use ($book) {
    $doc = referenceDocument([], array_fill(0, \Estrabao\Document\ReferenceAnalysis::MAX_ENTRIES + 1, $book));
    $audit = apaAudit($doc); same(true, $audit['inspection_limit_reached']); same('indeterminado', $audit['recognized_formats_status']);
});
test('ocorrência ABNT tardia permanece em amostra limitada', function () use ($book) {
    $refs = array_fill(0, 40, $book); $refs[] = 'SILVA, João. Livro. São Paulo: Editora, 2020.';
    $italics = array_fill(0, 40, ['Estudo da paisagem']); $italics[] = [];
    $doc = italicDocument($refs, $italics); $audit = apaAudit($doc);
    same('não conforme', $audit['recognized_formats_status']); same(32, count($audit['samples']));
    same(9, $audit['samples_omitted']); same(true, in_array(40, array_column($audit['samples'], 'reference_index'), true));
});
