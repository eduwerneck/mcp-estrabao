<?php
declare(strict_types=1);
use Estrabao\Document\DocxReader;
use Estrabao\Rules\ManuscriptFormatting;

function standardSection(): string
{
    return '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1701" w:right="1701" w:bottom="1701" w:left="1701" w:gutter="0"/></w:sectPr>';
}
function formattingParts(): array
{
    $properties = '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="24"/></w:rPr><w:pPr><w:spacing w:line="360" w:lineRule="auto"/></w:pPr>';
    $parts = styleParts(style('Body', $properties, null, 'paragraph', true));
    $parts['word/document.xml'] = str_replace('</w:body>', standardSection() . '</w:body>', $parts['word/document.xml']);
    return $parts;
}
function formattingChecks(string $path): array
{
    $doc = (new DocxReader())->read($path);
    same(true, $doc['available']);
    $checks = (new ManuscriptFormatting($doc))->evaluate(new \Estrabao\Contracts\Submission(fixture()));
    return array_column(array_map(function ($r) { return $r->jsonSerialize(); }, $checks), null, 'rule_id');
}
function formattingTest(array $parts, string $id, string $status): void
{
    withDocx($parts, function ($path) use ($id, $status) { same($status, formattingChecks($path)['manuscript.' . $id]['status']); });
}
function addSettings(array $parts, string $settings): array
{
    $parts['word/settings.xml'] = '<w:settings xmlns:w="' . DocxReader::W . '">' . $settings . '</w:settings>';
    $parts['word/_rels/document.xml.rels'] = str_replace('</Relationships>', '<Relationship Id="settings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/></Relationships>', $parts['word/_rels/document.xml.rels']);
    return $parts;
}

