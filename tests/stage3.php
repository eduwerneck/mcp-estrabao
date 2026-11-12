<?php
declare(strict_types=1);
use Estrabao\Document\DocxReader;
use Estrabao\Contracts\Submission;
use Estrabao\Rules\ManuscriptStructure;

function docxParts(string $body): array
{
    return [
        '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
        '_rels/.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
        'word/document.xml' => '<w:document xmlns:w="' . DocxReader::W . '"><w:body>' . $body . '</w:body></w:document>',
    ];
}
function docxBody(array $labels): string
{
    $body = '';
    foreach ($labels as $label) {
        $body .= '<w:p><w:r><w:t>' . htmlspecialchars($label, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
    }
    return $body;
}
function labels(): array
{
    return ['Introdução', 'Metodologia', 'Resultados e Discussão', 'Considerações Finais', 'Créditos (CRediT)', 'Referências'];
}
function withDocx(array $parts, callable $body, ?callable $modify = null): void
{
    $base = tempnam(sys_get_temp_dir(), 'estrabao-test-');
    unlink($base); $path = $base . '.docx';
    try {
        $zip = new ZipArchive(); same(true, $zip->open($path, ZipArchive::CREATE));
        foreach ($parts as $name => $content) { $zip->addFromString($name, $content); }
        if ($modify !== null) { $modify($zip); }
        $zip->close(); $body($path);
    } finally { if (is_file($path)) { unlink($path); } }
}
function structure(array $document, string $type = 'article', string $language = 'pt'): array
{
    return array_map(function ($r) { return $r->jsonSerialize(); },
        (new ManuscriptStructure($document, $type, $language))->evaluate(new Submission(fixture())));
}

test('DOCX seguro lê parágrafos, runs e hash sem extrair membros', function () {
    withDocx(docxParts('<w:p><w:r><w:t>Intro</w:t></w:r><w:r><w:t>dução</w:t></w:r></w:p>'), function ($path) {
        $doc = (new DocxReader())->read($path);
        same(true, $doc['available']); same('Introdução', $doc['paragraphs'][0]['text']);
        same(hash_file('sha256', $path), $doc['sha256']);
    });
});
foreach (['article', 'research_communication'] as $type) {
    test('seções completas sem exigir ordem ou fomento: ' . $type, function () use ($type) {
        withDocx(docxParts(docxBody(array_reverse(labels()))), function ($path) use ($type) {
            $checks = structure((new DocxReader())->read($path), $type);
            same('conforme', $checks[0]['status']); same('indeterminado', $checks[1]['status']);
            same($type === 'article' ? 3 : 2, count($checks));
            if ($type === 'article') { same('indeterminado', $checks[2]['status']); }
        });
    });
}
foreach (labels() as $missing) {
    test('ausência objetiva da seção ' . $missing, function () use ($missing) {
        $remaining = array_values(array_diff(labels(), [$missing]));
        withDocx(docxParts(docxBody($remaining)), function ($path) {
            same('não conforme', structure((new DocxReader())->read($path))[0]['status']);
        });
    });
}
test('menção em prosa não comprova seção e não prova ausência', function () {
    $labels = labels(); $labels[0] = 'A Introdução explica o problema sintético.';
    withDocx(docxParts(docxBody($labels)), function ($path) {
        same('indeterminado', structure((new DocxReader())->read($path))[0]['status']);
    });
});
test('Resultados e Discussão separados pedem confirmação da política', function () {
    $labels = labels(); $labels[2] = 'Resultados'; $labels[] = 'Discussão';
    withDocx(docxParts(docxBody($labels)), function ($path) {
        same('indeterminado', structure((new DocxReader())->read($path))[0]['status']);
    });
});
foreach (['en', 'es'] as $language) {
    test('traduções não aprovadas são indeterminadas: ' . $language, function () use ($language) {
        withDocx(docxParts(docxBody(labels())), function ($path) use ($language) {
            same('indeterminado', structure((new DocxReader())->read($path), 'article', $language)[0]['status']);
        });
    });
}
test('tipo não informado não é inferido do texto', function () {
    same('indeterminado', (new ManuscriptStructure(['available'=>true,'paragraphs'=>[]], null, 'pt'))
        ->evaluate(new Submission(fixture()))[0]->jsonSerialize()['status']);
    rejects(function () { new ManuscriptStructure(null, 'invented', 'pt'); });
    rejects(function () { new ManuscriptStructure(null, 'article', 'pt_BR'); });
});
foreach (['1. Introdução', '1.2 Introdução'] as $label) {
    test('numeração literal detectada separada da presença: ' . $label, function () use ($label) {
        $labels = labels(); $labels[0] = $label;
        withDocx(docxParts(docxBody($labels)), function ($path) {
            $checks = structure((new DocxReader())->read($path));
            same('conforme', $checks[0]['status']); same('não conforme', $checks[1]['status']);
        });
    });
}
foreach (['1' => true, '0' => false] as $id => $expected) {
    test('numeração OOXML e cancelamento ' . $id, function () use ($id, $expected) {
        $body = str_replace('<w:p>', '<w:p><w:pPr><w:numPr><w:numId w:val="' . $id . '"/></w:numPr></w:pPr>', docxBody(labels()));
        withDocx(docxParts($body), function ($path) use ($expected) {
            same($expected, structure((new DocxReader())->read($path))[1]['evidence']['explicit_numbering']);
        });
    });
}
foreach ([
    'traversal' => ['../escape.xml' => '<x/>'],
    'absolute' => ['/escape.xml' => '<x/>'],
    'backslash' => ['word\\escape.xml' => '<x/>'],
    'duplicate case' => ['WORD/document.xml' => '<x/>'],
    'DTD/XXE' => ['evil.xml' => '<!DOCTYPE x [<!ENTITY x SYSTEM "file:///etc/passwd">]><x>&x;</x>'],
    'external DTD' => ['evil.xml' => '<!DOCTYPE x SYSTEM "https://invalid.example/test"><x/>'],
    'malformed XML' => ['evil.xml' => '<x>'],
    'macro' => ['word/vbaProject.bin' => 'synthetic'],
    'embedded object' => ['word/embeddings/object.bin' => 'synthetic'],
    'zip bomb ratio' => ['padding.bin' => str_repeat('A', 1000000)],
    'entry size limit' => ['padding.bin' => str_repeat('x', DocxReader::MAX_PART + 1)],
    'utf16' => ['evil.xml' => "\xff\xfe<\0x\0/\0>\0"],
] as $name => $extra) {
    test('DOCX recusa ' . $name . ' sem falsa não conformidade', function () use ($extra) {
        withDocx(array_merge(docxParts(docxBody(labels())), $extra), function ($path) {
            $doc = (new DocxReader())->read($path);
            same(false, $doc['available']); same([], $doc['paragraphs']);
            same('indeterminado', structure($doc)[0]['status']);
        });
    });
}
foreach (['<w:tbl/>', '<w:altChunk/>', '<w:p><w:r><w:fldChar/></w:r></w:p>', '<w:sdt/>', '<w:p><w:ins/></w:p>', '<w:p><w:r><w:txbxContent/></w:r></w:p>', '<w:p><w:r><w:rPr><w:vanish/></w:rPr><w:t>Introdução</w:t></w:r></w:p>'] as $unsupported) {
    test('cobertura incompleta não vira ausência: ' . $unsupported, function () use ($unsupported) {
        withDocx(docxParts($unsupported), function ($path) {
            $doc = (new DocxReader())->read($path);
            same(false, $doc['available']); same('incomplete_coverage', $doc['reason']);
            same('indeterminado', structure($doc)[0]['status']);
        });
    });
}
test('ZIP com excesso de entradas', function () {
    $parts = docxParts('');
    for ($i = 0; $i < DocxReader::MAX_ENTRIES; $i++) { $parts['part' . $i] = 'x'; }
    withDocx($parts, function ($path) { same(false, (new DocxReader())->read($path)['available']); });
});
test('ZIP com symlink não é lido', function () {
    withDocx(docxParts(''), function ($path) { same(false, (new DocxReader())->read($path)['available']); }, function ($zip) {
        $zip->setExternalAttributesName('word/document.xml', 3, 0120777 << 16);
    });
});
test('pacote incompleto e conteúdo diferente de Word', function () {
    $parts = docxParts(''); unset($parts['_rels/.rels']); $parts['extra.xml'] = '<x/>';
    withDocx($parts, function ($path) { same(false, (new DocxReader())->read($path)['available']); });
    $parts = docxParts(''); $parts['[Content_Types].xml'] = '<Types/>';
    withDocx($parts, function ($path) { same(false, (new DocxReader())->read($path)['available']); });
});
test('DOCX com XML decomposto é indeterminado', function () {
    withDocx(docxParts(docxBody(["Introduc\u{0327}a\u{0303}o"])), function ($path) {
        same('unicode_coverage', (new DocxReader())->read($path)['reason']);
    });
});
test('DOCX corrompido e arquivo maior que limite', function () {
    withDocx(docxParts(''), function ($path) {
        file_put_contents($path, 'synthetic invalid zip');
        same(false, (new DocxReader())->read($path)['available']);
        file_put_contents($path, str_repeat('x', DocxReader::MAX_FILE + 1));
        same('file_limit', (new DocxReader())->read($path)['reason']);
    });
});
test('DOC e wrappers permanecem indeterminados, nunca buscados', function () {
    foreach (['https://invalid.example/file.docx', 'file:///tmp/file.docx', 'phar:///tmp/a.docx', '/nonexistent/synthetic.doc'] as $path) {
        same('unsupported_or_unavailable', (new DocxReader())->read($path)['reason']);
    }
});
test('CLI integra DOCX sem texto bruto nem caminho no relatório', function () {
    withDocx(docxParts(docxBody(array_merge(labels(), ['SYNTHETIC PRIVATE BODY']))), function ($path) {
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'article', 'pt']);
        same(0, $code); same('', $err);
        $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
        $checks = array_column($report['checks'], null, 'rule_id');
        same('conforme', $checks['manuscript.docx.structure']['status']);
        same(hash_file('sha256', $path), $checks['manuscript.docx.structure']['evidence']['document_sha256']);
        same(false, strpos($out, 'SYNTHETIC PRIVATE BODY') !== false);
        same(false, strpos($out, $path) !== false);
        list($code, $out) = cli([__DIR__ . '/fixtures/complete.json', $path, 'invented', 'pt']);
        same(1, $code); same('', $out);
    });
});
test('CLI falha de extração gera relatório indeterminado', function () {
    list($code, $out) = cli([__DIR__ . '/fixtures/complete.json', '/missing.doc', 'research_communication', 'pt']);
    same(0, $code);
    $checks = array_column(json_decode($out, true, 64, JSON_THROW_ON_ERROR)['checks'], null, 'rule_id');
    same('indeterminado', $checks['manuscript.docx.structure']['status']);
});

