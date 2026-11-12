<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\DocumentSource;
use Estrabao\Document\ManuscriptSegments;
use Estrabao\Text\{PlainText, TitleCase};

final class ManuscriptTitleCase implements Rule
{
    private $document;
    private $language;
    public function __construct(?array $document = null, ?string $language = null)
    {
        $this->document = $document; $this->language = $language;
    }

    public function evaluate(Submission $submission): array
    {
        $source = DocumentSource::mainText($this->document);
        $full = $source['full_document_text_available'] ?? false;
        $counts = [CheckResult::PASS => 0, CheckResult::FAIL => 0, CheckResult::UNKNOWN => 0];
        $samples = []; $prefix = true; $titles = [];
        foreach (['title', 'subtitle'] as $field) {
            foreach (['pt_BR', 'en_US', 'es_ES'] as $locale) {
                $map = $submission->field($field);
                if (isset($map[$locale])) {
                    $normalized = PlainText::normalize($map[$locale]);
                    if ($normalized !== null) { $titles[] = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $normalized)); }
                }
            }
        }
        if ($source['available'] ?? false) {
            foreach ($source['paragraphs'] as $paragraph) {
                $text = $paragraph['text']; if (!preg_match('/[\p{Lu}\p{Ll}\p{Lt}]/u', $text)) { continue; }
                $knownHeading = self::knownHeading($text);
                $outline = $paragraph['outline'] ?? ['known' => false, 'level' => null];
                $certain = $knownHeading || ($outline['known'] && $outline['level'] < 9) || ($prefix && self::matchesTitle($text, $titles));
                if ($knownHeading || ($outline['known'] && $outline['level'] < 9)) { $prefix = false; }
                $candidate = $certain || !$outline['known'] || ($paragraph['format']['bold'] ?? null) !== false;
                if (!$candidate) { continue; }
                $case = $paragraph['letter_case'] ?? ['available' => false];
                $forced = $case['available'] && $case['caps'] && $case['uppercase_rendered'];
                $inspection = TitleCase::inspect($text, $this->language, $knownHeading, $forced);
                if (!$certain) { $inspection['status'] = CheckResult::UNKNOWN; $inspection['reason'] = 'heading_identity_uncertain'; }
                elseif ($inspection['status'] !== CheckResult::FAIL && (!$case['available'] || $case['small_caps'])) {
                    $inspection['status'] = CheckResult::UNKNOWN;
                    $inspection['reason'] = $case['available'] ? 'small_caps_policy_unconfirmed' : 'word_case_properties_unresolved';
                }
                $counts[$inspection['status']]++;
                $sample = ['paragraph_index' => $paragraph['index'], 'heading_confirmed' => $certain,
                    'status' => $inspection['status'], 'all_caps' => $inspection['all_caps'], 'reason' => $inspection['reason']];
                if (count($samples) < 32) { $samples[] = $sample; }
                elseif ($inspection['status'] !== CheckResult::PASS) {
                    foreach ($samples as $index => $existing) {
                        if ($existing['status'] === CheckResult::PASS || ($inspection['status'] === CheckResult::FAIL && $existing['status'] === CheckResult::UNKNOWN)) {
                            $samples[$index] = $sample; break;
                        }
                    }
                }
            }
        }
        $status = $counts[CheckResult::FAIL] ? CheckResult::FAIL : ($full && $counts[CheckResult::PASS] && !$counts[CheckResult::UNKNOWN] ? CheckResult::PASS : CheckResult::UNKNOWN);
        return [new CheckResult('manuscript.headings.case', $status,
            ['source_profile'=>$source['source_profile']??'unavailable','full_document_text_available'=>$full,
                'coverage'=>$source['coverage']??[], 'policy_version' => TitleCase::POLICY, 'scope' => 'recognized_titles_and_heading_candidates',
                'document_sha256' => $this->document['sha256'] ?? null,
                'source_available' => $source['available'] ?? false,
                'counts' => $counts, 'samples' => $samples, 'samples_omitted' => max(0, array_sum($counts) - count($samples)),
                'automatic_rewrite' => false], '/manuscript',
            'Evitar caixa alta integral nos títulos; revisar siglas e candidatos ambíguos sem reescrever o manuscrito.', '2.0.0')];
    }

    private static function knownHeading(string $text): bool
    {
        foreach (array_merge(ManuscriptSegments::CORE, ['Referências', 'Créditos (CRediT)', 'Fontes de Fomento', 'Resumo', 'Abstract', 'Resumen', 'Palavras-chave', 'Keywords', 'Palabras clave']) as $label) {
            if (preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($label, '/') . '$/iuD', $text)) { return true; }
        }
        return false;
    }

    private static function matchesTitle(string $text, array $titles): bool
    {
        $text = trim(preg_replace('/[\s\p{Z}]+/u', ' ', $text));
        foreach ($titles as $title) {
            if ($title !== '' && ($text === $title || (strlen($title) <= 4096 && preg_match('/^' . preg_quote($title, '/') . '$/iuD', $text)))) { return true; }
        }
        return false;
    }
}
