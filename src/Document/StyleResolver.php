<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Bounded subset of WordprocessingML formatting; unsupported inheritance stays unknown. */
final class StyleResolver
{
    const MAX_STYLES = 1024;
    const MAX_NUMBERING_NODES = 4096;
    const MAX_RUNS_PER_PARAGRAPH = 4096;
    private $styles = [];
    private $defaults = [];
    private $styleXml;
    private $numbering;
    private $valid;

    public function __construct(array $parts)
    {
        $this->valid = true;
        $this->styleXml = $this->part($parts, 'styles');
        $this->numbering = $this->part($parts, 'numbering');
        if ($this->numbering !== null) {
            $nx = $this->xpath($this->numbering);
            if ($nx->query('/w:numbering/*')->length > self::MAX_NUMBERING_NODES || $nx->query('/w:numbering')->length !== 1 || $nx->query('//*[namespace-uri()!="' . DocxReader::W . '"]')->length) { $this->valid = false; }
        }
        if ($this->styleXml === null) { return; }
        $x = $this->xpath($this->styleXml);
        if ($x->query('/w:styles/w:style')->length > self::MAX_STYLES || $x->query('/w:styles')->length !== 1 || $x->query('//w:rPrChange|//w:pPrChange|//*[namespace-uri()!="' . DocxReader::W . '"]')->length) {
            $this->valid = false; return;
        }
        foreach ($x->query('/w:styles/w:style') as $style) {
            $id = $style->getAttributeNS(DocxReader::W, 'styleId');
            $type = $style->getAttributeNS(DocxReader::W, 'type');
            if ($id === '' || isset($this->styles[$id])) { $this->valid = false; return; }
            if ($style->hasAttributeNS(DocxReader::W, 'default') && !in_array($style->getAttributeNS(DocxReader::W, 'default'), ['1', 'true', 'on', '0', 'false', 'off'], true)) { $this->valid = false; return; }
            $this->styles[$id] = $style;
            if (in_array($style->getAttributeNS(DocxReader::W, 'default'), ['1', 'true', 'on'], true)) {
                if (isset($this->defaults[$type])) { $this->valid = false; return; }
                $this->defaults[$type] = $id;
            }
        }
    }

    /** Relationships select parts; never use an orphan part or follow external targets. */
    private function part(array $parts, string $kind): ?\DOMDocument
    {
        $rels = $parts['word/_rels/document.xml.rels'] ?? null;
        if ($rels === null) {
            if (isset($parts['word/' . $kind . '.xml'])) { $this->valid = false; }
            return null;
        }
        $x = new \DOMXPath($rels);
        $x->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
        if ($x->query('/r:Relationships')->length !== 1) { $this->valid = false; return null; }
        $links = $x->query('/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/' . $kind . '"]');
        if ($links->length === 0) {
            if (isset($parts['word/' . $kind . '.xml'])) { $this->valid = false; }
            return null;
        }
        if ($links->length !== 1) { $this->valid = false; return null; }
        $link = $links->item(0); $target = $link->getAttribute('Target');
        if ($link->getAttribute('TargetMode') === 'External' || preg_match('~[:\\\\%?#\x00-\x20]~', $target) || $target === '') {
            $this->valid = false; return null;
        }
        $segments = [];
        foreach (explode('/', $target[0] === '/' ? substr($target, 1) : 'word/' . $target) as $segment) {
            if ($segment === '..') {
                if (!$segments) { $this->valid = false; return null; }
                array_pop($segments);
            } elseif ($segment !== '' && $segment !== '.') { $segments[] = $segment; }
        }
        $name = implode('/', $segments);
        if (!isset($parts[$name])) { $this->valid = false; return null; }
        return $parts[$name];
    }

