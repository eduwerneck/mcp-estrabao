<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Text\PlainText;

final class AbstractLabels implements Rule
{
    const POLICY = 'diretrizes-fornecidas-2026-11-11';
    const LABELS = [
        'pt_BR' => [['Contexto', 'Introdução'], ['Metodologia', 'Método'], ['Resultados', 'Conclusões']],
        'en_US' => [['Background', 'Introduction'], ['Methodology', 'Method'], ['Results', 'Conclusions']],
        'es_ES' => [['Contexto', 'Introducción'], ['Metodología', 'Método'], ['Resultados', 'Conclusiones']],
    ];

    /** Offsets são bytes no texto normalizado, não posições no arquivo Word. */
    public static function inspect(string $text, string $locale): array
    {
        $groups = [];
        foreach (self::LABELS[$locale] as $index => $alternatives) {
            $names = implode('|', array_map(function ($label) { return preg_quote($label, '~'); }, $alternatives));
            $word = '(?<![\p{L}\p{N}_])(?:' . $names . ')(?![\p{L}\p{N}_])';
            // Dois-pontos, ponto, travessão ou linha isolada identificam um rótulo.
            $pattern = '~(?:' . $word . '[\p{Z}\t]*(?::|\.(?=\s|$)|[–—]|-(?=\s))|^[\p{Z}\t]*' . $word . '[\p{Z}\t]*$)~imu';
            $found = preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) === 1;
            $mentioned = preg_match('~' . $word . '~iu', $text) === 1;
            $groups[] = [
                'group' => $index + 1, 'accepted' => $alternatives,
                'state' => $found ? 'marker_found' : ($mentioned ? 'ambiguous_mention' : 'absent'),
                'normalized_byte_offset' => $found ? $match[0][1] : null,
            ];
        }
        return $groups;
    }

    public function evaluate(Submission $submission): array
    {
        $map = $submission->field('abstract'); $results = [];
        foreach (array_keys(self::LABELS) as $locale) {
            $text = isset($map[$locale]) ? PlainText::normalize($map[$locale]) : null;
            // Locale ausente num mapa disponível comprova ausência, não falha de extração.
            $available = $map !== null;
            if ($available && !array_key_exists($locale, $map)) { $text = ''; }
            $groups = $text === null ? [] : self::inspect($text, $locale);
            $states = array_column($groups, 'state');
            $status = $text === null ? CheckResult::UNKNOWN
                : (in_array('absent', $states, true) ? CheckResult::FAIL
                    : (in_array('ambiguous_mention', $states, true) ? CheckResult::UNKNOWN : CheckResult::PASS));
            $results[] = new CheckResult('abstract.labels', $status,
                ['policy_version' => self::POLICY, 'source_available' => $available,
                 'normalization_available' => $text !== null, 'groups' => $groups],
                '/abstract/' . $locale,
                $status === CheckResult::PASS ? 'Marcadores presentes; revisar o conteúdo editorialmente.'
                    : ($status === CheckResult::FAIL ? 'Incluir um marcador aprovado de cada grupo ausente.'
                        : 'Conferir o texto e a identificação dos marcadores; menção em prosa não comprova rótulo.'), '2.0.0');
        }
        return $results;
    }
}
