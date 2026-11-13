<?php
declare(strict_types=1);
use Estrabao\Rules\References;

function referenceDocument(array $citations = ['Texto (Silva, 2020).'], array $references = ['Silva, A. (2020). Título sintético. Editora sintética.']): array
{
    return countDocument(array_merge(['Introdução'], $citations, ['Metodologia', 'Resultados e Discussão', 'Considerações Finais', 'Referências'], $references));
}
function referenceChecks(?array $document, ?string $language = 'pt'): array
{
    return array_map(function ($r) { return $r->jsonSerialize(); }, (new References($document, $language))->evaluate(new \Estrabao\Contracts\Submission(fixture())));
}
function matchingStatus(array $citations, array $references): string
{
    return referenceChecks(referenceDocument($citations, $references))[1]['status'];
}
$single = 'Silva, A. (2020). Livro sintético. Editora.';
$two = 'Silva, A., & Souza, B. (2020). Estudo sintético. Revista.';
$three = 'Silva, A., Souza, B., & Lima, C. (2020). Estudo sintético. Revista.';
foreach ([
    'single parenthetical' => [['Texto (Silva, 2020).'], [$single]],
    'narrative at paragraph start' => [['Silva (2020) apresenta resultados.'], [$single]],
    'narrative with introducer' => [['Segundo Silva (2020), houve resultado.'], [$single]],
    'two authors parenthetical' => [['Texto (Silva & Souza, 2020).'], [$two]],
    'two authors narrative' => [['Silva e Souza (2020) apresentam resultados.'], [$two]],
    'three authors et al' => [['Texto (Silva et al., 2020).'], [$three]],
    'narrative et al' => [['Silva et al. (2020) apresentam resultados.'], [$three]],
    'locator' => [['Texto (Silva, 2020, pp. 12–14).'], [$single]],
    'multiple citations' => [['Texto (Silva, 2020; Souza, 2021).'], [$single, 'Souza, B. (2021). Outra obra. Editora.']],
    'year suffix' => [['Texto (Silva, 2020b).'], [str_replace('2020', '2020b', $single)]],
    'Unicode case insensitive' => [['Texto (sÁ, 2020).'], ['Sá, A. (2020). Livro. Editora.']],
    'hyphenated surname' => [['Texto (García-López, 2020).'], ['García-López, A. (2020). Livro. Editora.']],
    'apostrophe surname' => [["Texto (O'Neil, 2020)."], ["O'Neil, A. (2020). Livro. Editora."]],
] as $name => $case) {
    test('correspondência reconhecida ' . $name, function () use ($case) {
        $checks = referenceChecks(referenceDocument($case[0], $case[1]));
        same('conforme', $checks[1]['status']); same('indeterminado', $checks[0]['status']);
        same(false, $checks[0]['evidence']['full_apa_validation']);
        same(false, $checks[0]['evidence']['external_lookup_performed']);
    });
}
foreach ([
    'missing author' => [['Texto (Souza, 2020).'], [$single]],
    'wrong year' => [['Texto (Silva, 2021).'], [$single]],
    'wrong suffix' => [['Texto (Silva, 2020a).'], [str_replace('2020', '2020b', $single)]],
] as $name => $case) {
    test('chave literal sem referência no subconjunto fechado ' . $name, function () use ($case) {
        $check = referenceChecks(referenceDocument($case[0], $case[1]))[1];
        same('não conforme', $check['status']); same(true, $check['evidence']['analysis']['closed_subset']);
        same(1, $check['evidence']['analysis']['unresolved_citations']);
    });
}
foreach ([
    'different second author' => [['Texto (Silva & Santos, 2020).'], [$two]],
    'single vs et al' => [['Texto (Silva, 2020).'], [$three]],
    'et al with two authors' => [['Texto (Silva et al., 2020).'], [$two]],
    'same first author et al ambiguity' => [['Texto (Silva et al., 2020).'], [$three, 'Silva, A., Reis, B., & Santos, C. (2020). Outra obra. Editora.']],
    'duplicate reference key' => [['Texto (Silva, 2020).'], [$single, $single]],
] as $name => $case) {
    test('autoria ambígua não vira correspondência exata ' . $name, function () use ($case) {
        $result = referenceChecks(referenceDocument($case[0], $case[1]))[1];
        same('indeterminado', $result['status']); same(true, $result['evidence']['analysis']['ambiguous_matches'] > 0);
    });
}
foreach ([
    'institutional author' => 'Organização Mundial da Saúde. (2020). Relatório sintético.',
    'compound surname' => 'da Silva, A. (2020). Livro sintético.',
    'no date' => 'Silva, A. (n.d.). Livro sintético.',
    'full publication date' => 'Silva, A. (2020, May 2). Notícia sintética.',
    'missing title' => 'Silva, A. (2020). ',
    'two entries in paragraph' => $single . ' Souza, B. (2021). Outro livro. Editora.',
    'wrapped reference continuation' => 'Revista Sintética, 1(2), 1–10.',
    'numbered reference' => '[1] ' . $single,
    'legacy initials' => 'Silva, Alberto. (2020). Livro. Editora.',
] as $name => $reference) {
    test('referência não reconhecida impede falsa ausência ' . $name, function () use ($reference) {
        $check = referenceChecks(referenceDocument(['Texto (Ausente, 2020).'], [$reference]))[1];
        same('indeterminado', $check['status']); same(1, $check['evidence']['analysis']['unparsed_references']);
    });
}
foreach ([
    'personal communication' => 'Texto (Silva, comunicação pessoal, 2020).',
    'secondary citation' => 'Texto (Silva, 2019, como citado em Souza, 2020).',
    'multiple years' => 'Texto (Silva, 2020, 2021).',
    'undated citation' => 'Texto (Silva, n.d.).',
    'institutional citation' => 'Texto (World Health Organization, 2020).',
    'embedded narrative' => 'O estudo apresentado por Silva (2020) foi relevante.',
    'nested parentheses' => 'Texto (ver (Silva, 2020)).',
    'unbalanced parentheses' => 'Texto (Silva, 2020.',
    'plain date' => 'Em (2020) houve uma mudança.',
] as $name => $citation) {
    test('citação ambígua exige revisão ' . $name, function () use ($citation, $single) {
        $check = referenceChecks(referenceDocument(['Texto (Silva, 2020).', $citation], [$single]))[1];
        same('indeterminado', $check['status']); same(true, $check['evidence']['analysis']['unparsed_citation_groups'] > 0);
    });
}
test('marcador numérico é candidato sem reprovação APA automática', function () use ($single) {
    $checks = referenceChecks(referenceDocument(['Texto (Silva, 2020). Outro [1].'], [$single]));
    same('indeterminado', $checks[0]['status']); same('indeterminado', $checks[1]['status']);
    same(1, $checks[0]['evidence']['analysis']['numeric_citation_candidates']);
});
test('DOI ausente não é obrigatório nem gera falha', function () use ($single) {
    $check = referenceChecks(referenceDocument(['Texto (Silva, 2020).'], [$single]));
    same('indeterminado', $check[0]['status']); same('conforme', $check[1]['status']);
    same(1, $check[0]['evidence']['analysis']['doi_signals']['not_detected']);
});
foreach (['https://doi.org/10.1234/synthetic' => 'url_candidates', 'doi:10.1234/synthetic' => 'legacy_candidates', 'http://dx.doi.org/10.1234/synthetic' => 'legacy_candidates'] as $doi => $kind) {
    test('sinal DOI não é validação externa ' . $kind . $doi, function () use ($doi, $kind, $single) {
        $checks = referenceChecks(referenceDocument(['Texto (Silva, 2020).'], [$single . ' ' . $doi]));
        same($kind === 'legacy_candidates' ? 'não conforme' : 'indeterminado', $checks[0]['status']); same(1, $checks[0]['evidence']['analysis']['doi_signals'][$kind]);
        same('conforme', $checks[1]['status']); same(false, strpos(json_encode($checks), $doi) !== false);
    });
}
test('referência não observada é informação, não reprovação', function () use ($single) {
    $check = referenceChecks(referenceDocument(['Texto (Silva, 2020).'], [$single, 'Souza, B. (2021). Livro sintético. Editora.']))[1];
    same('conforme', $check['status']); same(1, $check['evidence']['analysis']['references_not_observed_in_supported_citations']);
});
test('ausência de citações reconhecidas não é aprovação vazia', function () use ($single) {
    same('indeterminado', matchingStatus(['Texto sem citações identificadas.'], [$single]));
    same('indeterminado', matchingStatus(['Texto (Silva, 2020).'], []));
});
foreach (['missing', 'duplicate', 'abstract_boundary', 'unknown_heading'] as $kind) {
    test('delimitação inconclusiva impede falha por ausência ' . $kind, function () use ($kind) {
        $doc = referenceDocument(['Texto (Ausente, 2020).']);
        if ($kind === 'missing') { $doc['paragraphs'][5]['text'] = 'Referências citadas'; }
        elseif ($kind === 'duplicate') { $doc['paragraphs'][] = countDocument(['Referências'])['paragraphs'][0]; }
        elseif ($kind === 'abstract_boundary') { array_unshift($doc['paragraphs'], countDocument(['Resumo'])['paragraphs'][0]); }
        else { $doc['paragraphs'][1]['outline']['level'] = 1; }
        $check = referenceChecks($doc)[1]; same('indeterminado', $check['status']); same(false, $check['evidence']['analysis']['segmentation_complete']);
    });
}
test('ordem de seções não é imposta', function () use ($single) {
    $doc = countDocument(['Referências', $single, 'Metodologia', 'Texto (Silva, 2020).', 'Introdução', 'Resultados e Discussão', 'Considerações Finais']);
    same('conforme', referenceChecks($doc)[1]['status']);
});
foreach (['en', 'es', null] as $language) {
    test('idioma de delimitação não suportado ' . ($language ?? 'null'), function () use ($language) {
        $checks = referenceChecks(referenceDocument(), $language);
        foreach ($checks as $check) { same('indeterminado', $check['status']); same(false, isset($check['evidence']['analysis'])); }
    });
}
test('normalização ou fonte indisponível não gera falta de referência', function () {
    foreach ([null, ['available' => false], referenceDocument(["\xff"]), referenceDocument(["cafe\u{0301} (Silva, 2020)."])] as $document) {
        foreach (referenceChecks($document) as $check) { same('indeterminado', $check['status']); }
    }
});
test('limite de entradas não faz análise truncada conclusiva', function () use ($single) {
    $check = referenceChecks(referenceDocument(['Texto (Silva, 2020).'], array_fill(0, \Estrabao\Document\ReferenceAnalysis::MAX_ENTRIES + 1, $single)))[1];
    same('indeterminado', $check['status']); same(true, $check['evidence']['analysis']['inspection_limit_reached']);
});
test('limite de parágrafos é explícito', function () {
    $check = referenceChecks(countDocument(array_fill(0, \Estrabao\Document\ReferenceAnalysis::MAX_PARAGRAPHS + 1, 'Texto')))[1];
    same('indeterminado', $check['status']); same('inspection_limit', $check['evidence']['analysis_unavailable_reason']);
});
test('amostras preservam falha tardia sem dados bibliográficos privados', function () use ($single) {
    $citations = array_fill(0, 40, 'Texto (Silva, 2020).'); $citations[] = 'Texto (PRIVATEAUTHOR, 2020).';
    $check = referenceChecks(referenceDocument($citations, [$single]))[1];
    same('não conforme', $check['status']); same(32, count($check['evidence']['analysis']['citation_samples']));
    same(9, $check['evidence']['analysis']['citation_samples_omitted']);
    same(true, in_array('unresolved', array_column($check['evidence']['analysis']['citation_samples'], 'state'), true));
    $encoded = json_encode($check); same(false, strpos($encoded, 'PRIVATEAUTHOR') !== false); same(false, strpos($encoded, 'Silva') !== false);
});
foreach (['conforme' => 'Silva', 'não conforme' => 'Souza'] as $expected => $name) {
    test('CLI integra pré-checagem ' . $expected, function () use ($expected, $name, $single) {
        $doc = referenceDocument(['Texto (' . $name . ', 2020).'], [$single]);
        withDocx(docxParts(docxBody(array_column($doc['paragraphs'], 'text'))), function ($path) use ($expected) {
            list($code, $out, $err) = cli([__DIR__ . '/fixtures/with-subtitles.json', $path, 'article', 'pt']);
            same(0, $code); same('', $err);
            $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR); same('0.16.0', $report['engine_version']);
            $checks = array_column($report['checks'], null, 'rule_id');
            same($expected, $checks['references.citation_matching']['status']); same('3.0.0', $checks['references.citation_matching']['rule_version']);
            same($expected === 'não conforme' ? 'não conforme' : 'indeterminado', $checks['references.apa7']['status']);
            same(hash_file('sha256', $path), $checks['references.apa7']['evidence']['document_sha256']);
            same(false, strpos($out, 'Livro sintético') !== false); same(false, strpos($out, $path) !== false);
        });
    });
}
test('notas e tabelas não liberam referências por extração parcial', function () {
    foreach ([footnoteParts(1), docxParts(docxBody(['Referências']) . simpleVisualTable())] as $parts) {
        withDocx($parts, function ($path) {
            foreach (referenceChecks((new \Estrabao\Document\DocxReader())->read($path)) as $check) { same('indeterminado', $check['status']); }
        });
    }
});
test('limite de citações impede conclusão com análise truncada', function () use ($single) {
    $texts = array_fill(0, 129, str_repeat('Texto (Silva, 2020). ', 16));
    $check = referenceChecks(referenceDocument($texts, [$single]))[1];
    same('indeterminado', $check['status']); same(true, $check['evidence']['analysis']['inspection_limit_reached']);
});
test('parágrafo grande demais permanece indeterminado', function () use ($single) {
    $check = referenceChecks(referenceDocument([str_repeat('palavra ', 10000) . '(Ausente, 2020).'], [$single]))[1];
    same('indeterminado', $check['status']); same(true, $check['evidence']['analysis']['inspection_limit_reached']);
});
test('lista de mais de vinte autores fica fora da gramática fechada', function () {
    $authors = array_fill(0, 20, 'Silva, A.'); $authors[] = '& Souza, B.';
    $check = referenceChecks(referenceDocument(['Texto (Silva et al., 2020).'], [implode(', ', $authors) . ' (2020). Livro. Editora.']))[1];
    same('indeterminado', $check['status']); same(1, $check['evidence']['analysis']['unparsed_references']);
});
test('entrada não reconhecida tardia é mantida na amostra limitada', function () {
    $refs = [];
    for ($i = 0; $i < 40; $i++) { $refs[] = 'Silva, A. (' . (2020 + $i) . '). Livro sintético. Editora.'; }
    $refs[] = 'INSTITUIÇÃO PRIVADA. Relatório sem formato reconhecido.';
    $check = referenceChecks(referenceDocument(['Texto (Silva, 2020).'], $refs))[1];
    same('indeterminado', $check['status']);
    $samples = $check['evidence']['analysis']['reference_samples']; same(32, count($samples));
    same(9, $check['evidence']['analysis']['reference_samples_omitted']);
    same(true, in_array(40, array_column($samples, 'reference_index'), true));
    same(false, strpos(json_encode($check), 'INSTITUIÇÃO PRIVADA') !== false);
});
test('símbolo de uma letra com ano não comprova referência ausente', function () use ($single) {
    $check = referenceChecks(referenceDocument(['Texto (Silva, 2020). Coordenada (x, 2020).'], [$single]))[1];
    same('indeterminado', $check['status']); same(0, $check['evidence']['analysis']['unresolved_citations']);
});
