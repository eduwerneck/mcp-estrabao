<?php
declare(strict_types=1);
use Estrabao\Document\ManuscriptSegments;
use Estrabao\Rules\ManuscriptLength;
use Estrabao\Text\WordInterval;

function countDocument(array $texts): array
{
    $paragraphs = [];
    foreach ($texts as $index => $text) {
        $paragraphs[] = ['index' => $index, 'text' => $text, 'format' => ['bold' => false], 'outline' => ['known' => true, 'level' => 9]];
    }
    return ['available' => true, 'sha256' => str_repeat('a', 64), 'paragraphs' => $paragraphs];
}
function countedBody(string $text, array $prefix = [], array $tail = []): array
{
    return countDocument(array_merge($prefix, ['Introdução', $text, 'Metodologia', 'Resultados e Discussão', 'Considerações Finais'], $tail, ['Referências', 'Autor sintético (2026). Obra sintética.']));
}
function manuscriptLength(array $doc, string $type = 'article', string $language = 'pt', ?array $snapshot = null): array
{
    return (new ManuscriptLength($doc, $type, $language))->evaluate(new \Estrabao\Contracts\Submission($snapshot ?? fixture()))[0]->jsonSerialize();
}
foreach (['article' => [3999 => 'não conforme', 4000 => 'conforme', 8000 => 'conforme', 8001 => 'não conforme'], 'research_communication' => [999 => 'não conforme', 1000 => 'conforme', 3500 => 'conforme', 3501 => 'não conforme']] as $type => $cases) {
    foreach ($cases as $words => $status) {
        test('extensão por tipo e fronteira ' . $type . '/' . $words, function () use ($type, $words, $status) {
            $check = manuscriptLength(countedBody(str_repeat('palavra ', $words)), $type);
            same($status, $check['status']); same($words, $check['evidence']['minimum_count']); same($words, $check['evidence']['maximum_count']);
            same('body_conservative_interval_v1', $check['evidence']['counting_method']);
        });
    }
}
foreach ([
    'palavra simples' => [2, 2],
    'socio-econômico' => [1, 2],
    "d'água" => [1, 2],
    '1,5 2026 SI' => [3, 4],
    "um\u{00A0}dois\ntrês" => [3, 3],
    '... — + !!!' => [0, 0],
] as $text => $counts) {
    test('convenções técnicas de palavra ' . $text, function () use ($text, $counts) { same($counts, WordInterval::counts($text)); });
}
foreach ([3998 => 'indeterminado', 7999 => 'indeterminado'] as $words => $status) {
    test('hífen cruza fronteira ' . $words, function () use ($words, $status) {
        same($status, manuscriptLength(countedBody(str_repeat('palavra ', $words) . 'socio-econômico'))['status']);
    });
}
test('títulos dos três idiomas, resumos, keywords e referências são excluídos', function () {
    $snapshot = fixture();
    $prefix = array_values($snapshot['title']);
    foreach ([['Resumo', 'Palavras-chave: um; dois; três'], ['Abstract', 'Keywords: one; two; three'], ['Resumen', 'Palabras clave: uno; dos; tres']] as $labels) {
        $prefix[] = $labels[0]; $prefix[] = str_repeat('resumo ', 400); $prefix[] = $labels[1];
    }
    $doc = countedBody(str_repeat('palavra ', 4000), $prefix);
    $doc['paragraphs'][count($doc['paragraphs']) - 1]['text'] = str_repeat('referência ', 9000);
    $result = manuscriptLength($doc);
    same('conforme', $result['status']); same(4000, $result['evidence']['maximum_count']);
    same(3, $result['evidence']['categories']['article_title']);
});
test('título HTML do snapshot corresponde só ao parágrafo inicial do DOCX', function () {
    $snapshot = fixture(); $snapshot['title']['pt_BR'] = '<p>Título &amp; texto</p>';
    $doc = countedBody(str_repeat('palavra ', 4000), ['Título & texto']);
    $result = manuscriptLength($doc, 'article', 'pt', $snapshot);
    same(4000, $result['evidence']['maximum_count']); same(1, $result['evidence']['categories']['article_title']);
});
test('título do snapshot repetido no corpo não remove prosa', function () {
    $snapshot = fixture(); $snapshot['title']['pt_BR'] = 'palavra';
    $result = manuscriptLength(countedBody(str_repeat('palavra ', 3999) . 'palavra'), 'article', 'pt', $snapshot);
    same(4000, $result['evidence']['minimum_count']);
});
test('prefixo sem título conhecido permanece possível, sem reprovação por template', function () {
    $result = manuscriptLength(countedBody(str_repeat('palavra ', 8000), ['Título não fornecido']));
    same('indeterminado', $result['status']); same(8000, $result['evidence']['minimum_count']); same(8003, $result['evidence']['maximum_count']);
});
test('palavras com nome de rótulo em prosa não são removidas', function () {
    $result = manuscriptLength(countedBody(str_repeat('palavra ', 3994) . 'A Introdução apresenta Metodologia e Referências.'));
    same(4000, $result['evidence']['minimum_count']); same('conforme', $result['status']);
});
test('seções em outra ordem e referências intermediárias não impõem ordenação', function () {
    $doc = countDocument(['Metodologia', str_repeat('palavra ', 2000), 'Referências', str_repeat('referência ', 9000), 'Introdução', str_repeat('palavra ', 2000), 'Considerações Finais', 'Resultados e Discussão']);
    $result = manuscriptLength($doc); same('conforme', $result['status']); same(4000, $result['evidence']['maximum_count']);
});
test('títulos conhecidos numerados são excluídos sem aprovar a formatação', function () {
    $doc = countedBody(str_repeat('palavra ', 4000));
    foreach ([0, 2, 3, 4, 5] as $index) { $doc['paragraphs'][$index]['text'] = '1. ' . $doc['paragraphs'][$index]['text']; }
    same('conforme', manuscriptLength($doc)['status']);
});
foreach (['Créditos (CRediT)', 'Fontes de Fomento'] as $section) {
    test('conteúdo condicional ou sem convenção de contagem ' . $section, function () use ($section) {
        $result = manuscriptLength(countedBody(str_repeat('palavra ', 8000), [], [$section, 'Uma contribuição sintética']));
        same('indeterminado', $result['status']); same(8000, $result['evidence']['minimum_count']); same(8003, $result['evidence']['maximum_count']);
    });
}
test('créditos não são usados para atingir mínimo', function () {
    $result = manuscriptLength(countedBody(str_repeat('palavra ', 3999), [], ['Créditos (CRediT)', 'contribuição']));
    same('indeterminado', $result['status']);
});
foreach (['bold', 'outline', 'unknown_style'] as $kind) {
    test('possível subtítulo não é prosa comprovada: ' . $kind, function () use ($kind) {
        $doc = countedBody(str_repeat('palavra ', 3999));
        $doc['paragraphs'][1]['text'] = str_repeat('palavra ', 4000);
        if ($kind === 'bold') { $doc['paragraphs'][1]['format']['bold'] = true; }
        elseif ($kind === 'outline') { $doc['paragraphs'][1]['outline']['level'] = 1; }
        else { $doc['paragraphs'][1]['outline']['known'] = false; }
        $result = manuscriptLength($doc); same('indeterminado', $result['status']); same(0, $result['evidence']['minimum_count']); same(4000, $result['evidence']['maximum_count']);
    });
}
test('subtítulo após referências reabre zona incerta', function () {
    $doc = countedBody(str_repeat('palavra ', 8000));
    $extra = countDocument(['Apêndice', 'Texto adicional sintético'])['paragraphs'];
    $extra[0]['format']['bold'] = true;
    foreach ($extra as $paragraph) { $paragraph['index'] = count($doc['paragraphs']); $doc['paragraphs'][] = $paragraph; }
    $result = manuscriptLength($doc); same('indeterminado', $result['status']); same(8004, $result['evidence']['maximum_count']);
});
test('legenda com convenção pendente entra somente no limite superior', function () {
    $doc = countedBody(str_repeat('palavra ', 4000));
    $doc['paragraphs'][1]['text'] = 'Figura 1. Legenda sintética';
    $result = manuscriptLength($doc); same(0, $result['evidence']['minimum_count']); same(4, $result['evidence']['maximum_count']);
});
test('parágrafos vazios não interrompem zona de corpo', function () {
    $doc = countedBody(str_repeat('palavra ', 4000));
    array_splice($doc['paragraphs'], 1, 0, countDocument([' '])['paragraphs']);
    foreach ($doc['paragraphs'] as $i => &$p) { $p['index'] = $i; } unset($p);
    same('conforme', manuscriptLength($doc)['status']);
});
foreach (['missing', 'duplicate', 'ambiguous_abstract'] as $kind) {
    test('delimitação incompleta não prova excesso nem falta: ' . $kind, function () use ($kind) {
        $doc = countedBody(str_repeat('palavra ', 10000));
        if ($kind === 'missing') { $doc['paragraphs'][5]['text'] = 'Referências citadas neste trabalho'; }
        elseif ($kind === 'duplicate') { $doc['paragraphs'][6]['text'] = 'Referências'; }
        else { array_unshift($doc['paragraphs'], ['index' => -1, 'text' => 'Resumo']); }
        $result = manuscriptLength($doc); same('indeterminado', $result['status']); same(false, $result['evidence']['segmentation_complete']);
    });
}
test('resumo rotulado após corpo é possível, não exclusão confirmada', function () {
    $result = manuscriptLength(countedBody(str_repeat('palavra ', 8000), [], ['Resumo: descrição adicional']));
    same('indeterminado', $result['status']); same(8003, $result['evidence']['maximum_count']);
});
foreach (['en', 'es'] as $language) {
    test('traduções pendentes não geram contagem conclusiva: ' . $language, function () use ($language) {
        $result = manuscriptLength(countedBody(str_repeat('palavra ', 5000)), 'article', $language);
        same('indeterminado', $result['status']); same(null, $result['evidence']['minimum_count']);
    });
}
test('tipo/idioma ausentes e contexto inválido', function () {
    foreach ([[null, 'pt'], ['article', null]] as $context) {
        same('indeterminado', (new ManuscriptLength(countedBody('texto'), $context[0], $context[1]))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize()['status']);
    }
    rejects(function () { new ManuscriptLength(null, 'invented', 'pt'); });
    rejects(function () { new ManuscriptLength(null, 'article', 'pt_BR'); });
});
test('extração indisponível e corpo vazio não são contagens válidas', function () {
    foreach ([null, ['available' => false], countDocument([]), countedBody(' ')] as $document) {
        same('indeterminado', (new ManuscriptLength($document, 'article', 'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize()['status']);
    }
});
test('normalização não suportada permanece indeterminada', function () {
    foreach (["\xff", "cafe\u{0301}"] as $text) {
        $result = manuscriptLength(countedBody($text));
        same('indeterminado', $result['status']); same(false, $result['evidence']['normalization_available']);
    }
});
foreach ([0 => true, 8 => true, 9 => false] as $level => $isHeading) {
    test('outline herdado ou direto classifica candidato: ' . $level, function () use ($level, $isHeading) {
        $parts = formattingParts();
        $parts['word/styles.xml'] = str_replace('<w:pPr>', '<w:pPr><w:outlineLvl w:val="' . $level . '"/>', $parts['word/styles.xml']);
        withDocx($parts, function ($path) use ($level, $isHeading) {
            $doc = (new \Estrabao\Document\DocxReader())->read($path);
            same(['known' => true, 'level' => $level], $doc['paragraphs'][0]['outline']);
            same($isHeading, $doc['paragraphs'][0]['outline']['level'] < 9);
        });
    });
}
foreach (['conforme' => 4000, 'não conforme' => 3999, 'indeterminado' => 4000] as $status => $words) {
    test('CLI integra contagem ' . $status, function () use ($status, $words) {
        $doc = countedBody(str_repeat('palavra ', $words));
        $texts = array_column($doc['paragraphs'], 'text');
        if ($status === 'indeterminado') { array_unshift($texts, 'Resumo'); }
        $parts = docxParts(docxBody($texts));
        withDocx($parts, function ($path) use ($status, $words) {
            list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'article', 'pt']);
            same(0, $code); same('', $err);
            $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
            same('0.10.0', $report['engine_version']);
            $result = array_column($report['checks'], null, 'rule_id')['manuscript.word_limit'];
            same($status, $result['status']);
            same(hash_file('sha256', $path), $result['evidence']['document_sha256']);
            same(false, strpos($out, 'palavra palavra') !== false); same(false, strpos($out, $path) !== false);
        }, function ($zip) { for ($i = 0; $i < $zip->numFiles; $i++) { $zip->setCompressionIndex($i, ZipArchive::CM_STORE); } });
    });
}
test('outline direto de corpo substitui outline herdado de título', function () {
    $parts = formattingParts();
    $parts['word/styles.xml'] = str_replace('<w:pPr>', '<w:pPr><w:outlineLvl w:val="0"/>', $parts['word/styles.xml']);
    $parts['word/document.xml'] = str_replace('<w:pPr>', '<w:pPr><w:outlineLvl w:val="9"/>', $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same(['known' => true, 'level' => 9], $doc['paragraphs'][0]['outline']);
    });
});
test('evidência de regiões é limitada e não copia conteúdo', function () {
    $texts = [];
    for ($i = 0; $i < 50; $i++) { $texts[] = ''; $texts[] = 'SYNTHETIC PRIVATE CONTENT'; }
    $doc = countedBody(str_repeat('palavra ', 4000), $texts);
    $result = manuscriptLength($doc);
    same(32, count($result['evidence']['regions']));
    same(true, $result['evidence']['regions_omitted'] > 0);
    same(false, strpos(json_encode($result), 'SYNTHETIC PRIVATE CONTENT') !== false);
});
