<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Independent inventory inside an already validated ZIP/XML package. Never decodes images. */
final class VisualInventory
{
    const MAX_TABLES = 1024;

    public static function read(array $parts): array
    {
        $x = new \DOMXPath($parts['word/document.xml']);
        $x->registerNamespace('w', DocxReader::W);
        $x->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
        $body = $x->query('/w:document/w:body')->item(0);
        $tables = $x->query('.//w:tbl', $body);
        $drawings = $x->query('.//w:drawing', $body)->length;
        $pictures = $x->query('.//pic:pic', $body)->length;
        $legacy = $x->query('.//w:pict|.//w:object', $body)->length;
        $reasons = []; $complete = true;
        if ($tables->length > self::MAX_TABLES) { $complete = false; $reasons[] = 'table_inspection_limit'; }
        if ($x->query('.//w:altChunk|.//w:subDoc|.//w:contentPart|.//w:customXml|.//w:sdt|.//w:ins|.//w:del|.//w:moveFrom|.//w:moveTo|.//w:fldChar|.//w:fldSimple|.//w:instrText|.//w:txbxContent|.//w:sym|.//w:footnoteReference|.//w:endnoteReference|.//w:headerReference|.//w:footerReference', $body)->length
            || $x->query('//*[namespace-uri()="http://schemas.openxmlformats.org/markup-compatibility/2006"]')->length) {
            $complete = false; $reasons[] = 'unsupported_containers_or_stories';
        }
        if ($x->query('/w:document/w:body/*[not(self::w:p or self::w:tbl or self::w:sectPr)]')->length
            || $x->query('/w:document/w:body//w:p//*[not(ancestor::w:drawing or ancestor::w:pict or ancestor::w:object or ancestor::w:pPr or ancestor::w:rPr) and not(self::w:r or self::w:t or self::w:tab or self::w:br or self::w:cr or self::w:pPr or self::w:rPr or self::w:hyperlink or self::w:bookmarkStart or self::w:bookmarkEnd or self::w:proofErr or self::w:drawing or self::w:pict or self::w:object)]')->length
            || $x->query('/w:document/w:body//*[namespace-uri()!="' . DocxReader::W . '" and not(ancestor::w:drawing or ancestor::w:pict or ancestor::w:object)]')->length) {
            $complete = false; $reasons[] = 'unrecognized_body_content';
        }
        if ($x->query('//w:tbl[not(ancestor::w:body)]|//w:drawing[not(ancestor::w:body)]|//w:pict[not(ancestor::w:body)]|//w:object[not(ancestor::w:body)]|/w:document/w:background')->length) {
            $complete = false; $reasons[] = 'visuals_outside_main_body';
        }
        foreach ($parts as $name => $part) {
            $px = new \DOMXPath($part); $px->registerNamespace('w', DocxReader::W);
            if ($px->query('//w:vanish|//w:webHidden')->length) { $complete = false; $reasons[] = 'hidden_content'; }
            if ($name !== 'word/document.xml' && $px->query('//w:tbl|//w:drawing|//w:pict|//w:object|//w:altChunk')->length) {
                $complete = false; $reasons[] = 'visuals_outside_main_body';
            }
        }
        $simple = 0; $ambiguous = 0; $samples = [];
        if ($tables->length <= self::MAX_TABLES) {
            foreach ($tables as $index => $table) {
                $isSimple = self::simpleTable($x, $table);
                if ($isSimple) { $simple++; } else { $ambiguous++; }
                if (count($samples) < 32) { $samples[] = ['table_index' => $index, 'body_child_index' => self::bodyIndex($table, $body), 'classification' => $isSimple ? 'simple_word_table' : 'ambiguous_table']; }
            }
        }
        if ($drawings || $pictures || $legacy) { $reasons[] = 'editorial_figure_units_unresolved'; }
        if ($ambiguous) { $reasons[] = 'ambiguous_tables'; }
        $visualSamples = [];
        foreach ($x->query('.//w:drawing|.//w:pict|.//w:object', $body) as $index => $visual) {
            if (count($visualSamples) >= 32) { break; }
            $visualSamples[] = ['instance_index' => $index, 'kind' => $visual->localName,
                'body_child_index' => self::bodyIndex($visual, $body)];
        }
        $known = $complete && !$drawings && !$pictures && !$legacy && !$ambiguous;
        return ['available' => true, 'coverage_complete' => $complete,
            'simple_tables' => $simple, 'table_candidates' => $tables->length, 'ambiguous_tables' => $ambiguous,
            'drawing_instances' => $drawings, 'picture_instances' => $pictures, 'legacy_objects' => $legacy,
            'minimum_count' => $simple, 'maximum_count' => $known ? $simple : null,
            'counting_units_resolved' => $known, 'reasons' => array_values(array_unique($reasons)),
            'visual_samples' => $visualSamples, 'visual_samples_omitted' => max(0, $drawings + $legacy - count($visualSamples)),
            'samples' => $samples, 'samples_omitted' => max(0, $tables->length - count($samples))];
    }

    private static function bodyIndex(\DOMNode $node, \DOMNode $body): int
    {
        while ($node->parentNode !== $body) { $node = $node->parentNode; }
        $index = 0;
        while ($node->previousSibling !== null) {
            $node = $node->previousSibling;
            if ($node instanceof \DOMElement) { $index++; }
        }
        return $index;
    }

    public static function simpleTable(\DOMXPath $x, \DOMElement $table): bool
    {
        // Count only top-level, rectangular, textual tables. Layout/nested/merged tables need review.
        if ($table->parentNode->namespaceURI !== DocxReader::W || $table->parentNode->localName !== 'body'
            || $x->query('.//w:tbl|.//w:drawing|.//w:pict|.//w:object|.//w:sdt|.//w:ins|.//w:del|.//w:tblpPr|.//w:gridSpan|.//w:vMerge|.//w:hMerge|.//w:gridBefore|.//w:gridAfter|.//w:tblPrChange|.//w:trPrChange|.//w:tcPrChange|.//w:tblGridChange|.//w:cellIns|.//w:cellDel|.//w:cellMerge', $table)->length
            || $x->query('.//*[namespace-uri()!="' . DocxReader::W . '"]', $table)->length
            || $x->query('./*[not(self::w:tblPr or self::w:tblGrid or self::w:tr)]', $table)->length
            || $x->query('./w:tblGrid', $table)->length !== 1) { return false; }
        $columns = $x->query('./w:tblGrid/w:gridCol', $table)->length;
        $rows = $x->query('./w:tr', $table);
        if ($columns < 2 || $rows->length < 2) { return false; }
        $content = false;
        foreach ($rows as $row) {
            if ($x->query('./*[not(self::w:trPr or self::w:tc)]', $row)->length || $x->query('./w:tc', $row)->length !== $columns) { return false; }
            foreach ($x->query('./w:tc', $row) as $cell) {
                if ($x->query('./*[not(self::w:tcPr or self::w:p)]', $cell)->length || !$x->query('./w:p', $cell)->length) { return false; }
                foreach ($x->query('./w:p//w:t', $cell) as $text) {
                    if (preg_match('/[^\s\p{Z}]/u', $text->textContent)) { $content = true; }
                }
            }
        }
        return $content;
    }
}
