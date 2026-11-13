<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Count linked Word footnotes in a validated package, without reading/rendering their text. */
final class FootnoteInventory
{
    const MAX_DEFINITIONS = 2048;
    const MAX_REFERENCES = 4096;

    public static function read(array $parts): array
    {
        $x = self::xpath($parts['word/document.xml']);
        $references = $x->query('/w:document/w:body//w:footnoteReference');
        $known = true; $part = PackageRelationships::part($parts, 'footnotes', $known);
        $complete = $known; $reasons = []; $definitions = []; $normal = 0; $separators = 0;
        $samples = []; $seen = []; $duplicates = 0; $unused = 0; $endnotes = false;
        if (!$known) { $reasons[] = 'footnotes_relationship_unresolved'; }
        if (isset($parts['word/_rels/document.xml.rels'])) {
            $rx = new \DOMXPath($parts['word/_rels/document.xml.rels']);
            $rx->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $links = $rx->query('/r:Relationships/r:Relationship');
            if ($links->length > self::MAX_REFERENCES) { $complete = false; $reasons[] = 'relationship_inspection_limit'; }
            else {
                $ids = [];
                foreach ($links as $link) {
                    $id = $link->getAttribute('Id');
                    if ($id === '' || isset($ids[$id]) || !in_array($link->getAttribute('TargetMode'), ['', 'Internal', 'External'], true)) {
                        $complete = false; $reasons[] = 'invalid_relationship_graph';
                    }
                    $ids[$id] = true;
                }
            }
        }
        if ($x->query('/w:document/w:body//w:altChunk|/w:document/w:body//w:subDoc|/w:document/w:body//w:contentPart|/w:document/w:body//w:customXml|/w:document/w:body//w:sdt|/w:document/w:body//w:ins|/w:document/w:body//w:del|/w:document/w:body//w:moveFrom|/w:document/w:body//w:moveTo|/w:document/w:body//w:fldChar|/w:document/w:body//w:fldSimple|/w:document/w:body//w:instrText|/w:document/w:body//w:txbxContent|/w:document/w:body//w:footnoteRef')->length
            || $x->query('//*[namespace-uri()="http://schemas.openxmlformats.org/markup-compatibility/2006"]')->length) {
            $complete = false; $reasons[] = 'unsupported_containers';
        }
        if ($x->query('/w:document/w:body/*[not(self::w:p or self::w:tbl or self::w:sectPr)]')->length
            || $x->query('/w:document/w:body//*[namespace-uri()!="' . DocxReader::W . '" and not(ancestor::w:drawing or ancestor::w:pict or ancestor::w:object)]')->length) {
            $complete = false; $reasons[] = 'unrecognized_body_content';
        }
        foreach ($parts as $name => $xml) {
            $px = self::xpath($xml);
            $px->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $px->registerNamespace('c', 'http://schemas.openxmlformats.org/package/2006/content-types');
            if ($px->query('//w:endnotes|//w:endnote|//w:endnoteReference|//w:endnoteRef|/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/endnotes"]|/c:Types/c:Override[@ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml"]')->length) { $endnotes = true; }
            if ($px->query('//w:vanish|//w:webHidden')->length) { $complete = false; $reasons[] = 'hidden_content'; }
            if ($name !== 'word/document.xml' && $px->query('//w:footnoteReference')->length) {
                $complete = false; $reasons[] = 'references_outside_main_body';
            }
        }
        if ($x->query('//w:footnoteReference[not(ancestor::w:body)]')->length) {
            $complete = false; $reasons[] = 'references_outside_main_body';
        }
        $nodes = null;
        if ($part !== null) {
            $name = null;
            foreach ($parts as $partName => $xml) { if ($xml === $part) { $name = $partName; break; } }
            $types = new \DOMXPath($parts['[Content_Types].xml']);
            $types->registerNamespace('c', 'http://schemas.openxmlformats.org/package/2006/content-types');
            $matches = [];
            foreach ($types->query('/c:Types/c:Override') as $override) {
                if ($override->getAttribute('PartName') === '/' . $name) { $matches[] = $override->getAttribute('ContentType'); }
            }
            if ($matches !== ['application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml']) {
                $complete = false; $reasons[] = 'footnotes_content_type_unresolved';
            }
            $px = self::xpath($part);
            if ($px->query('/w:footnotes')->length !== 1 || $px->query('/w:footnotes/*[not(self::w:footnote)]')->length) {
                $complete = false; $reasons[] = 'unsupported_footnotes_part';
            }
            $nodes = $px->query('/w:footnotes/w:footnote');
        }
        $limit = $references->length > self::MAX_REFERENCES || ($nodes !== null && $nodes->length > self::MAX_DEFINITIONS);
        if ($limit) { $complete = false; $reasons[] = 'inspection_limit'; }
        if (!$limit && $nodes !== null) {
            foreach ($nodes as $index => $node) {
                $id = self::id($node->getAttributeNS(DocxReader::W, 'id'));
                $type = $node->getAttributeNS(DocxReader::W, 'type');
                if ($id === null || isset($definitions[$id]) || !in_array($type, ['', 'normal', 'separator', 'continuationSeparator', 'continuationNotice'], true)) {
                    $complete = false; $reasons[] = 'invalid_or_duplicate_definition'; continue;
                }
                $isNormal = $type === '' || $type === 'normal';
                $definitions[$id] = ['normal' => $isNormal, 'index' => $index];
                if ($isNormal) { $normal++; } else { $separators++; }
            }
        }
        if (!$limit) {
            foreach ($references as $index => $reference) {
                $id = self::id($reference->getAttributeNS(DocxReader::W, 'id'));
                $validLocation = $reference->parentNode->namespaceURI === DocxReader::W && $reference->parentNode->localName === 'r'
                    && $x->query('ancestor::w:p', $reference)->length > 0
                    && !$x->query('ancestor::w:pPr|ancestor::w:rPr|ancestor::w:tblPr|ancestor::w:trPr|ancestor::w:tcPr|ancestor::w:sectPr|ancestor::w:drawing|ancestor::w:pict|ancestor::w:object', $reference)->length;
                if (!$validLocation) { $complete = false; $reasons[] = 'unsupported_reference_location'; }
                $resolved = $validLocation && $id !== null && isset($definitions[$id]) && $definitions[$id]['normal'];
                if (!$resolved) { $complete = false; $reasons[] = 'unresolved_or_separator_reference'; }
                if ($id !== null) {
                    if (isset($seen[$id])) { $duplicates++; $complete = false; $reasons[] = 'repeated_reference'; }
                    $seen[$id] = true;
                }
                if (count($samples) < 32) {
                    $samples[] = ['reference_index' => $index, 'definition_index' => $resolved ? $definitions[$id]['index'] : null, 'resolved' => $resolved];
                }
            }
            foreach ($definitions as $id => $definition) {
                if ($definition['normal'] && !isset($seen[$id])) { $unused++; }
            }
            if ($unused) { $complete = false; $reasons[] = 'unreferenced_normal_definitions'; }
        }
        return ['available' => true, 'coverage_complete' => $complete, 'note_count' => $complete ? $normal : null,
            'endnote_indicators' => $endnotes, 'manual_notes_inspected' => false,
            'references_seen' => $references->length, 'normal_definitions' => $limit ? null : $normal,
            'separator_definitions' => $limit ? null : $separators, 'repeated_references' => $duplicates,
            'unreferenced_definitions' => $limit ? null : $unused, 'reasons' => array_values(array_unique($reasons)),
            'samples' => $samples, 'samples_omitted' => max(0, $references->length - count($samples))];
    }

    private static function id(string $value): ?string
    {
        return preg_match('/^-?[0-9]{1,9}$/D', $value) ? (string) (int) $value : null;
    }

    private static function xpath(\DOMDocument $document): \DOMXPath
    {
        $x = new \DOMXPath($document); $x->registerNamespace('w', DocxReader::W); return $x;
    }
}