    public function paragraph(\DOMElement $paragraph): array
    {
        $unknown = ['bold' => null, 'numbered' => null, 'reason' => 'unsupported_style_inheritance'];
        if (!$this->valid) { return $unknown; }
        try {
            $x = $this->xpath($paragraph->ownerDocument);
            if ($x->query('.//w:r[w:t]', $paragraph)->length > self::MAX_RUNS_PER_PARAGRAPH) { return $unknown; }
            $this->containers($paragraph);
            $paragraphId = $this->value($paragraph, './w:pPr/w:pStyle');
            $chain = $this->chain($paragraphId ?? ($this->defaults['paragraph'] ?? null), 'paragraph');
            $bold = false; $num = null; $level = null; $styleLevel = false;
            if ($this->styleXml !== null) {
                $sx = $this->xpath($this->styleXml);
                if ($sx->query('/w:styles/w:docDefaults')->length > 1) { return $unknown; }
                foreach ($sx->query('/w:styles/w:docDefaults | /w:styles/w:docDefaults/w:rPrDefault | /w:styles/w:docDefaults/w:pPrDefault') as $container) { $this->containers($container); }
                $defaultRun = $sx->query('/w:styles/w:docDefaults/w:rPrDefault/w:rPr')->item(0);
                if ($defaultRun !== null) { $bold = $this->bold($defaultRun, $bold, false); }
                $defaultPara = $sx->query('/w:styles/w:docDefaults/w:pPrDefault/w:pPr')->item(0);
                if ($defaultPara !== null) { $this->number($defaultPara, $num, $level); }
            }
            foreach ($chain as $style) {
                $r = $this->xpath($style->ownerDocument)->query('./w:rPr', $style)->item(0);
                if ($r !== null) { $bold = $this->bold($r, $bold, true); }
                $p = $this->xpath($style->ownerDocument)->query('./w:pPr', $style)->item(0);
                if ($p !== null) {
                    $this->number($p, $num, $level);
                    if ($this->value($p, './w:numPr/w:ilvl') !== null) { $styleLevel = true; }
                }
            }
            $directPara = $x->query('./w:pPr', $paragraph)->item(0);
            if ($directPara !== null) { $this->number($directPara, $num, $level); }
            $directLevel = $directPara !== null && $this->value($directPara, './w:numPr/w:ilvl') !== null;
            $numbered = $this->numbered($num, $level, $paragraphId, $chain, $directLevel, $styleLevel);
            $allBold = true; $hasText = false;
            foreach ($x->query('.//w:r[w:t]', $paragraph) as $run) {
                if (!preg_match('/[^\s\p{Z}]/u', implode('', array_map(function ($n) { return $n->textContent; }, iterator_to_array($x->query('./w:t', $run)))))) { continue; }
                $this->containers($run);
                $hasText = true; $runBold = $bold;
                $characterId = $this->value($run, './w:rPr/w:rStyle');
                foreach ($this->chain($characterId ?? ($this->defaults['character'] ?? null), 'character') as $style) {
                    $r = $this->xpath($style->ownerDocument)->query('./w:rPr', $style)->item(0);
                    if ($r !== null) { $runBold = $this->bold($r, $runBold, true); }
                }
                $r = $x->query('./w:rPr', $run)->item(0);
                if ($r !== null) { $runBold = $this->bold($r, $runBold, false); }
                $allBold = $allBold && $runBold;
            }
            return ['bold' => $hasText ? $allBold : null, 'numbered' => $numbered, 'reason' => $numbered === null ? 'numbering_not_resolved' : 'resolved_supported_styles'];
        } catch (\RuntimeException $error) { return $unknown; }
    }

    private function chain(?string $id, string $type): array
    {
        $chain = []; $seen = [];
        while ($id !== null) {
            if (isset($seen[$id]) || count($chain) >= 32 || !isset($this->styles[$id])) { throw new \RuntimeException(); }
            $seen[$id] = true; $style = $this->styles[$id];
            if ($style->getAttributeNS(DocxReader::W, 'type') !== $type) { throw new \RuntimeException(); }
            $this->containers($style);
            $chain[] = $style; $id = $this->value($style, './w:basedOn');
        }
        return array_reverse($chain);
    }

    private function bold(\DOMNode $properties, bool $inherited, bool $toggle): bool
    {
        $x = $this->xpath($properties->ownerDocument);
        if ($x->query('./w:cs|./w:rtl|./w:rPrChange', $properties)->length) { throw new \RuntimeException(); }
        $nodes = $x->query('./w:b', $properties);
        if ($nodes->length === 0) { return $inherited; }
        if ($nodes->length !== 1) { throw new \RuntimeException(); }
        $v = $nodes->item(0)->getAttributeNS(DocxReader::W, 'val');
        if (!in_array($v, ['', '1', 'true', 'on', '0', 'false', 'off'], true)) { throw new \RuntimeException(); }
        $on = in_array($v, ['', '1', 'true', 'on'], true);
        // Style b=on toggles; style b=off preserves. Direct formatting is absolute.
        return $toggle ? ($on ? !$inherited : $inherited) : $on;
    }

