<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Text\PlainText;

final class AbstractLength implements Rule
{
    /** Intervalo conservador de duas contagens técnicas, ainda sujeito à revisão. */
    public static function counts(string $text, string $locale): array
    {
        // Remover somente os nomes aprovados para calcular limite inferior sem rótulos.
        $withoutLabels = $text;
        foreach (AbstractLabels::LABELS[$locale] as $alternatives) {
            foreach ($alternatives as $label) {
                $withoutLabels = preg_replace('~(?<![\p{L}\p{N}_])' . preg_quote($label, '~') . '(?![\p{L}\p{N}_])~iu', ' ', $withoutLabels);
            }
        }
        $tokens = preg_split('/[\s\p{Z}]+/u', trim($withoutLabels), -1, PREG_SPLIT_NO_EMPTY);
        $lower = 0;
        foreach ($tokens as $token) {
            if (preg_match('/[\p{L}\p{N}]/u', $token)) { $lower++; }
        }
        // Limite superior inclui rótulos e separa hífens/apóstrofos/pontuação.
        $upper = preg_match_all('/[\p{L}\p{N}]+(?:\p{M}+[\p{L}\p{N}]*)*/u', $text);
        return [$lower, max($lower, $upper)];
    }

    public function evaluate(Submission $submission): array
    {
        $map = $submission->field('abstract'); $results = [];
        foreach (array_keys(AbstractLabels::LABELS) as $locale) {
            $text = isset($map[$locale]) ? PlainText::normalize($map[$locale]) : null;
            $counts = $text === null ? null : self::counts($text, $locale);
            $hasContent = $text !== null && PlainText::hasContent($text);
            // Ausência não aprova extensão por uma contagem vazia; regra de presença cuida dela.
            $status = !$hasContent ? CheckResult::UNKNOWN
                : ($counts[1] <= 250 ? CheckResult::PASS : ($counts[0] > 250 ? CheckResult::FAIL : CheckResult::UNKNOWN));
            $results[] = new CheckResult('abstract.word_limit', $status,
                ['policy_version' => AbstractLabels::POLICY, 'maximum' => 250,
                 'counting_method' => 'conservative_interval_v1',
                 'minimum_count' => $counts === null ? null : $counts[0],
                 'maximum_count' => $counts === null ? null : $counts[1],
                 'source_available' => $map !== null, 'normalization_available' => $text !== null],
                '/abstract/' . $locale,
                $status === CheckResult::PASS ? 'Dentro do limite nas duas contagens técnicas; não avalia qualidade do resumo.'
                    : ($status === CheckResult::FAIL ? 'Reduzir o resumo a até 250 palavras e conferir a contagem editorial.'
                        : 'Conferir contagem e texto; ausência ou divergência entre métodos requer revisão humana.'));
        }
        return $results;
    }
}
