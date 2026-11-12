<?php
declare(strict_types=1);
use Estrabao\Text\TitleCase;
use Estrabao\Rules\{MetadataTitleCase, ManuscriptTitleCase};

foreach ([
    ['Paisagem e formação docente', 'pt', 'conforme', false],
    ['paisagem e formação docente', 'pt', 'conforme', false],
    ['ESTUDO da PAISAGEM', 'pt', 'conforme', false],
    ['PAISAGEM E FORMAÇÃO DOCENTE', 'pt', 'não conforme', true],
    ['THE STUDY OF LANDSCAPES', 'en', 'não conforme', true],
    ['ESTUDIO DE PAISAJES', 'es', 'não conforme', true],
    ['DNA', 'pt', 'indeterminado', true],
    ['UNESCO', 'pt', 'indeterminado', true],
    ['DNA RNA SI', 'pt', 'indeterminado', true],
    ['A ONU', 'pt', 'indeterminado', true],
    ['DNA e RNA', 'pt', 'conforme', false],
    ['ONU E UNESCO', 'pt', 'não conforme', true],
    ['ESTUDO GEOGRÁFICO', 'pt', 'indeterminado', true],
    ['日本語', 'pt', 'indeterminado', null],
    ['123 — ...', 'pt', 'indeterminado', null],
    ['', 'pt', 'indeterminado', null],
    ["FORMAC\u{0327}A\u{0303}O", 'pt', 'indeterminado', null],
    ["\xff", 'pt', 'indeterminado', null],
] as $case) {
    test('caixa textual conservadora ' . json_encode($case[0]), function () use ($case) {
        $inspection = TitleCase::inspect($case[0], $case[1]);
        same($case[2], $inspection['status']); same($case[3], $inspection['all_caps']);
    });
}
test('título conhecido em caixa alta não é tratado como sigla', function () {
    same('não conforme', TitleCase::inspect('INTRODUÇÃO', 'pt', true)['status']);
    same('indeterminado', TitleCase::inspect('INTRODUÇÃO', 'pt')['status']);
});
function caseMetadata(array $snapshot): array
{
    return array_map(function ($check) { return $check->jsonSerialize(); }, (new MetadataTitleCase())->evaluate(new \Estrabao\Contracts\Submission($snapshot)));
}
test('snapshot 1.1 adiciona subtítulos opcionais sem mudar obrigatoriedade', function () {
    $snapshot = \Estrabao\Contracts\Submission::fromJson(file_get_contents(__DIR__ . '/fixtures/with-subtitles.json'));
    same('Análise de dados sintéticos', $snapshot->field('subtitle')['pt_BR']);
    $checks = (new MetadataTitleCase())->evaluate($snapshot);
    same(6, count($checks));
    foreach ($checks as $check) { same('conforme', $check->jsonSerialize()['status']); }
    $without = fixture(); $without['schema_version'] = '1.1';
    same('indeterminado', caseMetadata($without)[3]['status']);
    same('conforme', caseMetadata(fixture())[0]['status']);
});
foreach ([null, [], ['pt_BR' => '']] as $subtitle) {
    test('subtítulo opcional indisponível/vazio não é reprovação ' . json_encode($subtitle), function () use ($subtitle) {
        $snapshot = fixture(); $snapshot['schema_version'] = '1.1'; $snapshot['subtitle'] = $subtitle;
        same('indeterminado', caseMetadata($snapshot)[3]['status']);
    });
}
foreach ([
    ['schema_version' => '1.0', 'subtitle' => null],
    ['schema_version' => '1.1', 'subtitle' => 'texto'],
    ['schema_version' => '1.1', 'subtitle' => ['pt' => 'texto']],
    ['schema_version' => '1.1', 'subtitle' => ['pt_BR' => 123]],
    ['schema_version' => '1.1', 'token' => 'synthetic'],
    ['schema_version' => '1.2'],
] as $i => $data) {
    test('contrato de subtítulo rejeita entrada inválida ' . $i, function () use ($data) {
        rejects(function () use ($data) { new \Estrabao\Contracts\Submission($data); });
    });
}
test('subtítulo JSON precisa preservar distinção objeto/lista', function () {
    rejects(function () { \Estrabao\Contracts\Submission::fromJson('{"schema_version":"1.1","subtitle":[]}'); });
    \Estrabao\Contracts\Submission::fromJson('{"schema_version":"1.1","subtitle":{}}');
});
test('metadados não são reescritos e evidência não copia os títulos', function () {
    $data = fixture(); $data['schema_version'] = '1.1';
    $data['subtitle'] = ['pt_BR' => 'SYNTHETIC PRIVATE STUDY OF DATA'];
    $snapshot = new \Estrabao\Contracts\Submission($data); $digest = $snapshot->digest();
    $checks = (new MetadataTitleCase())->evaluate($snapshot);
    same($digest, $snapshot->digest()); same($data['subtitle'], $snapshot->field('subtitle'));
    same(false, strpos(json_encode(array_map(function ($r) { return $r->jsonSerialize(); }, $checks)), 'SYNTHETIC PRIVATE') !== false);
});
test('HTML e normalização não produzem certeza indevida', function () {
    $data = fixture(); $data['title']['pt_BR'] = '<strong>PAISAGEM E EDUCAÇÃO</strong>';
    same('não conforme', caseMetadata($data)[0]['status']);
    $data['title']['pt_BR'] = '<script>PAISAGEM E EDUCAÇÃO</script>';
    same('indeterminado', caseMetadata($data)[0]['status']);
    $data['title']['pt_BR'] = 'FORMAC&#807;A&#771;O';
    same('indeterminado', caseMetadata($data)[0]['status']);
});
function docHeadingCase(array $parts, ?array $snapshot = null, string $language = 'pt'): array
{
    $result = null;
    withDocx($parts, function ($path) use (&$result, $snapshot, $language) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        $result = (new ManuscriptTitleCase($doc, $language))->evaluate(new \Estrabao\Contracts\Submission($snapshot ?? fixture()))[0]->jsonSerialize();
    });
    return $result;
}
test('seções em caixa alta mantêm presença mas falham na nova regra', function () {
    $parts = docxParts(docxBody(['INTRODUÇÃO', 'METODOLOGIA', 'RESULTADOS E DISCUSSÃO', 'CONSIDERAÇÕES FINAIS', 'CRÉDITOS (CREDIT)', 'REFERÊNCIAS']));
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same('conforme', structure($doc)[0]['status']);
        same('não conforme', (new ManuscriptTitleCase($doc, 'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize()['status']);
    });
});
test('títulos canônicos em caixa mista passam sem impor sentence case', function () {
    same('conforme', docHeadingCase(docxParts(docxBody(labels())))['status']);
});
test('título e subtítulo correspondentes ao snapshot são selecionados no prefixo', function () {
    $data = fixture(); $data['schema_version'] = '1.1'; $data['subtitle'] = ['pt_BR' => 'ESTUDO DA PAISAGEM'];
    $parts = docxParts(docxBody(array_merge([$data['title']['pt_BR'], $data['subtitle']['pt_BR']], labels())));
    $check = docHeadingCase($parts, $data); same('não conforme', $check['status']);
    same(true, $check['evidence']['samples'][1]['heading_confirmed']);
});
test('caixa alta em prosa normal não vira título por suposição', function () {
    $check = docHeadingCase(docxParts(docxBody(array_merge(labels(), ['ESTUDO DA PAISAGEM']))));
    same('conforme', $check['status']); same(6, $check['evidence']['counts']['conforme']);
});
test('negrito isolado só identifica candidato ambíguo', function () {
    $body = docxBody(labels()) . '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>ESTUDO DA PAISAGEM</w:t></w:r></w:p>';
    same('indeterminado', docHeadingCase(docxParts($body))['status']);
});
test('outline identifica subtítulo extra sem exigir nome de estilo', function () {
    $body = docxBody(labels()) . '<w:p><w:pPr><w:outlineLvl w:val="1"/></w:pPr><w:r><w:t>ESTUDO DA PAISAGEM</w:t></w:r></w:p>';
    same('não conforme', docHeadingCase(docxParts($body))['status']);
});
foreach ([
    'paragraph style' => [style('Heading', '<w:rPr><w:caps/></w:rPr>'), '<w:pStyle w:val="Heading"/>', '', 'não conforme'],
    'inherited style' => [style('Base', '<w:rPr><w:caps/></w:rPr>') . style('Heading', '', 'Base'), '<w:pStyle w:val="Heading"/>', '', 'não conforme'],
    'style toggle cancels' => [style('Base', '<w:rPr><w:caps/></w:rPr>') . style('Heading', '<w:rPr><w:caps/></w:rPr>', 'Base'), '<w:pStyle w:val="Heading"/>', '', 'conforme'],
    'style off preserves' => [style('Base', '<w:rPr><w:caps/></w:rPr>') . style('Heading', '<w:rPr><w:caps w:val="0"/></w:rPr>', 'Base'), '<w:pStyle w:val="Heading"/>', '', 'não conforme'],
    'direct off' => [style('Heading', '<w:rPr><w:caps/></w:rPr>'), '<w:pStyle w:val="Heading"/>', '<w:caps w:val="0"/>', 'conforme'],
    'character style' => [style('Char', '<w:rPr><w:caps/></w:rPr>', null, 'character'), '', '<w:rStyle w:val="Char"/>', 'não conforme'],
    'small caps' => ['', '', '<w:smallCaps/>', 'indeterminado'],
    'invalid property' => ['', '', '<w:caps w:val="invented"/>', 'indeterminado'],
    'duplicate property' => ['', '', '<w:caps/><w:caps/>', 'indeterminado'],
    'conflicting properties' => ['', '', '<w:caps/><w:smallCaps/>', 'indeterminado'],
    'cycle' => [style('Heading', '', 'Heading'), '<w:pStyle w:val="Heading"/>', '', 'indeterminado'],
    'document defaults' => ['<w:docDefaults><w:rPrDefault><w:rPr><w:caps/></w:rPr></w:rPrDefault></w:docDefaults>', '', '', 'não conforme'],
] as $name => $case) {
    test('formatação de caixa no Word ' . $name, function () use ($case) {
        same($case[3], docHeadingCase(styleParts($case[0], $case[1], $case[2]))['status']);
    });
}
test('caps em só um run não implica título inteiro em caixa alta', function () {
    $parts = docxParts('<w:p><w:r><w:rPr><w:caps/></w:rPr><w:t>Intro</w:t></w:r><w:r><w:t>dução</w:t></w:r></w:p>');
    same('conforme', docHeadingCase($parts)['status']);
});
test('sigla em subtítulo de outline permanece para revisão', function () {
    $parts = docxParts('<w:p><w:pPr><w:outlineLvl w:val="0"/></w:pPr><w:r><w:t>UNESCO</w:t></w:r></w:p>');
    same('indeterminado', docHeadingCase($parts)['status']);
});
test('extração incompleta não aprova títulos pela leitura parcial', function () {
    same('indeterminado', docHeadingCase(docxParts(docxBody(labels()) . simpleVisualTable()))['status']);
    same('indeterminado', (new ManuscriptTitleCase())->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize()['status']);
});
test('evidência limitada preserva violação tardia sem copiar texto', function () {
    $texts = array_fill(0, 40, 'Introdução'); $texts[] = 'INTRODUÇÃO';
    $result = docHeadingCase(docxParts(docxBody($texts)));
    same('não conforme', $result['status']); same(32, count($result['evidence']['samples'])); same(9, $result['evidence']['samples_omitted']);
    same(true, in_array(40, array_column($result['evidence']['samples'], 'paragraph_index'), true));
    same(false, strpos(json_encode($result), 'INTRODUÇÃO') !== false);
});
test('CLI mantém compatibilidade 1.0 e aceita 1.1 com subtítulo', function () {
    foreach (['complete.json', 'with-subtitles.json'] as $fixture) {
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/' . $fixture]);
        same(0, $code); same('', $err);
        $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
        same('0.12.0', $report['engine_version']); same(45, count($report['checks']));
    }
});
