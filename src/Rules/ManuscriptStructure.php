<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

final class ManuscriptStructure implements Rule
{
    private $document;
    private $type;
    private $language;

    public function __construct(?array $document = null, ?string $type = null, ?string $language = null)
    {
        if (($type !== null && !in_array($type, ['article', 'research_communication'], true))
            || ($language !== null && !in_array($language, ['pt', 'en', 'es'], true))) {
            throw new \InvalidArgumentException('Contexto de manuscrito inválido.');
        }
        $this->document = $document; $this->type = $type; $this->language = $language;
    }

    public function evaluate(Submission $submission): array
    {
        $evidence = ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
            'document_sha256' => $this->document['sha256'] ?? null,
            'extraction' => $this->document['reason'] ?? 'not_supplied',
            'manuscript_type' => $this->type, 'language' => $this->language];
        $status = CheckResult::UNKNOWN;
        if (($this->document['available'] ?? false) && $this->type !== null && $this->language === 'pt') {
            $sections = []; $status = CheckResult::PASS;
            foreach (['Introdução', 'Metodologia', 'Resultados e Discussão', 'Considerações Finais', 'Créditos (CRediT)', 'Referências'] as $label) {
                $matches = []; $ambiguous = false;
                foreach ($this->document['paragraphs'] as $paragraph) {
                    $text = $paragraph['text'];
                    // Exact standalone labels, optionally with explicit numeric prefix.
                    $pattern = '/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($label, '/') . '$/iuD';
                    if (preg_match($pattern, $text)) {
                        $matches[] = ['paragraph_index' => $paragraph['index'], 'direct_bold' => $paragraph['direct_bold'] ?? null,
                            'explicit_numbering' => $paragraph['numbered'] || preg_match('/^[0-9]/', $text) === 1];
                    } elseif (preg_match('/' . preg_quote($label, '/') . '/iu', $text)) { $ambiguous = true; }
                }
                if ($label === 'Resultados e Discussão' && !$matches) {
                    foreach ($this->document['paragraphs'] as $paragraph) {
                        if (preg_match('/^(Resultados|Discussão)$/iuD', $paragraph['text'])) { $ambiguous = true; }
                    }
                }
                $sectionStatus = $matches ? CheckResult::PASS : ($ambiguous ? CheckResult::UNKNOWN : CheckResult::FAIL);
                $sections[] = ['required_label' => $label, 'status' => $sectionStatus, 'matches' => $matches];
                if ($sectionStatus === CheckResult::FAIL) { $status = CheckResult::FAIL; }
                elseif ($sectionStatus === CheckResult::UNKNOWN && $status === CheckResult::PASS) { $status = CheckResult::UNKNOWN; }
            }
            $evidence['sections'] = $sections;
            $evidence['order_enforced'] = false;
        }
        $results = [new CheckResult('manuscript.docx.structure', $status, $evidence, '/manuscript',
            'Revisar a presença dos títulos; traduções, variantes e cobertura incompleta exigem revisão humana.', '2.0.0')];
        if ($this->document !== null) {
            $numbered = false; $nonBold = false;
            foreach ($evidence['sections'] ?? [] as $section) {
                foreach ($section['matches'] as $match) {
                    $numbered = $numbered || $match['explicit_numbering'];
                    $nonBold = $nonBold || $match['direct_bold'] === false;
                }
            }
            $results[] = new CheckResult('manuscript.headings.format', ($numbered || $nonBold) ? CheckResult::FAIL : CheckResult::UNKNOWN,
                ['explicit_numbering' => $numbered, 'explicit_nonbold' => $nonBold, 'effective_bold_resolved' => false,
                    'inherited_numbering_resolved' => false], '/manuscript',
                'Conferir títulos não numerados e em negrito; resolver estilos e herança antes de concluir conformidade.');
            if ($this->type === 'article') {
                $results[] = new CheckResult('manuscript.funding', CheckResult::UNKNOWN,
                    ['conditional' => true, 'applicability_known' => false], '/manuscript',
                    'Verificar se houve fomento e, quando aplicável, a seção Fontes de Fomento.');
            }
        }
        return $results;
    }
}
