<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Resolve only already-parsed, local package parts. No I/O or relationship traversal. */
final class PackageRelationships
{
    public static function part(array $parts, string $kind, bool &$known): ?\DOMDocument
    {
        $known = true;
        $rels = $parts['word/_rels/document.xml.rels'] ?? null;
        if ($rels === null) {
            if (isset($parts['word/' . $kind . '.xml'])) { $known = false; }
            return null;
        }
        $x = new \DOMXPath($rels);
        $x->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
        if ($x->query('/r:Relationships')->length !== 1) { $known = false; return null; }
        $links = $x->query('/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/' . $kind . '"]');
        if ($links->length === 0) {
            if (isset($parts['word/' . $kind . '.xml'])) { $known = false; }
            return null;
        }
        if ($links->length !== 1) { $known = false; return null; }
        $link = $links->item(0); $target = $link->getAttribute('Target');
        if ($link->getAttribute('TargetMode') === 'External' || preg_match('~[:\\\\%?#\x00-\x20]~', $target) || $target === '') {
            $known = false; return null;
        }
        $segments = [];
        foreach (explode('/', $target[0] === '/' ? substr($target, 1) : 'word/' . $target) as $segment) {
            if ($segment === '..') {
                if (!$segments) { $known = false; return null; }
                array_pop($segments);
            } elseif ($segment !== '' && $segment !== '.') { $segments[] = $segment; }
        }
        $name = implode('/', $segments);
        if (!isset($parts[$name])) { $known = false; return null; }
        return $parts[$name];
    }
}
