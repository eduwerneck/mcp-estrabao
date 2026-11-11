<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Text\PlainText;

final class KeywordCount implements Rule
{
    public function evaluate(Submission $submission): array
    {
        $map = $submission->field('keywords'); $results = [];
        foreach (array_keys(AbstractLabels::LABELS) as $locale) {
            $items = $map[$locale] ?? [];
            $count = 0; $empty = 0; $unreadable = 0; $normalized = []; $available = $map !== null;
            foreach ($items as $item) {
                $text = PlainText::normalize($item);
                if ($text === null) { $available = false; $unreadable++; continue; }
                if (PlainText::hasContent($text)) { $count++; $normalized[] = trim($text); }
                else { $empty++; }
            }
            $status = !$available ? CheckResult::UNKNOWN : ($count >= 3 && $count <= 4 ? CheckResult::PASS : CheckResult::FAIL);
            $results[] = new CheckResult('metadata.keywords.count', $status,
                ['policy_version' => AbstractLabels::POLICY, 'source_available' => $map !== null,
                 'normalization_available' => $available, 'count' => $available ? $count : null,
                 'minimum' => 3, 'maximum' => 4, 'empty_items' => $empty, 'unreadable_items' => $unreadable,
                 'exact_duplicates' => count($normalized) - count(array_unique($normalized))],
                '/keywords/' . $locale,
                $status === CheckResult::UNKNOWN ? 'Obter palavras-chave extraídas com segurança antes de avaliar quantidade.'
                    : ($status === CheckResult::PASS ? 'Quantidade válida; duplicatas e adequação temática dependem de revisão humana.'
                        : 'Informar de 3 a 4 palavras-chave não vazias neste idioma.'));
        }
        return $results;
    }
}