test('formatação completa herdada nas cinco regras', function () {
    withDocx(formattingParts(), function ($path) {
        $checks = formattingChecks($path);
        same(5, count($checks));
        same(['conforme'], array_values(array_unique(array_column($checks, 'status'))));
        foreach ($checks as $check) { same(hash_file('sha256', $path), $check['evidence']['document_sha256']); }
    });
});
foreach ([
    'A4 portrait' => ['11906', '16838', 'conforme'],
    'A4 landscape' => ['16838', '11906', 'conforme'],
    'A4 integer rounding' => ['11907', '16839', 'conforme'],
    'US Letter' => ['12240', '15840', 'não conforme'],
    'unknown units' => ['210mm', '297mm', 'indeterminado'],
    'missing width' => ['', '16838', 'indeterminado'],
] as $name => $case) {
    test('tamanho da página: ' . $name, function () use ($case) {
        $parts = formattingParts();
        $parts['word/document.xml'] = str_replace(['w:w="11906"', 'w:h="16838"'], ['w:w="' . $case[0] . '"', 'w:h="' . $case[1] . '"'], $parts['word/document.xml']);
        formattingTest($parts, 'page.a4', $case[2]);
    });
}
foreach (['top', 'right', 'bottom', 'left'] as $side) {
    test('margem divergente: ' . $side, function () use ($side) {
        $parts = formattingParts(); $parts['word/document.xml'] = str_replace('w:' . $side . '="1701"', 'w:' . $side . '="1440"', $parts['word/document.xml']);
        formattingTest($parts, 'page.margins', 'não conforme');
    });
}
foreach (['1699' => 'conforme', '1703' => 'conforme', '1698' => 'não conforme', '0' => 'não conforme', '-1701' => 'indeterminado', '3cm' => 'indeterminado'] as $margin => $status) {
    test('arredondamento e unidade da margem: ' . $margin, function () use ($margin, $status) {
        $parts = formattingParts(); $parts['word/document.xml'] = str_replace('w:top="1701"', 'w:top="' . $margin . '"', $parts['word/document.xml']);
        formattingTest($parts, 'page.margins', $status);
    });
}
foreach (['<w:mirrorMargins/>', '<w:gutterAtTop/>', '<w:bookFoldPrinting/>'] as $setting) {
    test('margens especiais são indeterminadas: ' . $setting, function () use ($setting) {
        formattingTest(addSettings(formattingParts(), $setting), 'page.margins', 'indeterminado');
    });
}
test('gutter positivo não aprova margens efetivas', function () {
    $parts = formattingParts(); $parts['word/document.xml'] = str_replace('w:gutter="0"', 'w:gutter="100"', $parts['word/document.xml']);
    formattingTest($parts, 'page.margins', 'indeterminado');
});
test('ausência de propriedades de página é indeterminada', function () {
    $parts = formattingParts(); $parts['word/document.xml'] = str_replace(standardSection(), '<w:sectPr/>', $parts['word/document.xml']);
    formattingTest($parts, 'page.a4', 'indeterminado'); formattingTest($parts, 'page.margins', 'indeterminado');
});
test('seção intermediária divergente é examinada', function () {
    $parts = formattingParts();
    $wrong = str_replace('w:w="11906"', 'w:w="12240"', standardSection());
    $parts['word/document.xml'] = str_replace('<w:pPr>', '<w:pPr>' . $wrong, $parts['word/document.xml']);
    formattingTest($parts, 'page.a4', 'não conforme');
});
test('seção final herda página e margens omitidas da anterior', function () {
    $parts = formattingParts();
    $parts['word/document.xml'] = str_replace(standardSection(), '<w:sectPr/>', $parts['word/document.xml']);
    $parts['word/document.xml'] = preg_replace('/<w:pPr>/', '<w:pPr>' . standardSection(), $parts['word/document.xml'], 1);
    formattingTest($parts, 'page.a4', 'conforme'); formattingTest($parts, 'page.margins', 'conforme');
});
test('seção final ausente impede conformidade de página', function () {
    $parts = formattingParts(); $parts['word/document.xml'] = str_replace(standardSection(), '', $parts['word/document.xml']);
    $parts['word/document.xml'] = preg_replace('/<w:pPr>/', '<w:pPr>' . standardSection(), $parts['word/document.xml'], 1);
    formattingTest($parts, 'page.a4', 'indeterminado');
});
test('propriedade de seção duplicada ou revisada é indeterminada', function () {
    foreach (['<w:pgSz w:w="11906" w:h="16838"/>', '<w:sectPrChange/>'] as $extra) {
        $parts = formattingParts(); $parts['word/document.xml'] = str_replace('</w:sectPr>', $extra . '</w:sectPr>', $parts['word/document.xml']);
        formattingTest($parts, 'page.a4', 'indeterminado');
    }
});
foreach (['Times New Roman' => 'conforme', 'times new roman' => 'conforme', 'Arial' => 'não conforme', '' => 'indeterminado'] as $font => $status) {
    test('família de fonte declarada: ' . $font, function () use ($font, $status) {
        $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('Times New Roman', $font, $parts['word/styles.xml']);
        formattingTest($parts, 'body.font', $status);
    });
}
foreach (['24' => 'conforme', '22' => 'não conforme', '28' => 'não conforme', '0' => 'indeterminado', '12pt' => 'indeterminado', '-24' => 'indeterminado'] as $size => $status) {
    test('tamanho em meios-pontos: ' . $size, function () use ($size, $status) {
        $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('w:val="24"', 'w:val="' . $size . '"', $parts['word/styles.xml']);
        formattingTest($parts, 'body.font_size', $status);
    });
}
foreach ([['360', 'auto', 'conforme'], ['240', 'auto', 'não conforme'], ['480', 'auto', 'não conforme'], ['360', 'exact', 'indeterminado'], ['360', 'atLeast', 'indeterminado'], ['0', 'auto', 'indeterminado'], ['abc', 'auto', 'indeterminado'], ['360', 'invented', 'indeterminado']] as $case) {
    test('entrelinhas: ' . implode('/', $case), function () use ($case) {
        $parts = formattingParts(); $parts['word/styles.xml'] = str_replace(['w:line="360"', 'w:lineRule="auto"'], ['w:line="' . $case[0] . '"', 'w:lineRule="' . $case[1] . '"'], $parts['word/styles.xml']);
        formattingTest($parts, 'body.line_spacing', $case[2]);
    });
}
test('espaçamento com line sem lineRule usa auto quando não herdado', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace(' w:lineRule="auto"', '', $parts['word/styles.xml']);
    formattingTest($parts, 'body.line_spacing', 'conforme');
});
test('lineRule herdado não é apagado por line direto', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('w:lineRule="auto"', 'w:lineRule="exact"', $parts['word/styles.xml']);
    $parts['word/document.xml'] = str_replace('<w:pPr>', '<w:pPr><w:spacing w:line="360"/>', $parts['word/document.xml']);
    formattingTest($parts, 'body.line_spacing', 'indeterminado');
});
test('grade de documento impede afirmar entrelinhas efetiva', function () {
    $parts = formattingParts(); $parts['word/document.xml'] = str_replace('</w:sectPr>', '<w:docGrid w:type="lines" w:linePitch="360"/></w:sectPr>', $parts['word/document.xml']);
    formattingTest($parts, 'body.line_spacing', 'indeterminado');
});
test('tipografia direta substitui o estilo herdado', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace(['Times New Roman', 'w:val="24"', 'w:line="360"'], ['Arial', 'w:val="20"', 'w:line="240"'], $parts['word/styles.xml']);
    $parts['word/document.xml'] = str_replace(['<w:rPr>', '<w:pPr>'], ['<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="24"/>', '<w:pPr><w:spacing w:line="360"/>'], $parts['word/document.xml']);
    foreach (['body.font', 'body.font_size', 'body.line_spacing'] as $id) { formattingTest($parts, $id, 'conforme'); }
});
test('propriedades tipográficas de basedOn e estilo de caractere', function () {
    $parts = formattingParts();
    $parts['word/styles.xml'] = str_replace('</w:styles>', style('Child', '', 'Body') . style('CharBase', '<w:rPr><w:sz w:val="22"/></w:rPr>', null, 'character') . style('Char', '', 'CharBase', 'character') . '</w:styles>', $parts['word/styles.xml']);
    $parts['word/document.xml'] = str_replace(['<w:pPr>', '<w:rPr>'], ['<w:pPr><w:pStyle w:val="Child"/>', '<w:rPr><w:rStyle w:val="Char"/>'], $parts['word/document.xml']);
    formattingTest($parts, 'body.font', 'conforme'); formattingTest($parts, 'body.font_size', 'não conforme');
});
test('defaults de documento fornecem fonte, tamanho e espaçamento', function () {
    $parts = formattingParts();
    $parts['word/styles.xml'] = '<w:styles xmlns:w="' . DocxReader::W . '"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="24"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:line="360"/></w:pPr></w:pPrDefault></w:docDefaults></w:styles>';
    foreach (['body.font', 'body.font_size', 'body.line_spacing'] as $id) { formattingTest($parts, $id, 'conforme'); }
});
function themeParts(): array
{
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('w:ascii="Times New Roman" w:hAnsi="Times New Roman"', 'w:asciiTheme="minorAscii" w:hAnsiTheme="minorHAnsi"', $parts['word/styles.xml']);
    $parts['word/theme/theme1.xml'] = '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:themeElements><a:fontScheme><a:minorFont><a:latin typeface="Times New Roman"/></a:minorFont></a:fontScheme></a:themeElements></a:theme>';
    $parts['word/_rels/document.xml.rels'] = str_replace('</Relationships>', '<Relationship Id="theme" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/></Relationships>', $parts['word/_rels/document.xml.rels']);
    return $parts;
}
test('fontes de tema local são resolvidas', function () { formattingTest(themeParts(), 'body.font', 'conforme'); });
test('tema prevalece sobre literal na mesma camada', function () {
    $parts = themeParts(); $parts['word/styles.xml'] = str_replace('<w:rFonts ', '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" ', $parts['word/styles.xml']);
    formattingTest($parts, 'body.font', 'conforme');
});
test('fonte literal direta substitui tema herdado', function () {
    $parts = themeParts(); $parts['word/document.xml'] = str_replace('<w:rPr>', '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>', $parts['word/document.xml']);
    formattingTest($parts, 'body.font', 'não conforme');
});
foreach (['missing', 'external', 'empty'] as $kind) {
    test('tema não resolvido: ' . $kind, function () use ($kind) {
        $parts = themeParts();
        if ($kind === 'missing') { unset($parts['word/theme/theme1.xml']); }
        elseif ($kind === 'external') { $parts['word/_rels/document.xml.rels'] = str_replace('Target="theme/theme1.xml"', 'TargetMode="External" Target="https://invalid.example/theme.xml"', $parts['word/_rels/document.xml.rels']); }
        else { $parts['word/theme/theme1.xml'] = str_replace('Times New Roman', '', $parts['word/theme/theme1.xml']); }
        formattingTest($parts, 'body.font', 'indeterminado'); formattingTest($parts, 'body.font_size', 'conforme');
    });
}
test('slots ASCII e hAnsi não são confundidos', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('w:hAnsi="Times New Roman"', 'w:hAnsi="Arial"', $parts['word/styles.xml']);
    formattingTest($parts, 'body.font', 'não conforme');
    $parts['word/document.xml'] = preg_replace('/<w:t>.*?<\/w:t>/u', '<w:t>ASCII only</w:t>', $parts['word/document.xml']);
    formattingTest($parts, 'body.font', 'conforme');
});
foreach (['<w:cs/>', '<w:rtl/>', '<w:rFonts w:hint="eastAsia"/>'] as $property) {
    test('seleção de script não suportada: ' . $property, function () use ($property) {
        $parts = formattingParts(); $parts['word/document.xml'] = str_replace('<w:rPr>', '<w:rPr>' . $property, $parts['word/document.xml']);
        formattingTest($parts, 'body.font', 'indeterminado'); formattingTest($parts, 'body.font_size', 'indeterminado');
    });
}
test('texto não latino sem resolução específica é indeterminado', function () {
    $parts = formattingParts(); $parts['word/document.xml'] = preg_replace('/<w:t>.*?<\/w:t>/u', '<w:t>日本語</w:t>', $parts['word/document.xml']);
    formattingTest($parts, 'body.font', 'indeterminado'); formattingTest($parts, 'body.font_size', 'indeterminado');
});
test('extração indisponível ou documento vazio nunca aprova formatação', function () {
    foreach ([null, ['available' => false, 'reason' => 'incomplete_coverage'], ['available' => true, 'paragraphs' => []]] as $doc) {
        foreach ((new ManuscriptFormatting($doc))->evaluate(new \Estrabao\Contracts\Submission(fixture())) as $check) { same('indeterminado', $check->jsonSerialize()['status']); }
    }
});
test('evidências limitadas e sem textos nem nomes arbitrários de fonte', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('Times New Roman', 'SYNTHETIC PRIVATE FONT', $parts['word/styles.xml']);
    $parts['word/document.xml'] = '<w:document xmlns:w="' . DocxReader::W . '"><w:body>' . docxBody(array_fill(0, 50, 'SYNTHETIC PRIVATE TEXT')) . standardSection() . '</w:body></w:document>';
    withDocx($parts, function ($path) {
        $checks = formattingChecks($path);
        same(32, count($checks['manuscript.body.font']['evidence']['samples']));
        same(18, $checks['manuscript.body.font']['evidence']['samples_omitted']);
        same(false, strpos(json_encode($checks), 'SYNTHETIC PRIVATE') !== false);
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'article', 'pt']);
        same(0, $code); same('', $err);
        same(false, strpos($out, 'SYNTHETIC PRIVATE') !== false); same(false, strpos($out, $path) !== false);
        same('0.19.0', json_decode($out, true, 64, JSON_THROW_ON_ERROR)['engine_version']);
    });
});
test('violação tardia preserva posição na amostra limitada', function () {
    $parts = formattingParts();
    $body = docxBody(array_fill(0, 40, 'Texto normal sintético'));
    $body .= '<w:p><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/></w:rPr><w:t>Texto divergente</w:t></w:r></w:p>';
    $parts['word/document.xml'] = '<w:document xmlns:w="' . DocxReader::W . '"><w:body>' . $body . standardSection() . '</w:body></w:document>';
    withDocx($parts, function ($path) {
        $result = formattingChecks($path)['manuscript.body.font']; same('não conforme', $result['status']);
        $failures = array_values(array_filter($result['evidence']['samples'], function ($sample) { return $sample['status'] === 'não conforme'; }));
        same(40, $failures[0]['paragraph_index']);
    });
});
test('relacionamento externo de settings impede aprovar margens', function () {
    $parts = addSettings(formattingParts(), '');
    $parts['word/_rels/document.xml.rels'] = str_replace('Target="settings.xml"', 'TargetMode="External" Target="https://invalid.example/settings.xml"', $parts['word/_rels/document.xml.rels']);
    formattingTest($parts, 'page.margins', 'indeterminado'); formattingTest($parts, 'page.a4', 'conforme');
});
test('estilos cíclicos não geram divergência de tipografia', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('</w:style>', '<w:basedOn w:val="Body"/></w:style>', $parts['word/styles.xml']);
    foreach (['body.font', 'body.font_size', 'body.line_spacing'] as $id) { formattingTest($parts, $id, 'indeterminado'); }
    formattingTest($parts, 'page.a4', 'conforme');
});
test('propriedades tipográficas duplicadas não aprovam o texto', function () {
    foreach (['<w:sz w:val="24"/>', '<w:rFonts w:ascii="Times New Roman"/>'] as $duplicate) {
        $parts = formattingParts(); $parts['word/styles.xml'] = str_replace('</w:rPr>', $duplicate . '</w:rPr>', $parts['word/styles.xml']);
        formattingTest($parts, 'body.font_size', 'indeterminado');
    }
});
test('fontes parcialmente desconhecidas não escondem divergência comprovada', function () {
    $parts = formattingParts(); $parts['word/styles.xml'] = str_replace(['w:ascii="Times New Roman"', 'w:hAnsi="Times New Roman"'], ['w:ascii="Arial"', ''], $parts['word/styles.xml']);
    formattingTest($parts, 'body.font', 'não conforme');
});
