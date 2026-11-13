<?php
declare(strict_types=1);
use Estrabao\Document\DocxReader;
use Estrabao\Rules\Footnotes;

function footnoteParts(int $count, bool $separators = true): array
{
    $references = ''; $notes = '';
    if ($separators) {
        $notes = '<w:footnote w:type="separator" w:id="-1"><w:p><w:r><w:separator/></w:r></w:p></w:footnote>'
            . '<w:footnote w:type="continuationSeparator" w:id="0"><w:p><w:r><w:continuationSeparator/></w:r></w:p></w:footnote>';
    }
    for ($i = 1; $i <= $count; $i++) {
        $references .= '<w:r><w:footnoteReference w:id="' . $i . '"/></w:r>';
        $notes .= '<w:footnote w:id="' . $i . '"><w:p><w:r><w:footnoteRef/><w:t>Nota sintética privada ' . $i . '</w:t></w:r></w:p></w:footnote>';
    }
    $parts = docxParts('<w:p><w:r><w:t>Texto sintético</w:t></w:r>' . $references . '</w:p>');
    $parts['word/footnotes.xml'] = '<w:footnotes xmlns:w="' . DocxReader::W . '">' . $notes . '</w:footnotes>';
    $parts['word/_rels/document.xml.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="fn" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footnotes" Target="footnotes.xml"/></Relationships>';
    $parts['[Content_Types].xml'] = str_replace('</Types>', '<Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/></Types>', $parts['[Content_Types].xml']);
    return $parts;
}
function footnoteChecks(array $document): array
{
    return array_map(function ($check) { return $check->jsonSerialize(); }, (new Footnotes($document))->evaluate(new \Estrabao\Contracts\Submission(fixture())));
}
function footnoteDocx(array $parts, callable $body, ?callable $modify = null): void
{
    withDocx($parts, function ($path) use ($body) { $body((new DocxReader())->read($path), $path); }, $modify);
}
foreach ([0 => 'conforme', 1 => 'conforme', 3 => 'conforme', 4 => 'não conforme'] as $count => $status) {
    test('limite de notas de rodapé ' . $count, function () use ($count, $status) {
        footnoteDocx(footnoteParts($count), function ($document, $path) use ($count, $status) {
            $checks = footnoteChecks($document); same($status, $checks[0]['status']);
            same($count, $checks[0]['evidence']['inventory']['note_count']);
            same(2, $checks[0]['evidence']['inventory']['separator_definitions']);
            same(hash_file('sha256', $path), $checks[0]['evidence']['document_sha256']);
            same($count === 0 ? 'conforme' : 'indeterminado', $checks[1]['status']);
            same(false, $checks[1]['evidence']['renderer_available']);
            if ($count) { same(false, $document['available']); same([], $document['paragraphs']); }
        });
    });
}
test('documento sem chamadas nem parte de notas confirma zero', function () {
    footnoteDocx(docxParts(docxBody(labels())), function ($document) {
        same(true, $document['available']); same(0, $document['footnotes']['note_count']);
        foreach (footnoteChecks($document) as $check) { same('conforme', $check['status']); }
    });
});
test('separadores não precisam de IDs fixos para serem excluídos', function () {
    $parts = footnoteParts(3); $parts['word/footnotes.xml'] = str_replace(['w:id="-1"', 'w:id="0"'], ['w:id="-99"', 'w:id="-98"'], $parts['word/footnotes.xml']);
    footnoteDocx($parts, function ($document) { same(3, $document['footnotes']['note_count']); });
});
test('tipos normal e continuationNotice são tratados pelo tipo', function () {
    $parts = footnoteParts(1, false);
    $parts['word/footnotes.xml'] = str_replace('<w:footnote w:id="1">', '<w:footnote w:type="normal" w:id="1">', $parts['word/footnotes.xml']);
    $parts['word/footnotes.xml'] = str_replace('</w:footnotes>', '<w:footnote w:type="continuationNotice" w:id="0"><w:p/></w:footnote></w:footnotes>', $parts['word/footnotes.xml']);
    footnoteDocx($parts, function ($document) { same(1, $document['footnotes']['note_count']); same(1, $document['footnotes']['separator_definitions']); });
});
test('duas regras não aprovam texto parcial de documento com notas', function () {
    footnoteDocx(footnoteParts(3), function ($document) {
        same('conforme', footnoteChecks($document)[0]['status']);
        same('indeterminado', manuscriptLength($document)['status']);
        same('indeterminado', structure($document)[0]['status']);
        same('indeterminado', visualCheck($document)['status']);
    });
});
foreach ([
    'missing part', 'orphan part', 'external relationship', 'duplicate relationship', 'wrong relationship target',
    'wrong root', 'missing content type', 'wrong content type', 'duplicate content type',
    'missing reference id', 'missing definition id', 'unknown reference', 'separator reference',
    'duplicate definition', 'repeated reference', 'unreferenced definition', 'unknown note type',
] as $kind) {
    test('vínculo de nota inconclusivo: ' . $kind, function () use ($kind) {
        $parts = footnoteParts(1);
        $rels = 'word/_rels/document.xml.rels'; $notes = 'word/footnotes.xml'; $doc = 'word/document.xml'; $types = '[Content_Types].xml';
        if ($kind === 'missing part') { unset($parts[$notes]); }
        elseif ($kind === 'orphan part') { unset($parts[$rels]); }
        elseif ($kind === 'external relationship') { $parts[$rels] = str_replace('Target="footnotes.xml"', 'TargetMode="External" Target="https://invalid.example/footnotes.xml"', $parts[$rels]); }
        elseif ($kind === 'duplicate relationship') { $parts[$rels] = str_replace('</Relationships>', '<Relationship Id="other" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footnotes" Target="footnotes.xml"/></Relationships>', $parts[$rels]); }
        elseif ($kind === 'wrong relationship target') { $parts[$rels] = str_replace('footnotes.xml', 'document.xml', $parts[$rels]); }
        elseif ($kind === 'wrong root') { $parts[$notes] = '<w:document xmlns:w="' . DocxReader::W . '"/>'; }
        elseif ($kind === 'missing content type') { $parts[$types] = docxParts('')[$types]; }
        elseif ($kind === 'wrong content type') { $parts[$types] = str_replace('wordprocessingml.footnotes+xml', 'wordprocessingml.styles+xml', $parts[$types]); }
        elseif ($kind === 'duplicate content type') { $parts[$types] = str_replace('</Types>', '<Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/></Types>', $parts[$types]); }
        elseif ($kind === 'missing reference id') { $parts[$doc] = str_replace(' w:id="1"', '', $parts[$doc]); }
        elseif ($kind === 'missing definition id') { $parts[$notes] = str_replace(' w:id="1"', '', $parts[$notes]); }
        elseif ($kind === 'unknown reference') { $parts[$doc] = str_replace('w:id="1"', 'w:id="2"', $parts[$doc]); }
        elseif ($kind === 'separator reference') { $parts[$doc] = str_replace('w:id="1"', 'w:id="0"', $parts[$doc]); }
        elseif ($kind === 'duplicate definition') { $parts[$notes] = str_replace('</w:footnotes>', '<w:footnote w:id="1"><w:p/></w:footnote></w:footnotes>', $parts[$notes]); }
        elseif ($kind === 'repeated reference') { $parts[$doc] = str_replace('</w:p>', '<w:r><w:footnoteReference w:id="1"/></w:r></w:p>', $parts[$doc]); }
        elseif ($kind === 'unreferenced definition') { $parts[$doc] = str_replace('<w:footnoteReference w:id="1"/>', '', $parts[$doc]); }
        else { $parts[$notes] = str_replace('<w:footnote w:id="1">', '<w:footnote w:type="invented" w:id="1">', $parts[$notes]); }
        footnoteDocx($parts, function ($document) {
            $checks = footnoteChecks($document); same('indeterminado', $checks[0]['status']); same('indeterminado', $checks[1]['status']);
            same(false, $document['footnotes']['coverage_complete']); same(null, $document['footnotes']['note_count']);
        });
    });
}
test('caminho local alternativo da parte de notas é resolvido sem rede', function () {
    $parts = footnoteParts(3);
    $parts['custom/notes.xml'] = $parts['word/footnotes.xml']; unset($parts['word/footnotes.xml']);
    $parts['word/_rels/document.xml.rels'] = str_replace('Target="footnotes.xml"', 'Target="../custom/notes.xml"', $parts['word/_rels/document.xml.rels']);
    $parts['[Content_Types].xml'] = str_replace('/word/footnotes.xml', '/custom/notes.xml', $parts['[Content_Types].xml']);
    footnoteDocx($parts, function ($document) { same(3, $document['footnotes']['note_count']); });
});
foreach (['<w:sdt/>', '<w:altChunk/>', '<w:ins/>', '<w:del/>', '<w:p><w:r><w:fldChar/></w:r></w:p>', '<w:p><w:r><w:subDoc/></w:r></w:p>'] as $unsupported) {
    test('cobertura incompleta não prova excesso de notas ' . $unsupported, function () use ($unsupported) {
        $parts = footnoteParts(4); $parts['word/document.xml'] = str_replace('</w:body>', $unsupported . '</w:body>', $parts['word/document.xml']);
        footnoteDocx($parts, function ($document) { same('indeterminado', footnoteChecks($document)[0]['status']); });
    });
}
test('chamada em cabeçalho não se confunde com cobertura do corpo', function () {
    $parts = footnoteParts(1);
    $parts['word/header.xml'] = '<w:hdr xmlns:w="' . DocxReader::W . '"><w:p><w:r><w:footnoteReference w:id="1"/></w:r></w:p></w:hdr>';
    footnoteDocx($parts, function ($document) { same('indeterminado', footnoteChecks($document)[0]['status']); });
});
test('texto oculto impede contagem conclusiva', function () {
    $parts = footnoteParts(1); $parts['word/document.xml'] = str_replace('<w:r>', '<w:r><w:rPr><w:vanish/></w:rPr>', $parts['word/document.xml']);
    footnoteDocx($parts, function ($document) { same('indeterminado', footnoteChecks($document)[0]['status']); });
});
test('notas em tabela podem ser contadas sem liberar leitura textual', function () {
    $parts = footnoteParts(1);
    $parts['word/document.xml'] = str_replace('</w:body>', simpleVisualTable() . '</w:body>', $parts['word/document.xml']);
    footnoteDocx($parts, function ($document) { same(false, $document['available']); same(1, $document['footnotes']['note_count']); });
});
test('quebras explícitas e tamanho do texto não são contagem visual de linhas', function () {
    foreach (['<w:br/><w:br/><w:br/>', '</w:r></w:p><w:p><w:r><w:t>Outra parte</w:t>', '<w:t>' . str_repeat('Texto sintético ', 100) . '</w:t>'] as $extra) {
        $parts = footnoteParts(1); $parts['word/footnotes.xml'] = str_replace('</w:t>', '</w:t>' . $extra, $parts['word/footnotes.xml']);
        footnoteDocx($parts, function ($document) { same('indeterminado', footnoteChecks($document)[1]['status']); });
    }
});
test('endnotes não são convertidas em notas de rodapé', function () {
    $parts = docxParts('<w:p><w:r><w:endnoteReference w:id="1"/></w:r></w:p>');
    $parts['word/endnotes.xml'] = '<w:endnotes xmlns:w="' . DocxReader::W . '"><w:endnote w:id="1"><w:p><w:r><w:t>Nota de fim</w:t></w:r></w:p></w:endnote></w:endnotes>';
    footnoteDocx($parts, function ($document) { same(0, $document['footnotes']['note_count']); same('conforme', footnoteChecks($document)[0]['status']); });
});
test('pacote inválido não deixa inventário de notas aproveitável', function () {
    $parts = footnoteParts(4); $parts['word/footnotes.xml'] = '<!DOCTYPE x [<!ENTITY y SYSTEM "file:///etc/passwd">]><x>&y;</x>';
    footnoteDocx($parts, function ($document) {
        same(false, isset($document['footnotes'])); same('indeterminado', footnoteChecks($document)[0]['status']);
    });
});
test('fonte ausente ou DOC sem parser não comprova zero notas', function () {
    foreach ([null, (new DocxReader())->read('/missing.doc'), ['available' => false]] as $document) {
        $checks = (new Footnotes($document))->evaluate(new \Estrabao\Contracts\Submission(fixture()));
        foreach ($checks as $check) { same('indeterminado', $check->jsonSerialize()['status']); }
    }
});
foreach ([1 => 'conforme', 4 => 'não conforme'] as $count => $status) {
    test('CLI integra notas e preserva sigilo ' . $count, function () use ($count, $status) {
        footnoteDocx(footnoteParts($count), function ($document, $path) use ($status) {
            list($code, $out, $err) = cli([__DIR__ . '/fixtures/with-subtitles.json', $path, 'article', 'pt']);
            same(0, $code); same('', $err);
            $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR); same('0.14.0', $report['engine_version']);
            $checks = array_column($report['checks'], null, 'rule_id');
            same($status, $checks['manuscript.footnotes.count']['status']);
            same('indeterminado', $checks['manuscript.footnotes.lines']['status']);
            same(false, strpos($out, 'Nota sintética privada') !== false); same(false, strpos($out, $path) !== false);
        });
    });
}
test('limite técnico impede inspeção excessiva de definições', function () {
    footnoteDocx(footnoteParts(\Estrabao\Document\FootnoteInventory::MAX_DEFINITIONS + 1, false), function ($document) {
        same(false, $document['footnotes']['coverage_complete']);
        same(true, in_array('inspection_limit', $document['footnotes']['reasons'], true));
        same('indeterminado', footnoteChecks($document)[0]['status']);
    }, function ($zip) { for ($i = 0; $i < $zip->numFiles; $i++) { $zip->setCompressionIndex($i, ZipArchive::CM_STORE); } });
});
test('amostras de vínculo têm tamanho limitado sem expor IDs ou conteúdo', function () {
    footnoteDocx(footnoteParts(40), function ($document) {
        $result = footnoteChecks($document)[0]; same('não conforme', $result['status']);
        same(32, count($result['evidence']['inventory']['samples'])); same(8, $result['evidence']['inventory']['samples_omitted']);
        same(false, strpos(json_encode($result), 'Nota sintética privada') !== false);
    });
});
test('chamada fora de um run textual não comprova nota renderizável', function () {
    $parts = footnoteParts(4); $parts['word/document.xml'] = str_replace('<w:r><w:footnoteReference', '<w:rPr><w:footnoteReference', $parts['word/document.xml']);
    $parts['word/document.xml'] = str_replace('/></w:r>', '/></w:rPr>', $parts['word/document.xml']);
    footnoteDocx($parts, function ($document) {
        same(true, in_array('unsupported_reference_location', $document['footnotes']['reasons'], true));
        same('indeterminado', footnoteChecks($document)[0]['status']);
    });
});
test('container estrangeiro não inspecionado impede prova de zero notas', function () {
    $parts = docxParts('<x:container xmlns:x="urn:unsupported"/>');
    footnoteDocx($parts, function ($document) { same('indeterminado', footnoteChecks($document)[0]['status']); });
});
test('separadores sozinhos e tipos desconhecidos não viram quatro notas', function () {
    $parts = footnoteParts(0);
    $parts['word/footnotes.xml'] = str_replace('</w:footnotes>', '<w:footnote w:type="continuationNotice" w:id="-2"><w:p/></w:footnote><w:footnote w:type="separator" w:id="-3"><w:p/></w:footnote></w:footnotes>', $parts['word/footnotes.xml']);
    footnoteDocx($parts, function ($document) { same(0, $document['footnotes']['note_count']); same(4, $document['footnotes']['separator_definitions']); });
});
test('ID de relacionamento duplicado entre tipos deixa grafo inconclusivo', function () {
    $parts = footnoteParts(4);
    $parts['word/_rels/document.xml.rels'] = str_replace('</Relationships>', '<Relationship Id="fn" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" TargetMode="External" Target="https://invalid.example/never-follow"/></Relationships>', $parts['word/_rels/document.xml.rels']);
    footnoteDocx($parts, function ($document) {
        same(true, in_array('invalid_relationship_graph', $document['footnotes']['reasons'], true));
        same('indeterminado', footnoteChecks($document)[0]['status']);
    });
});
