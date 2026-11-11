<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

/** Expõe lacunas; não implementa análise de DOCX, APA ou rótulos. */
final class PendingPolicy implements Rule
{
    public function evaluate(Submission $submission): array
    {
        $results = [];
        foreach ([
            'abstract.labels' => 'Fornecer política e rótulos obrigatórios por idioma.',
            'manuscript.docx.structure' => 'Fornecer template oficial e implementar extração segura de DOCX.',
            'references.apa7' => 'Definir escopo verificável de APA 7 e implementar análise com revisão humana.',
            'references.citation_matching' => 'Implementar extração e correspondência entre citações e referências.',
        ] as $id => $message) {
            $results[] = new CheckResult($id, CheckResult::UNKNOWN,
                ['implemented' => false, 'policy_version' => null], '', $message);
        }
        return $results;
    }
}
