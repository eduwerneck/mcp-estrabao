<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

final class VisualCount implements Rule
{
    private $document;
    private $type;

    public function __construct(?array $document = null, ?string $type = null)
    {
        if ($type !== null && !in_array($type, ['article', 'research_communication'], true)) {
            throw new \InvalidArgumentException('Tipo de manuscrito inválido.');
        }
        $this->document = $document; $this->type = $type;
    }

    public function evaluate(Submission $submission): array
    {
        $inventory = $this->document['visuals'] ?? ['available' => false];
        $applicable = $this->type === 'research_communication';
        $status = CheckResult::UNKNOWN;
        if ($applicable && $inventory['available'] && $inventory['coverage_complete']) {
            if ($inventory['minimum_count'] > 6) { $status = CheckResult::FAIL; }
            elseif ($inventory['counting_units_resolved'] && $inventory['maximum_count'] <= 6) { $status = CheckResult::PASS; }
        }
        return [new CheckResult('manuscript.visuals.count', $status,
            ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                'document_sha256' => $this->document['sha256'] ?? null,
                'scope' => 'main_body_structural_inventory', 'manuscript_type' => $this->type,
                'numeric_limit_published' => $applicable, 'maximum' => $applicable ? 6 : null,
                'counting_method' => 'simple_word_tables_with_unresolved_figures_v1', 'inventory' => $inventory], '/manuscript',
            $applicable ? 'Revisar o limite conjunto de 6 figuras/tabelas; imagens, figuras compostas e estruturas ambíguas exigem revisão conforme STAGE7.md.'
                : 'Não aplicar limite numérico a artigos nem inferir tipo; o máximo de 6 foi publicado apenas para Comunicações de Pesquisa.')];
    }
}
