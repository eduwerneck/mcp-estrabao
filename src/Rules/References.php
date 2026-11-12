<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\ReferenceAnalysis;

final class References implements Rule
{
    private $document;
    private $language;
    public function __construct(?array $document = null, ?string $language = null) { $this->document = $document; $this->language = $language; }

    public function evaluate(Submission $submission): array
    {
        $evidence = ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
            'document_sha256' => $this->document['sha256'] ?? null,
            'source_available' => $this->document['available'] ?? false, 'language' => $this->language,
            'scope' => 'recognized_author_date_preflight', 'full_apa_validation' => false,
            'external_lookup_performed' => false];
        $matching = CheckResult::UNKNOWN;
        if ($evidence['source_available'] && $this->language === 'pt') {
            try {
                $analysis = ReferenceAnalysis::analyze($this->document); $evidence['analysis'] = $analysis;
                if ($analysis['closed_subset'] && $analysis['recognized_citations'] > 0) {
                    $matching = $analysis['unresolved_citations'] > 0 ? CheckResult::FAIL : CheckResult::PASS;
                }
            } catch (\RuntimeException $error) {
                $evidence['analysis_unavailable_reason'] = $error->getMessage() === 'inspection_limit' ? 'inspection_limit' : 'normalization_unavailable';
            }
        }
        return [
            new CheckResult('references.apa7', CheckResult::UNKNOWN, $evidence, '/manuscript',
                'Pré-checagem lexical não certifica APA 7; revisar autoria, datas, títulos, fontes e formatação. DOI ausente não reprova.', '2.0.0'),
            new CheckResult('references.citation_matching', $matching, $evidence + ['matching_direction' => 'recognized_citations_to_references'], '/manuscript',
                'Conferir chaves autor–ano reconhecidas; formatos fora do subconjunto e referências não observadas exigem revisão segundo STAGE10.md.', '2.0.0'),
        ];
    }
}
