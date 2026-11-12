<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Text\{PlainText, TitleCase};

final class MetadataTitleCase implements Rule
{
    public function evaluate(Submission $submission): array
    {
        $results = [];
        foreach (['title', 'subtitle'] as $field) {
            $map = $submission->field($field);
            foreach (['pt_BR' => 'pt', 'en_US' => 'en', 'es_ES' => 'es'] as $locale => $language) {
                $text = isset($map[$locale]) ? PlainText::normalize($map[$locale]) : null;
                $inspection = TitleCase::inspect($text, $language);
                $results[] = new CheckResult('metadata.' . $field . '.case', $inspection['status'],
                    ['policy_version' => TitleCase::POLICY, 'scope' => 'stored_metadata_text',
                        'source_available' => $map !== null, 'all_caps' => $inspection['all_caps'],
                        'reason' => $inspection['reason'], 'automatic_rewrite' => false], '/' . $field . '/' . $locale,
                    'Evitar caixa alta integral; preservar siglas e revisar casos ambíguos. Subtítulo é opcional.');
            }
        }
        return $results;
    }
}
