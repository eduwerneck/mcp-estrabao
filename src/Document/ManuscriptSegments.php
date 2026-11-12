<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Text\{PlainText, WordInterval};

/** Lexical segmentation with uncertainty retained; no assumed order or scientific interpretation. */
final class ManuscriptSegments
{
    const CORE = ['Introdução', 'Metodologia', 'Resultados e Discussão', 'Considerações Finais'];

    public static function count(array $document, ?array $titles): array
    {
        $lower = 0; $upper = 0; $found = []; $zone = 'uncertain'; $seenBody = false; $ambiguousBoundary = false;
        $regions = []; $region = null; $regionCount = 0; $categories = [];
        $titleTexts = [];
        foreach (array_intersect_key($titles ?? [], array_flip(['pt_BR', 'en_US', 'es_ES'])) as $title) {
            $normalized = PlainText::normalize($title);
            if ($normalized !== null) { $titleTexts[] = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $normalized)); }
        }
        foreach ($document['paragraphs'] as $paragraph) {
            list($low, $high) = WordInterval::counts($paragraph['text']);
            $text = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $paragraph['text']));
            $label = self::label($text);
            if ($high === 0) {
                $category = 'empty'; $low = 0; $high = 0;
            } elseif ($label !== null) {
                if ($zone === 'abstract' && in_array($label, self::CORE, true)) { $ambiguousBoundary = true; }
                if (in_array($label, self::CORE, true) || $label === 'Referências') {
                    $found[$label] = ($found[$label] ?? 0) + 1;
                }
                if (in_array($label, self::CORE, true)) { $zone = 'body'; $seenBody = true; }
                elseif ($label === 'Referências') { $zone = 'references'; }
                else { $zone = 'conditional'; }
                $category = 'section_title'; $low = 0; $high = 0;
            } elseif (preg_match('/^(Resumo|Abstract|Resumen)(?:$|\s*:)/iuD', $text)) {
                if ($seenBody) { $zone = 'uncertain'; $category = 'uncertain'; $low = 0; }
                else { $zone = 'abstract'; $category = 'abstract'; $low = 0; $high = 0; }
            } elseif (preg_match('/^(Palavras-chave|Keywords|Palabras clave)(?:$|\s*:)/iuD', $text)) {
                if ($seenBody) { $zone = 'uncertain'; $category = 'uncertain'; $low = 0; }
                else { $zone = 'keywords'; $category = 'keywords'; $low = 0; $high = 0; }
            } elseif (!$seenBody && $zone === 'uncertain' && self::matchesTitle($text, $titleTexts)) {
                $category = 'article_title'; $low = 0; $high = 0;
            } else {
                $outline = $paragraph['outline'] ?? ['known' => false, 'level' => null];
                $bold = $paragraph['format']['bold'] ?? null;
                $candidate = !$outline['known'] || $outline['level'] < 9 || $bold === null || $bold === true;
                if ($candidate && $zone !== 'uncertain') {
                    // An extra heading can start an unrecognized section, including after references.
                    $zone = 'uncertain';
                }
                if (in_array($zone, ['references', 'abstract', 'keywords'], true)) {
                    $category = $zone; $low = 0; $high = 0;
                } elseif ($zone === 'body' && !$candidate && !preg_match('/^(Figura|Tabela|Quadro|Gráfico)\s+[0-9]+\b/iu', $text)) {
                    $category = 'body';
                } else { $category = $zone === 'conditional' ? 'conditional' : 'uncertain'; $low = 0; }
            }
            $lower += $low; $upper += $high;
            $categories[$category] = ($categories[$category] ?? 0) + 1;
            if ($region !== null && $region['category'] === $category) {
                $region['last_paragraph'] = $paragraph['index'];
            } else {
                if ($region !== null) { $regionCount++; if (count($regions) < 32) { $regions[] = $region; } }
                $region = ['category' => $category, 'first_paragraph' => $paragraph['index'], 'last_paragraph' => $paragraph['index']];
            }
        }
        if ($region !== null) { $regionCount++; if (count($regions) < 32) { $regions[] = $region; } }
        $complete = !$ambiguousBoundary;
        foreach (array_merge(self::CORE, ['Referências']) as $label) {
            if (($found[$label] ?? 0) !== 1) { $complete = false; }
        }
        return ['minimum_count' => $lower, 'maximum_count' => $upper, 'segmentation_complete' => $complete, 'ambiguous_abstract_boundary' => $ambiguousBoundary,
            'categories' => $categories, 'regions' => $regions, 'regions_omitted' => max(0, $regionCount - count($regions)),
            'recognized_sections' => $found];
    }

    private static function label(string $text): ?string
    {
        foreach (array_merge(self::CORE, ['Créditos (CRediT)', 'Fontes de Fomento', 'Referências']) as $label) {
            if (preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($label, '/') . '$/iuD', $text)) { return $label; }
        }
        return null;
    }

    private static function matchesTitle(string $text, array $titles): bool
    {
        foreach ($titles as $title) {
            if ($title === '') { continue; }
            if ($text === $title || (strlen($title) <= 4096 && preg_match('/^' . preg_quote($title, '/') . '$/iuD', $text))) { return true; }
        }
        return false;
    }
}