    private function number(\DOMNode $properties, ?string &$num, ?string &$level): void
    {
        $x = $this->xpath($properties->ownerDocument);
        if ($x->query('./w:numPr', $properties)->length > 1 || $x->query('./w:pPrChange', $properties)->length) { throw new \RuntimeException(); }
        $id = $this->value($properties, './w:numPr/w:numId');
        $ilvl = $this->value($properties, './w:numPr/w:ilvl');
        if ($id !== null) {
            if (!preg_match('/^[0-9]{1,9}$/D', $id)) { throw new \RuntimeException(); }
            $num = (string) (int) $id;
        }
        if ($ilvl !== null) {
            if (!preg_match('/^[0-8]$/D', $ilvl)) { throw new \RuntimeException(); }
            $level = $ilvl;
        }
    }

    private function numbered(?string $num, ?string $level, ?string $styleId, array $chain, bool $directLevel, bool $styleLevel): ?bool
    {
        if ($num === '0') { return false; }
        if ($this->numbering === null) { return $num === null && $level === null ? false : null; }
        $x = $this->xpath($this->numbering);
        if ($x->query('/w:numbering')->length !== 1) { return null; }
        if ($num === null) {
            // Implicit style-to-list links need a separate numbering-style resolver.
            foreach ($x->query('//w:pStyle') as $link) {
                foreach ($chain as $style) {
                    if ($link->getAttributeNS(DocxReader::W, 'val') === $style->getAttributeNS(DocxReader::W, 'styleId')) { return null; }
                }
                if ($link->getAttributeNS(DocxReader::W, 'val') === $styleId) { return null; }
            }
            return $level === null ? false : null;
        }
        $instances = $x->query('/w:numbering/w:num[@w:numId="' . $num . '"]');
        if ($instances->length !== 1) { return null; }
        $instance = $instances->item(0);
        $abstract = $this->value($instance, './w:abstractNumId');
        if ($abstract === null || !preg_match('/^[0-9]{1,9}$/D', $abstract)) { return null; }
        $definitions = $x->query('/w:numbering/w:abstractNum[@w:abstractNumId="' . $abstract . '"]');
        if ($definitions->length !== 1) { return null; }
        $definition = $definitions->item(0);
        if ($x->query('./w:numStyleLink|./w:styleLink', $definition)->length) { return null; }
        if (!$directLevel) {
            $mapped = null;
            foreach (array_reverse($chain) as $style) {
                $id = $style->getAttributeNS(DocxReader::W, 'styleId');
                $matches = [];
                foreach ($x->query('./w:lvl/w:pStyle', $definition) as $link) {
                    if ($link->getAttributeNS(DocxReader::W, 'val') === $id) {
                        $matches[] = $link->parentNode->getAttributeNS(DocxReader::W, 'ilvl');
                    }
                }
                if (count($matches) > 1) { return null; }
                if ($matches) { $mapped = $matches[0]; break; }
            }
            if ($mapped !== null) {
                if (!preg_match('/^[0-8]$/D', $mapped)) { return null; }
                $level = $mapped;
            } elseif ($styleLevel) { return null; }
        }
        $level = $level ?? '0';
        $overrides = $x->query('./w:lvlOverride[@w:ilvl="' . $level . '"]/w:lvl', $instance);
        $levels = $overrides->length ? $overrides : $x->query('./w:lvl[@w:ilvl="' . $level . '"]', $definition);
        if ($levels->length !== 1) { return null; }
        $format = $this->value($levels->item(0), './w:numFmt');
        // Bullets and custom formats are deferred; no invented editorial policy.
        if ($format === 'none') { return false; }
        return in_array($format, ['decimal', 'upperRoman', 'lowerRoman', 'upperLetter', 'lowerLetter'], true) ? true : null;
    }

    private function containers(\DOMNode $node): void
    {
        $x = $this->xpath($node->ownerDocument);
        foreach (['./w:pPr', './w:rPr', './w:rPrDefault', './w:pPrDefault'] as $path) {
            if ($x->query($path, $node)->length > 1) { throw new \RuntimeException(); }
        }
    }

    private function value(\DOMNode $node, string $path): ?string
    {
        $nodes = $this->xpath($node->ownerDocument)->query($path, $node);
        if ($nodes->length === 0) { return null; }
        if ($nodes->length !== 1 || !$nodes->item(0)->hasAttributeNS(DocxReader::W, 'val')) { throw new \RuntimeException(); }
        return $nodes->item(0)->getAttributeNS(DocxReader::W, 'val');
    }

    private function xpath(\DOMDocument $document): \DOMXPath
    {
        $x = new \DOMXPath($document); $x->registerNamespace('w', DocxReader::W); return $x;
    }
}
