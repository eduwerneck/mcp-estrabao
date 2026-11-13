<?php
declare(strict_types=1);
namespace Estrabao\Reporting;
use Estrabao\Contracts\{Submission, Rule};

final class Report
{
    const ENGINE_VERSION = '0.17.0';
    /** Não escreve em disco, não comunica a serviços, não altera estado editorial. */
    public static function generate(Submission $submission, array $rules): array
    {
        $checks = [];
        foreach ($rules as $rule) {
            if (!$rule instanceof Rule) {
                throw new \InvalidArgumentException('Regra inválida.');
            }
            foreach ($rule->evaluate($submission) as $result) {
                $checks[] = $result->jsonSerialize();
            }
        }
        return [
            'schema_version' => '1.0', 'engine_version' => self::ENGINE_VERSION,
            'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'input_sha256' => $submission->digest(),
            'locale_profile' => ['pt_BR', 'en_US', 'es_ES'],
            'scope' => 'local_snapshot_only', 'human_review_required' => true,
            'checks' => $checks,
        ];
    }
}
