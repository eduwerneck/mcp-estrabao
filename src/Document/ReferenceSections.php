<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Shared lexical boundaries, not semantic identification of all bibliographic material. */
final class ReferenceSections
{
    public static function extract(array $document): array
    {
        if (count($document['paragraphs']) > ReferenceAnalysis::MAX_PARAGRAPHS) { throw new \RuntimeException('inspection_limit'); }
        $referenceTexts = []; $bodyTexts = []; $found = []; $zone = 'prefix'; $boundaries = true; $seenBody = false;
        foreach ($document['paragraphs'] as $paragraph) {
            if (preg_match('//u', $paragraph['text']) !== 1 || preg_match('/\p{M}/u', $paragraph['text'])) { throw new \RuntimeException('normalization_unavailable'); }
            $text = trim($paragraph['text']); if ($text === '') { continue; }
            $label = self::section($text);
            if ($label !== null) {
                if ($zone === 'abstract' && in_array($label, ManuscriptSegments::CORE, true)) { $boundaries = false; }
                if (in_array($label, ManuscriptSegments::CORE, true)) { $seenBody = true; }
                $found[$label] = ($found[$label] ?? 0) + 1;
                $zone = $label === 'Referências' ? 'references' : (in_array($label, ManuscriptSegments::CORE, true) ? 'body' : 'other');
                continue;
            }
            if (!$seenBody && preg_match('/^(Resumo|Abstract|Resumen)(?:$|\s*:)/iuD', $text)) { $zone = 'abstract'; continue; }
            if (!$seenBody && preg_match('/^(Palavras-chave|Keywords|Palabras clave)(?:$|\s*:)/iuD', $text)) { $zone = 'prefix'; continue; }
            if (in_array($zone, ['prefix', 'abstract', 'other'], true)) { continue; }
            $outline = $paragraph['outline'] ?? ['known' => false, 'level' => null];
            $bold = $paragraph['format']['bold'] ?? null;
            $entryLead = $zone === 'references' && preg_match('/^' . ReferenceAnalysis::SURNAME . ',\s*\p{L}/u', $text)
                && preg_match('/(?:' . ReferenceAnalysis::YEAR_SIGNAL . ')/iu', $text);
            if (!$outline['known'] || $outline['level'] < 9 || ($bold !== false && !($bold === true && $entryLead))) {
                $boundaries = false; $zone = 'other'; continue;
            }
            if ($zone === 'references') { $referenceTexts[] = $paragraph; }
            elseif ($zone === 'body') { $bodyTexts[] = $paragraph; }
        }
        foreach (array_merge(ManuscriptSegments::CORE, ['Referências']) as $label) {
            if (($found[$label] ?? 0) !== 1) { $boundaries = false; }
        }
        return ['references' => $referenceTexts, 'body' => $bodyTexts, 'complete' => $boundaries];
    }

    private static function section(string $text): ?string
    {
        foreach (array_merge(ManuscriptSegments::CORE, ['Referências', 'Créditos (CRediT)', 'Fontes de Fomento']) as $label) {
            if (preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($label, '/') . '$/iuD', $text)) { return $label; }
        }
        return null;
    }
}
