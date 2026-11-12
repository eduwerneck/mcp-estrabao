<?php
declare(strict_types=1);

function styleParts(string $styles = '', string $paragraphProperties = '', string $runProperties = '', ?string $numbering = null): array
{
    $body = str_replace(['<w:p>', '<w:r>'], ['<w:p><w:pPr>' . $paragraphProperties . '</w:pPr>', '<w:r><w:rPr>' . $runProperties . '</w:rPr>'], docxBody(labels()));
    $parts = docxParts($body);
    $parts['word/styles.xml'] = '<w:styles xmlns:w="' . \Estrabao\Document\DocxReader::W . '">' . $styles . '</w:styles>';
    $links = '<Relationship Id="s" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    if ($numbering !== null) {
        $parts['word/numbering.xml'] = '<w:numbering xmlns:w="' . \Estrabao\Document\DocxReader::W . '">' . $numbering . '</w:numbering>';
        $links .= '<Relationship Id="n" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>';
    }
    $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $links . '</Relationships>';
    return $parts;
}
function style(string $id, string $properties = '', ?string $base = null, string $type = 'paragraph', bool $default = false): string
{
    return '<w:style w:type="' . $type . '" w:styleId="' . $id . '"' . ($default ? ' w:default="1"' : '') . '>'
        . ($base === null ? '' : '<w:basedOn w:val="' . $base . '"/>') . $properties . '</w:style>';
}
function listDefinition(string $format = 'decimal', string $extra = ''): string
{
    return '<w:abstractNum w:abstractNumId="1">' . $extra . '<w:lvl w:ilvl="0"><w:numFmt w:val="' . $format . '"/></w:lvl></w:abstractNum>'
        . '<w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>';
}
function checkStyleParts(array $parts, ?bool $bold, ?bool $numbered, string $status): void
{
    withDocx($parts, function ($path) use ($bold, $numbered, $status) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same(true, $doc['available']);
        same($bold, $doc['paragraphs'][0]['format']['bold']);
        same($numbered, $doc['paragraphs'][0]['format']['numbered']);
        $result = structure($doc)[1]; same($status, $result['status']); same('3.0.0', $result['rule_version']);
    });
}
$heading = '<w:pStyle w:val="Heading"/>';
$boldRun = '<w:rPr><w:b/></w:rPr>';
foreach ([
    'default paragraph' => [style('Heading', $boldRun, null, 'paragraph', true), '', '', true],
    'named paragraph' => [style('Heading', $boldRun), $heading, '', true],
    'basedOn' => [style('Base', $boldRun) . style('Heading', '', 'Base'), $heading, '', true],
    'style toggle on cancels inherited on' => [style('Base', $boldRun) . style('Heading', $boldRun, 'Base'), $heading, '', false],
    'style toggle off preserves inherited on' => [style('Base', $boldRun) . style('Heading', '<w:rPr><w:b w:val="0"/></w:rPr>', 'Base'), $heading, '', true],
    'direct off overrides style' => [style('Heading', $boldRun), $heading, '<w:b w:val="false"/>', false],
    'direct on overrides toggle' => [style('Base', $boldRun) . style('Heading', $boldRun, 'Base'), $heading, '<w:b/>', true],
    'character style' => [style('Emphasis', $boldRun, null, 'character'), '', '<w:rStyle w:val="Emphasis"/>', true],
    'character basedOn' => [style('Base', $boldRun, null, 'character') . style('Emphasis', '', 'Base', 'character'), '', '<w:rStyle w:val="Emphasis"/>', true],
    'character toggles paragraph bold' => [style('Heading', $boldRun) . style('Emphasis', $boldRun, null, 'character'), $heading, '<w:rStyle w:val="Emphasis"/>', false],
    'default character' => [style('DefaultChar', $boldRun, null, 'character', true), '', '', true],
    'docDefaults bold' => ['<w:docDefaults><w:rPrDefault><w:rPr><w:b/></w:rPr></w:rPrDefault></w:docDefaults>', '', '', true],
    'document defaults off' => ['', '', '', false],
] as $name => $case) {
    test('herança de negrito: ' . $name, function () use ($case) {
        list($styles, $paragraph, $run, $bold) = $case;
        checkStyleParts(styleParts($styles, $paragraph, $run), $bold, false, $bold ? 'conforme' : 'não conforme');
    });
}
foreach ([
    'cycle' => style('Heading', '', 'Other') . style('Other', '', 'Heading'),
    'missing base' => style('Heading', '', 'Missing'),
    'missing selected style' => '',
    'wrong style type' => style('Heading', $boldRun, null, 'character'),
    'duplicate style' => style('Heading', $boldRun) . style('Heading', $boldRun),
    'duplicate defaults' => style('One', '', null, 'paragraph', true) . style('Two', '', null, 'paragraph', true),
    'invalid toggle' => style('Heading', '<w:rPr><w:b w:val="invented"/></w:rPr>'),
    'complex script' => style('Heading', '<w:rPr><w:cs/><w:bCs/></w:rPr>'),
] as $name => $styles) {
    test('herança desconhecida: ' . $name, function () use ($styles, $heading) {
        checkStyleParts(styleParts($styles, $heading), null, null, 'indeterminado');
    });
}
test('cadeia longa tem limite sem recursão', function () use ($heading) {
    $styles = style('Heading', '', 's0');
    for ($i = 0; $i < 33; $i++) { $styles .= style('s' . $i, '', $i === 32 ? null : 's' . ($i + 1)); }
    checkStyleParts(styleParts($styles, $heading), null, null, 'indeterminado');
});
foreach (['decimal' => true, 'upperRoman' => true, 'lowerLetter' => true, 'none' => false, 'bullet' => null, 'custom' => null] as $format => $expected) {
    test('numeração herdada: ' . $format, function () use ($format, $expected, $heading, $boldRun) {
        $styles = style('Base', '<w:pPr><w:numPr><w:numId w:val="2"/></w:numPr></w:pPr>' . $boldRun) . style('Heading', '', 'Base');
        checkStyleParts(styleParts($styles, $heading, '', listDefinition($format)), true, $expected,
            $expected === true ? 'não conforme' : ($expected === false ? 'conforme' : 'indeterminado'));
    });
}
test('numId zero direto cancela lista herdada', function () use ($heading, $boldRun) {
    $styles = style('Heading', '<w:pPr><w:numPr><w:numId w:val="2"/></w:numPr></w:pPr>' . $boldRun);
    checkStyleParts(styleParts($styles, $heading . '<w:numPr><w:numId w:val="0"/></w:numPr>', '', listDefinition()), true, false, 'conforme');
});
test('numeração direta válida', function () {
    checkStyleParts(styleParts('', '<w:numPr><w:numId w:val="2"/></w:numPr>', '<w:b/>', listDefinition()), true, true, 'não conforme');
});
test('ilvl direto mantém numId herdado', function () use ($heading, $boldRun) {
    $styles = style('Heading', '<w:pPr><w:numPr><w:numId w:val="2"/></w:numPr></w:pPr>' . $boldRun);
    $numbering = str_replace('w:ilvl="0"', 'w:ilvl="1"', listDefinition('lowerRoman'));
    checkStyleParts(styleParts($styles, $heading . '<w:numPr><w:ilvl w:val="1"/></w:numPr>', '', $numbering), true, true, 'não conforme');
});
test('override de nível sem numeração', function () {
    $numbering = str_replace('</w:num>', '<w:lvlOverride w:ilvl="0"><w:lvl w:ilvl="0"><w:numFmt w:val="none"/></w:lvl></w:lvlOverride></w:num>', listDefinition());
    checkStyleParts(styleParts('', '<w:numPr><w:numId w:val="2"/></w:numPr>', '<w:b/>', $numbering), true, false, 'conforme');
});
foreach ([
    'missing definition' => null,
    'missing instance' => '',
    'broken abstract reference' => '<w:num w:numId="2"><w:abstractNumId w:val="8"/></w:num>',
    'numbering style link' => listDefinition('decimal', '<w:numStyleLink w:val="List"/>'),
    'duplicate instance' => listDefinition() . '<w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num>',
] as $name => $numbering) {
    test('numeração não resolvida: ' . $name, function () use ($numbering) {
        checkStyleParts(styleParts('', '<w:numPr><w:numId w:val="2"/></w:numPr>', '<w:b/>', $numbering), true, null, 'indeterminado');
    });
}
test('estilo implicitamente associado a lista não aprova ausência', function () use ($heading, $boldRun) {
    $numbering = str_replace('<w:numFmt', '<w:pStyle w:val="Heading"/><w:numFmt', listDefinition());
    checkStyleParts(styleParts(style('Heading', $boldRun), $heading, '', $numbering), true, null, 'indeterminado');
});
foreach (['external', 'missing', 'orphan', 'duplicate'] as $kind) {
    test('relacionamento de estilo inseguro ou incompleto: ' . $kind, function () use ($kind, $heading, $boldRun) {
        $parts = styleParts(style('Heading', $boldRun), $heading);
        $key = 'word/_rels/document.xml.rels';
        if ($kind === 'external') { $parts[$key] = str_replace('Target="styles.xml"', 'TargetMode="External" Target="https://invalid.example/styles.xml"', $parts[$key]); }
        elseif ($kind === 'missing') { unset($parts['word/styles.xml']); }
        elseif ($kind === 'orphan') { unset($parts[$key]); }
        else { $parts[$key] = str_replace('</Relationships>', '<Relationship Id="s2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>', $parts[$key]); }
        checkStyleParts($parts, null, null, 'indeterminado');
    });
}
test('relacionamento interno seleciona parte de nome diferente', function () use ($heading, $boldRun) {
    $parts = styleParts(style('Heading', $boldRun), $heading);
    $parts['custom/styles-part.xml'] = $parts['word/styles.xml']; unset($parts['word/styles.xml']);
    $parts['word/_rels/document.xml.rels'] = str_replace('Target="styles.xml"', 'Target="../custom/styles-part.xml"', $parts['word/_rels/document.xml.rels']);
    checkStyleParts($parts, true, false, 'conforme');
});
test('título com runs mistos não é inteiramente negrito', function () {
    $parts = styleParts('', '', '<w:b/>');
    $parts['word/document.xml'] = str_replace('<w:t>Introdução</w:t>', '<w:t>Intro</w:t></w:r><w:r><w:rPr><w:b w:val="0"/></w:rPr><w:t>dução</w:t>', $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $doc = (new \Estrabao\Document\DocxReader())->read($path);
        same(false, $doc['paragraphs'][0]['format']['bold']); same('não conforme', structure($doc)[1]['status']);
    });
});
test('formatação completa exige todas as seções reconhecidas', function () {
    $parts = styleParts('', '', '<w:b/>');
    $parts['word/document.xml'] = str_replace('Metodologia', 'Texto sintético privado', $parts['word/document.xml']);
    withDocx($parts, function ($path) {
        $checks = structure((new \Estrabao\Document\DocxReader())->read($path));
        same('não conforme', $checks[0]['status']); same('indeterminado', $checks[1]['status']);
        same(false, strpos(json_encode($checks), 'Texto sintético privado') !== false);
    });
});
test('nível do título vem de pStyle da definição de lista', function () use ($heading, $boldRun) {
    $styles = style('Heading', '<w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="2"/></w:numPr></w:pPr>' . $boldRun);
    $numbering = str_replace('w:ilvl="0"', 'w:ilvl="2"', listDefinition());
    $numbering = str_replace('<w:numFmt', '<w:pStyle w:val="Heading"/><w:numFmt', $numbering);
    checkStyleParts(styleParts($styles, $heading, '', $numbering), true, true, 'não conforme');
});
test('ilvl de estilo sem associação reconhecida é indeterminado', function () use ($heading, $boldRun) {
    $styles = style('Heading', '<w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="2"/></w:numPr></w:pPr>' . $boldRun);
    checkStyleParts(styleParts($styles, $heading, '', listDefinition()), true, null, 'indeterminado');
});
test('containers de propriedades duplicados não aprovam formatação', function () use ($heading, $boldRun) {
    checkStyleParts(styleParts(style('Heading', $boldRun . $boldRun), $heading), null, null, 'indeterminado');
});
test('espaço Unicode em run sem negrito não reprova o título', function () {
    $parts = styleParts('', '', '<w:b/>');
    $parts['word/document.xml'] = str_replace('<w:t>Introdução</w:t>', '<w:t>Introdução</w:t></w:r><w:r><w:t>&#160;</w:t>', $parts['word/document.xml']);
    checkStyleParts($parts, true, false, 'conforme');
});
foreach (['conforme', 'não conforme', 'indeterminado'] as $expected) {
    test('CLI resolve estilos e produz formato ' . $expected, function () use ($expected, $heading, $boldRun) {
        $styles = style('Heading', $boldRun);
        $numbering = null;
        if ($expected === 'não conforme') {
            $styles = style('Heading', $boldRun . '<w:pPr><w:numPr><w:numId w:val="2"/></w:numPr></w:pPr>');
            $numbering = listDefinition();
        } elseif ($expected === 'indeterminado') { $styles = style('Heading', '', 'Heading'); }
        withDocx(styleParts($styles, $heading, '', $numbering), function ($path) use ($expected) {
            list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'article', 'pt']);
            same(0, $code); same('', $err);
            $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
            same('0.13.0', $report['engine_version']);
            $checks = array_column($report['checks'], null, 'rule_id');
            same($expected, $checks['manuscript.headings.format']['status']);
            same('conforme', $checks['manuscript.docx.structure']['status']);
            same(false, strpos($out, $path) !== false);
            same(false, strpos($out, 'Heading') !== false);
        });
    });
}
test('quantidade excessiva de estilos não inicia resolução', function () use ($heading) {
    $styles = style('Heading');
    for ($i = 0; $i < \Estrabao\Document\StyleResolver::MAX_STYLES; $i++) { $styles .= style('s' . $i); }
    checkStyleParts(styleParts($styles, $heading), null, null, 'indeterminado');
});
