<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\{ReferenceAnalysis, ReferenceStyleAudit};

final class References implements Rule
{
    private $document;
    private $language;
    public function __construct(?array $document = null, ?string $language = null) { $this->document = $document; $this->language = $language; }

    public function evaluate(Submission $submission): array
    {
        $source = ($this->document['available'] ?? false) ? $this->document : ($this->document['reference_body'] ?? null);
        $styleSource = ($source['available'] ?? false) ? $source : ($this->document['reference_tail'] ?? null);
        $profile = ($this->document['available'] ?? false) ? 'full_main_text' : (($source['available'] ?? false) ? 'linked_footnotes_main_body' : (($styleSource['available'] ?? false) ? 'verified_reference_tail' : 'unavailable'));
        $evidence = ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
            'document_sha256' => $this->document['sha256'] ?? null,
            'source_available' => $styleSource['available'] ?? false, 'source_profile' => $profile, 'main_body_available' => $source['available'] ?? false,
            'full_document_text_available' => $this->document['available'] ?? false, 'footnote_content_analyzed' => false, 'language' => $this->language,
            'scope' => 'apa7_structural_and_declared_format_checks', 'style_profile' => 'apa7_reference_patterns_v1', 'full_apa_validation' => false,
            'external_lookup_performed' => false];
        $matching = CheckResult::UNKNOWN; $formats = CheckResult::UNKNOWN; $citationStyle = CheckResult::UNKNOWN;
        if ($source['available'] ?? false) {
            if ($this->language === 'pt') {
                try {
                    $analysis = ReferenceAnalysis::analyze($source); $evidence['analysis'] = $analysis;
                    if ($analysis['closed_subset'] && $analysis['recognized_citations'] > 0) { $matching = $analysis['unresolved_citations'] > 0 ? CheckResult::FAIL : CheckResult::PASS; }
                    if ($analysis['segmentation_complete'] && !$analysis['inspection_limit_reached']) {
                        if ($analysis['citation_style_counts'][CheckResult::FAIL]) { $citationStyle = CheckResult::FAIL; }
                        elseif ($analysis['closed_subset'] && $analysis['recognized_citations'] > 0 && !$analysis['citation_style_counts'][CheckResult::UNKNOWN]) { $citationStyle = CheckResult::PASS; }
                    }
                } catch (\RuntimeException $error) { $evidence['analysis_unavailable_reason'] = $error->getMessage() === 'inspection_limit' ? 'inspection_limit' : 'normalization_unavailable'; }
            }
        }
        if (($styleSource['available'] ?? false) && in_array($this->language, ['pt', 'en', 'es'], true)) {
            $audit = ReferenceStyleAudit::analyze($styleSource, $this->language); $evidence['style_audit'] = $audit;
            $formats = $audit['recognized_formats_status'];
        }
        $components = [];
        foreach (['recognized_reference_formats' => $formats, 'citation_format' => $citationStyle, 'citation_matching' => $matching] as $name => $status) {
            if ($status === CheckResult::FAIL) { $components[] = $name; }
        }
        $evidence['known_incompatibility_components'] = $components;
        $apa = $components ? CheckResult::FAIL : CheckResult::UNKNOWN;
        return [
            new CheckResult('references.apa7', $apa, $evidence, '/manuscript',
                'Corrigir incompatibilidades comprovadas e revisar casos incertos conforme STAGE11.md; verificações automáticas não certificam APA 7 completa.', '3.0.0'),
            new CheckResult('references.citation_matching', $matching, $evidence + ['matching_direction' => 'recognized_citations_to_references'], '/manuscript',
                'Conferir chaves autor–ano reconhecidas; ambiguidade não comprova referência ausente.', '3.0.0'),
            new CheckResult('references.apa7.recognized_formats', $formats, $evidence, '/manuscript',
                'Conferir os formatos e campos reconhecidos. Tipo bibliográfico incerto, semântica e dados da obra ainda exigem revisão.'),
            new CheckResult('references.apa7.citation_format', $citationStyle, $evidence, '/manuscript',
                'Conferir pontuação e conjunções das citações ligadas a referências reconhecidas; maiúsculas isoladas não provam ABNT.'),
        ];
    }
}