foreach (['' => null, '<w:b/>' => true, '<w:b w:val="0"/>' => false] as $property => $expected) {
    test('negrito direto ou herdado não resolvido: ' . $property, function () use ($property, $expected) {
        $body = str_replace('<w:r>', '<w:r><w:rPr>' . $property . '</w:rPr>', docxBody(labels()));
        withDocx(docxParts($body), function ($path) use ($expected) {
            $doc = (new DocxReader())->read($path);
            same($expected, $doc['paragraphs'][0]['direct_bold']);
            same($expected === false ? 'não conforme' : 'indeterminado', structure($doc)[1]['status']);
        });
    });
}
test('hiperlink externo é texto local; vínculo não é seguido', function () {
    $parts = docxParts('<w:p><w:hyperlink><w:r><w:t>Introdução</w:t></w:r></w:hyperlink></w:p>');
    $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId2" TargetMode="External" Target="https://invalid.example/never-follow" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink"/></Relationships>';
    withDocx($parts, function ($path) { same(true, (new DocxReader())->read($path)['available']); });
});
test('ZIP criptografado é indeterminado', function () {
    withDocx(docxParts(''), function ($path) {
        same(false, (new DocxReader())->read($path)['available']);
    }, function ($zip) { same(true, $zip->setEncryptionName('word/document.xml', ZipArchive::EM_AES_256, 'synthetic-password')); });
});
test('CRC divergente é indeterminado', function () {
    withDocx(docxParts(''), function ($path) {
        $bytes = file_get_contents($path);
        $offset = strpos($bytes, "PK\x01\x02");
        same(true, $offset !== false);
        $bytes = substr_replace($bytes, pack('V', 123), $offset + 16, 4);
        file_put_contents($path, $bytes);
        same(false, (new DocxReader())->read($path)['available']);
    });
});
