<?php
declare(strict_types=1);
use Estrabao\Document\DocxReader;
use Estrabao\Rules\VisualCount;

function simpleVisualTable(): string
{
    return '<w:tbl><w:tblGrid><w:gridCol w:w="2000"/><w:gridCol w:w="2000"/></w:tblGrid>'
        . '<w:tr><w:tc><w:p><w:r><w:t>Coluna A</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Coluna B</w:t></w:r></w:p></w:tc></w:tr>'
        . '<w:tr><w:tc><w:p><w:r><w:t>Dado A</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Dado B</w:t></w:r></w:p></w:tc></w:tr></w:tbl>';
}
function visualPicture(string $wrapper = 'inline', string $id = 'image1'): string
{
    return '<w:p><w:r><w:drawing xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<wp:' . $wrapper . '><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:blipFill><a:blip r:embed="' . $id . '"/></pic:blipFill></pic:pic></a:graphicData></a:graphic></wp:' . $wrapper . '>'
        . '</w:drawing></w:r></w:p>';
}
function visualCheck(array $document, ?string $type = 'research_communication'): array
{
    return (new VisualCount($document, $type))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize();
}
function visualDocxTest(array $parts, callable $body): void
{
    withDocx($parts, function ($path) use ($body) { $body((new DocxReader())->read($path), $path); });
}
foreach ([0 => 'conforme', 1 => 'conforme', 6 => 'conforme', 7 => 'não conforme'] as $count => $status) {
    test('limite conjunto com tabelas simples ' . $count, function () use ($count, $status) {
        visualDocxTest(docxParts(str_repeat(simpleVisualTable(), $count)), function ($document, $path) use ($count, $status) {
            $result = visualCheck($document); same($status, $result['status']);
            same($count, $result['evidence']['inventory']['simple_tables']);
            same($count, $result['evidence']['inventory']['maximum_count']);
            same(hash_file('sha256', $path), $result['evidence']['document_sha256']);
            same(6, $result['evidence']['maximum']);
            if ($count) { same(false, $document['available']); same('incomplete_coverage', $document['reason']); }
        });
    });
}
test('tabelas não liberam extração textual parcial para regras anteriores', function () {
    visualDocxTest(docxParts(docxBody(labels()) . simpleVisualTable()), function ($document) {
        same('conforme', visualCheck($document)['status']);
        same([], $document['paragraphs']); same(false, $document['available']);
        same('indeterminado', structure($document)[0]['status']);
        same('indeterminado', manuscriptLength($document)['status']);
    });
});
foreach (['article', null] as $type) {
    test('não impor limite a artigo ou tipo desconhecido ' . ($type ?? 'null'), function () use ($type) {
        visualDocxTest(docxParts(str_repeat(simpleVisualTable(), 7)), function ($document) use ($type) {
            $check = visualCheck($document, $type); same('indeterminado', $check['status']);
            same(null, $check['evidence']['maximum']); same(false, $check['evidence']['numeric_limit_published']);
        });
    });
}
test('contexto inválido é rejeitado', function () { rejects(function () { new VisualCount(null, 'invented'); }); });
foreach (['inline', 'anchor'] as $wrapper) {
    test('imagem candidata não é unidade editorial confirmada ' . $wrapper, function () use ($wrapper) {
        visualDocxTest(docxParts(visualPicture($wrapper)), function ($document) {
            $check = visualCheck($document); same('indeterminado', $check['status']);
            same(1, $check['evidence']['inventory']['drawing_instances']);
            same(1, $check['evidence']['inventory']['picture_instances']);
            same(null, $check['evidence']['inventory']['maximum_count']);
            same(true, $check['evidence']['inventory']['coverage_complete']);
        });
    });
}
test('sete inserções da mesma imagem não são sete figuras comprovadas', function () {
    visualDocxTest(docxParts(str_repeat(visualPicture(), 7)), function ($document) {
        same(7, $document['visuals']['picture_instances']);
        same('indeterminado', visualCheck($document)['status']);
    });
});
test('mistura de tabelas e imagem permanece indeterminada', function () {
    visualDocxTest(docxParts(str_repeat(simpleVisualTable(), 6) . visualPicture()), function ($document) {
        same(6, $document['visuals']['minimum_count']); same(null, $document['visuals']['maximum_count']);
        same('indeterminado', visualCheck($document)['status']);
    });
});
test('figura agrupada composta permanece indeterminada', function () {
    $drawing = '<w:p><w:r><w:drawing xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"><wpg:wgp><wpg:grpSpPr/></wpg:wgp></w:drawing></w:r></w:p>';
    visualDocxTest(docxParts($drawing), function ($document) {
        same(1, $document['visuals']['drawing_instances']); same('indeterminado', visualCheck($document)['status']);
    });
});
foreach (['<w:p><w:r><w:pict/></w:r></w:p>', '<w:p><w:r><w:object/></w:r></w:p>'] as $legacy) {
    test('objetos legados não são figuras confirmadas ' . $legacy, function () use ($legacy) {
        visualDocxTest(docxParts($legacy), function ($document) {
            same(1, $document['visuals']['legacy_objects']); same('indeterminado', visualCheck($document)['status']);
        });
    });
}
foreach ([
    'empty' => '<w:tbl/>',
    'single cell layout' => '<w:tbl><w:tblGrid><w:gridCol/></w:tblGrid><w:tr><w:tc><w:p><w:r><w:t>Layout</w:t></w:r></w:p></w:tc></w:tr></w:tbl>',
    'merged' => str_replace('<w:tc>', '<w:tc><w:tcPr><w:gridSpan w:val="2"/></w:tcPr>', simpleVisualTable()),
    'floating' => str_replace('<w:tbl>', '<w:tbl><w:tblPr><w:tblpPr/></w:tblPr>', simpleVisualTable()),
    'nested' => str_replace('<w:tc><w:p>', '<w:tc>' . simpleVisualTable() . '<w:p>', simpleVisualTable()),
    'picture inside table' => str_replace('<w:tc><w:p>', '<w:tc>' . visualPicture() . '<w:p>', simpleVisualTable()),
    'revised' => str_replace('<w:tbl>', '<w:tbl><w:tblPr><w:tblPrChange/></w:tblPr>', simpleVisualTable()),
] as $name => $table) {
    test('tabela ambígua ' . $name, function () use ($table) {
        visualDocxTest(docxParts($table), function ($document) {
            same(0, $document['visuals']['simple_tables']);
            same(true, $document['visuals']['ambiguous_tables'] > 0);
            same('indeterminado', visualCheck($document)['status']);
        });
    });
}
foreach (['<w:sdt/>', '<w:altChunk/>', '<w:ins/>', '<w:del/>', '<w:p><w:r><w:fldChar/></w:r></w:p>', '<w:p><w:r><w:subDoc/></w:r></w:p>', '<w:p><w:r><w:footnoteReference/></w:r></w:p>'] as $content) {
    test('cobertura incompleta nunca prova limite ' . $content, function () use ($content) {
        visualDocxTest(docxParts(str_repeat(simpleVisualTable(), 7) . $content), function ($document) {
            same(false, $document['visuals']['coverage_complete']);
            same('indeterminado', visualCheck($document)['status']);
        });
    });
}
test('mídias não inseridas não são figuras', function () {
    $parts = docxParts(docxBody(['Figura 1. Legenda sintética sem objeto']));
    for ($i = 0; $i < 7; $i++) { $parts['word/media/unused' . $i . '.png'] = 'synthetic bytes, not decoded'; }
    visualDocxTest($parts, function ($document) { same(0, $document['visuals']['picture_instances']); same('conforme', visualCheck($document)['status']); });
});
test('relacionamento externo de imagem nunca é seguido nem validado como figura', function () {
    $parts = docxParts(visualPicture());
    $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="image1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" TargetMode="External" Target="https://invalid.example/private-image.png"/></Relationships>';
    visualDocxTest($parts, function ($document) { same('indeterminado', visualCheck($document)['status']); });
});
test('visual em outra parte impede aprovação do inventário do corpo', function () {
    $parts = docxParts(''); $parts['word/header1.xml'] = '<w:hdr xmlns:w="' . DocxReader::W . '">' . visualPicture() . '</w:hdr>';
    visualDocxTest($parts, function ($document) {
        same(false, $document['visuals']['coverage_complete']); same('indeterminado', visualCheck($document)['status']);
    });
});
test('referência de cabeçalho não inspecionada impede conformidade', function () {
    visualDocxTest(docxParts('<w:sectPr><w:headerReference/></w:sectPr>'), function ($document) { same('indeterminado', visualCheck($document)['status']); });
});
test('revisões e texto oculto não são contados como tabelas presentes', function () {
    $parts = docxParts('<w:del>' . simpleVisualTable() . '</w:del>');
    visualDocxTest($parts, function ($document) { same(0, $document['visuals']['simple_tables']); same('indeterminado', visualCheck($document)['status']); });
    $parts = docxParts('<w:p><w:r><w:rPr><w:vanish/></w:rPr><w:t>oculto</w:t></w:r></w:p>');
    visualDocxTest($parts, function ($document) { same(false, $document['visuals']['coverage_complete']); same('indeterminado', visualCheck($document)['status']); });
});
test('falha de pacote não deixa inventário aproveitável', function () {
    $parts = docxParts(simpleVisualTable()); $parts['word/bad.xml'] = '<!DOCTYPE x [<!ENTITY y SYSTEM "file:///etc/passwd">]><x>&y;</x>';
    visualDocxTest($parts, function ($document) {
        same(false, $document['available']); same(false, isset($document['visuals'])); same('indeterminado', visualCheck($document)['status']);
    });
});
test('arquivo ausente e tipo DOC não têm inventário disponível', function () {
    foreach (['/missing.doc', 'https://invalid.example/manuscript.docx'] as $path) {
        $document = (new DocxReader())->read($path); same('indeterminado', visualCheck($document)['status']);
    }
    same('indeterminado', (new VisualCount())->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize()['status']);
});
foreach ([6 => 'conforme', 7 => 'não conforme'] as $count => $status) {
    test('CLI aplica limite a comunicação com tabelas ' . $count, function () use ($count, $status) {
        visualDocxTest(docxParts(str_repeat(simpleVisualTable(), $count)), function ($document, $path) use ($status) {
            list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'research_communication', 'pt']);
            same(0, $code); same('', $err);
            $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
            same('0.10.0', $report['engine_version']);
            same($status, array_column($report['checks'], null, 'rule_id')['manuscript.visuals.count']['status']);
            same(false, strpos($out, 'Coluna A') !== false); same(false, strpos($out, $path) !== false);
        });
    });
}
test('evidência limita amostras sem copiar texto ou nomes de mídia', function () {
    $parts = docxParts(str_repeat(simpleVisualTable(), 40));
    $parts['word/document.xml'] = str_replace('Coluna A', 'SYNTHETIC PRIVATE CONTENT', $parts['word/document.xml']);
    visualDocxTest($parts, function ($document) {
        $check = visualCheck($document); same(32, count($check['evidence']['inventory']['samples']));
        same(8, $check['evidence']['inventory']['samples_omitted']);
        same(false, strpos(json_encode($check), 'SYNTHETIC PRIVATE CONTENT') !== false);
    });
});
test('sete tabelas simples comprovam excesso mesmo com imagem incerta', function () {
    visualDocxTest(docxParts(str_repeat(simpleVisualTable(), 7) . visualPicture()), function ($document) {
        same('não conforme', visualCheck($document)['status']);
    });
});
test('fundo visual não inspecionado impede aprovação', function () {
    $parts = docxParts(''); $parts['word/document.xml'] = str_replace('<w:body>', '<w:background/><w:body>', $parts['word/document.xml']);
    visualDocxTest($parts, function ($document) { same('indeterminado', visualCheck($document)['status']); });
});
test('inventário limita inspeção de milhares de tabelas', function () {
    withDocx(docxParts(str_repeat(simpleVisualTable(), \Estrabao\Document\VisualInventory::MAX_TABLES + 1)), function ($path) {
        $document = (new DocxReader())->read($path);
        same(false, $document['visuals']['coverage_complete']);
        same(true, in_array('table_inspection_limit', $document['visuals']['reasons'], true));
        same('indeterminado', visualCheck($document)['status']);
    }, function ($zip) { for ($i = 0; $i < $zip->numFiles; $i++) { $zip->setCompressionIndex($i, ZipArchive::CM_STORE); } });
});
test('inventário estrutural não depende da tradução de títulos do corpo', function () {
    visualDocxTest(docxParts(str_repeat(simpleVisualTable(), 7)), function ($document, $path) {
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json', $path, 'research_communication', 'es']);
        same(0, $code); same('', $err);
        $checks = array_column(json_decode($out, true, 64, JSON_THROW_ON_ERROR)['checks'], null, 'rule_id');
        same('não conforme', $checks['manuscript.visuals.count']['status']);
        same('indeterminado', $checks['manuscript.docx.structure']['status']);
    });
});
test('evidência visual registra posição sem expor propriedades da imagem', function () {
    $parts = docxParts(docxBody(['Texto sintético']) . visualPicture('inline', 'PRIVATE_RELATIONSHIP_ID'));
    visualDocxTest($parts, function ($document) {
        $check = visualCheck($document);
        same(1, $check['evidence']['inventory']['visual_samples'][0]['body_child_index']);
        same(false, strpos(json_encode($check), 'PRIVATE_RELATIONSHIP_ID') !== false);
    });
});
