<?php
declare(strict_types=1);
namespace Estrabao\Document;

final class PageLayout
{
    public static function read(array $parts): array
    {
        $document = $parts['word/document.xml'];
        $x = new \DOMXPath($document); $x->registerNamespace('w', DocxReader::W);
        $known = true; $settings = PackageRelationships::part($parts, 'settings', $known);
        $specialMargins = !$known; $grid = false;
        if ($settings !== null) {
            $sx = new \DOMXPath($settings); $sx->registerNamespace('w', DocxReader::W);
            if ($sx->query('/w:settings')->length !== 1
                || $sx->query('//w:mirrorMargins|//w:gutterAtTop|//w:bookFoldPrinting|//w:bookFoldRevPrinting')->length) { $specialMargins = true; }
        }
        $sections = []; $size = null; $margins = null;
        $nodes = $x->query('/w:document/w:body/w:p/w:pPr/w:sectPr | /w:document/w:body/w:sectPr');
        // The final body sectPr defines the last section; without it, coverage is incomplete.
        $complete = $x->query('/w:document/w:body/w:sectPr')->length === 1
            && $x->query('/w:document/w:body/*[last()][self::w:sectPr]')->length === 1;
        foreach ($nodes as $index => $section) {
            $valid = $x->query('./w:sectPrChange|.//w:sectPrChange', $section)->length === 0;
            if ($section->parentNode->localName === 'body') {
                $valid = $valid && $x->query('/w:document/w:body/w:sectPr')->length === 1
                    && $x->query('/w:document/w:body/*[last()][self::w:sectPr]')->length === 1;
            } elseif ($x->query('./w:sectPr', $section->parentNode)->length !== 1) { $valid = false; }
            foreach (['pgSz' => ['w', 'h'], 'pgMar' => ['top', 'right', 'bottom', 'left', 'gutter']] as $element => $attributes) {
                $properties = $x->query('./w:' . $element, $section);
                if ($properties->length > 1) { $valid = false; }
                if ($properties->length) {
                    $values = [];
                    foreach ($attributes as $attribute) { $values[$attribute] = StyleResolver::measure($properties->item(0)->getAttributeNS(DocxReader::W, $attribute)); }
                    if ($element === 'pgSz') {
                        $orientation = $properties->item(0)->getAttributeNS(DocxReader::W, 'orient');
                        if (!in_array($orientation, ['', 'portrait', 'landscape'], true)) { $valid = false; }
                        $size = $values;
                    } else { $margins = $values; }
                }
            }
            if ($x->query('./w:docGrid[not(@w:type="default")]', $section)->length) { $grid = true; }
            $sections[] = ['section_index' => $index, 'valid' => $valid, 'size_twips' => $size,
                'margins_twips' => $margins, 'special_margins' => $specialMargins];
            // Invalid section properties must not become inherited evidence for later sections.
            if (!$valid) { $size = null; $margins = null; }
        }
        return ['complete' => $complete, 'sections' => $sections, 'grid' => $grid];
    }
}
