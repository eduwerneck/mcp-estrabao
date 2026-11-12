<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\ManuscriptSegments;

final class ManuscriptLength implements Rule
{
    const LIMITS = ['article' => [4000, 8000], 'research_communication' => [1000, 3500]];
    private $document;
    private $type;
    private $language;

    public function __construct(?array $document = null, ?string $type = null, ?string $language = null)
    {
        if (($type !== null && !isset(self::LIMITS[$type]))
            || ($language !== null && !in_array($language, ['pt', 'en', 'es'], true))) {
            throw new \InvalidArgumentException('Contexto de manuscrito inválido.');
        }
        $this->document = $document; $this->type = $type; $this->language = $language;
    }

    public function evaluate(Submission $submission): array
    {
        $limits = self::LIMITS[$this->type ?? ''] ?? null; $status = CheckResult::UNKNOWN;
        $evidence = ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
            'document_sha256' => $this->document['sha256'] ?? null,
            'source_available' => $this->document['available'] ?? false,
            'manuscript_type' => $this->type, 'language' => $this->language,
            'minimum' => $limits[0] ?? null, 'maximum' => $limits[1] ?? null,
            'counting_method' => 'body_conservative_interval_v1',
            'minimum_count' => null, 'maximum_count' => null, 'normalization_available' => null, 'segmentation_complete' => false];
        if ($evidence['source_available'] && $limits !== null && $this->language === 'pt') {
            try {
                $counts = ManuscriptSegments::count($this->document, $submission->field('title'));
                $evidence = array_replace($evidence, $counts);
                $evidence['normalization_available'] = true;
                if ($counts['segmentation_complete'] && $counts['maximum_count'] > 0) {
                    $status = $counts['minimum_count'] >= $limits[0] && $counts['maximum_count'] <= $limits[1]
                        ? CheckResult::PASS
                        : ($counts['maximum_count'] < $limits[0] || $counts['minimum_count'] > $limits[1] ? CheckResult::FAIL : CheckResult::UNKNOWN);
                }
            } catch (\RuntimeException $error) { $evidence['normalization_available'] = false; }
        }
        return [new CheckResult('manuscript.word_limit', $status, $evidence, '/manuscript',
            'Conferir intervalo e exclusões segundo STAGE6.md; convenção editorial, títulos ambíguos, créditos e fomento exigem revisão humana.')];
    }
}
