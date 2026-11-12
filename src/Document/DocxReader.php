<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Local, bounded OOXML inspection. Never extracts members or follows relationships. */
final class DocxReader
{
    const MAX_FILE = 10485760;
    const MAX_ENTRIES = 256;
    const MAX_PART = 8388608;
    const MAX_TOTAL = 33554432;
    const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function read(string $path): array
    {
        $unknown = ['available' => false, 'reason' => 'unavailable', 'sha256' => null, 'paragraphs' => []];
        $temporary = null; $zip = null; $opened = false;
        try {
            if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) || !is_file($path)
                || !is_readable($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'docx') {
                $unknown['reason'] = 'unsupported_or_unavailable'; return $unknown;
            }
            $stream = @fopen($path, 'rb');
            if ($stream === false) { return $unknown; }
            try { $bytes = stream_get_contents($stream, self::MAX_FILE + 1); }
            finally { fclose($stream); }
            if ($bytes === false || strlen($bytes) > self::MAX_FILE) {
                $unknown['reason'] = 'file_limit'; return $unknown;
            }
            $unknown['sha256'] = hash('sha256', $bytes);
            if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
                $unknown['reason'] = 'runtime_extensions'; return $unknown;
            }
            $unknown['reason'] = 'unsafe_or_invalid_package';
            $temporary = @tempnam(sys_get_temp_dir(), 'estrabao-docx-');
            if ($temporary === false || !@chmod($temporary, 0600)
                || @file_put_contents($temporary, $bytes) !== strlen($bytes)) { return $unknown; }
            $zip = new \ZipArchive();
            if (@$zip->open($temporary, \ZipArchive::CHECKCONS) !== true) { return $unknown; }
            $opened = true;
            if ($zip->numFiles < 3 || $zip->numFiles > self::MAX_ENTRIES) { return $unknown; }
            $parts = []; $seen = []; $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) { return $unknown; }
                $name = $stat['name']; $key = strtolower($name);
                if ($name === '' || preg_match('~[\\\\\x00-\x1f:]|^/|(?:^|/)\.\.?(?:/|$)~', $name)
                    || isset($seen[$key]) || strpos($key, 'vbaproject') !== false
                    || strpos($key, 'word/embeddings/') === 0) { return $unknown; }
                $seen[$key] = true;
                $total += $stat['size'];
                if ($stat['size'] > self::MAX_PART || $total > self::MAX_TOTAL
                    || $stat['size'] > max(1, $stat['comp_size']) * 200
                    || !in_array($stat['comp_method'], [0, 8], true)
                    || !isset($stat['encryption_method']) || $stat['encryption_method'] !== 0) { return $unknown; }
                $opsys = 0; $attributes = 0;
                if (!$zip->getExternalAttributesIndex($i, $opsys, $attributes)
                    || ($opsys === 3 && (($attributes >> 16) & 0170000) === 0120000)) { return $unknown; }
                $body = @$zip->getFromIndex($i, self::MAX_PART + 1);
                if ($body === false || strlen($body) !== $stat['size']
                    || strtolower(hash('crc32b', $body)) !== sprintf('%08x', $stat['crc'])) { return $unknown; }
                if (preg_match('/\.(xml|rels)$/i', $name)) { $parts[$name] = $this->xml($body); }
            }
            if (!isset($parts['[Content_Types].xml'], $parts['_rels/.rels'], $parts['word/document.xml'])) { return $unknown; }
            $types = new \DOMXPath($parts['[Content_Types].xml']);
            $types->registerNamespace('c', 'http://schemas.openxmlformats.org/package/2006/content-types');
            if ($types->query('/c:Types/c:Override[@PartName="/word/document.xml" and @ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"]')->length !== 1
                || stripos($parts['[Content_Types].xml']->saveXML(), 'macroEnabled') !== false) { return $unknown; }
            $rels = new \DOMXPath($parts['_rels/.rels']);
            $rels->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            if ($rels->query('/r:Relationships/r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" and (@Target="word/document.xml" or @Target="/word/document.xml") and not(@TargetMode="External")]')->length !== 1) { return $unknown; }
            $xpath = new \DOMXPath($parts['word/document.xml']); $xpath->registerNamespace('w', self::W);
            if ($xpath->query('/w:document/w:body')->length !== 1) { return $unknown; }
            // Unsupported containers/fields/revisions can hide or change section labels.
            if ($xpath->query('/w:document/w:body//*[namespace-uri()!="' . self::W . '"]')->length
                || $xpath->query('/w:document/w:body/w:p//*[not(ancestor::w:pPr or ancestor::w:rPr) and not(self::w:r or self::w:t or self::w:tab or self::w:br or self::w:cr or self::w:pPr or self::w:rPr or self::w:hyperlink or self::w:bookmarkStart or self::w:bookmarkEnd or self::w:proofErr)]')->length
                || $xpath->query('/w:document/w:body/*[not(self::w:p or self::w:sectPr)]')->length
                || $xpath->query('//w:altChunk|//w:txbxContent|//w:ins|//w:del|//w:moveFrom|//w:moveTo|//w:fldChar|//w:fldSimple|//w:instrText|//w:sdt|//w:tbl|//w:object|//w:pict|//w:sym|//w:vanish')->length
                || $xpath->query('//*[namespace-uri()="http://schemas.openxmlformats.org/markup-compatibility/2006"]')->length) {
                $unknown['reason'] = 'incomplete_coverage'; return $unknown;
            }
            foreach ($parts as $part) {
                $hidden = new \DOMXPath($part); $hidden->registerNamespace('w', self::W);
                if ($hidden->query('//w:vanish|//w:webHidden')->length) {
                    $unknown['reason'] = 'incomplete_coverage'; return $unknown;
                }
            }
            $resolver = new StyleResolver($parts);
            $paragraphs = [];
            foreach ($xpath->query('/w:document/w:body/w:p') as $index => $paragraph) {
                $text = '';
                foreach ($xpath->query('.//w:t|.//w:tab|.//w:br|.//w:cr', $paragraph) as $node) {
                    $text .= $node->localName === 't' ? $node->textContent : ' ';
                }
                // Combining marks are deferred rather than mistaken for missing accents.
                if (preg_match('/\p{M}/u', $text)) { $unknown['reason'] = 'unicode_coverage'; return $unknown; }
                $bold = true; $hasText = false;
                foreach ($xpath->query('.//w:r[w:t]', $paragraph) as $run) {
                    if (trim($run->textContent) === '') { continue; }
                    $hasText = true;
                    $property = $xpath->query('./w:rPr/w:b', $run)->item(0);
                    $value = $property === null ? null : $property->getAttributeNS(self::W, 'val');
                    if (in_array($value, ['0', 'false', 'off'], true)) { $bold = false; break; }
                    if ($value === null || !in_array($value, ['', '1', 'true', 'on'], true)) { $bold = null; }
                }
                $paragraphs[] = ['format' => $resolver->paragraph($paragraph), 'direct_bold' => $hasText ? $bold : null, 'index' => $index, 'text' => trim(preg_replace('/\s+/u', ' ', $text)),
                    'numbered' => $xpath->query('./w:pPr/w:numPr[w:numId and not(w:numId/@w:val="0")]', $paragraph)->length > 0];
            }
            return ['available' => true, 'reason' => 'body_paragraphs', 'sha256' => $unknown['sha256'], 'paragraphs' => $paragraphs];
        } catch (\Throwable $error) {
            return $unknown; // No document text, paths, XML errors or exception details in reports.
        } finally {
            if ($opened) { $zip->close(); }
            if (is_string($temporary) && is_file($temporary)) { unlink($temporary); }
        }
    }

    private function xml(string $body): \DOMDocument
    {
        if (strpos($body, "\0") !== false || !preg_match('//u', $body)
            || preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $body)
            || (preg_match('/<\?xml[^>]*encoding\s*=\s*["\']([^"\']+)/i', $body, $m)
                && !in_array(strtolower($m[1]), ['utf-8', 'utf8'], true))) {
            throw new \RuntimeException('Unsupported XML.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument();
            $document->resolveExternals = false; $document->substituteEntities = false;
            if (!$document->loadXML($body, LIBXML_NONET) || $document->doctype !== null) {
                throw new \RuntimeException('Invalid XML.');
            }
            return $document;
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
}
