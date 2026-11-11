<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

final class MetadataPresence implements Rule
{
    public function evaluate(Submission $submission): array
    {
        $results = [];
        foreach (['title', 'abstract', 'keywords'] as $field) {
            $map = $submission->field($field);
            foreach (['pt_BR', 'en_US', 'es_ES'] as $locale) {
                $value = $map[$locale] ?? null;
                $present = false;
                foreach (is_array($value) ? $value : [$value] as $text) {
                    // Texto visível; tags vazias e entidades de espaço não contam.
                    if (is_string($text) && preg_match('/[^\s\p{Z}]/u', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                        $present = true;
                    }
                }
                $status = $map === null ? CheckResult::UNKNOWN : ($present ? CheckResult::PASS : CheckResult::FAIL);
                $results[] = new CheckResult('metadata.' . $field . '.presence', $status,
                    ['source_available' => $map !== null, 'locale' => $locale, 'visible_content_present' => $present],
                    '/' . $field . '/' . $locale,
                    $map === null ? 'Obter snapshot completo antes da decisão editorial.' : ($present ? 'Nenhuma correção de presença necessária.' : 'Preencher o metadado neste idioma.'));
            }
        }
        return $results;
    }
}
