<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

final class Footnotes implements Rule
{
    private $document;
    public function __construct(?array $document = null) { $this->document = $document; }

    public function evaluate(Submission $submission): array
    {
        $inventory = $this->document['footnotes'] ?? ['available' => false];
        $complete = $inventory['available'] && $inventory['coverage_complete'];
        $count = $complete ? $inventory['note_count'] : null;
        return [
            new CheckResult('manuscript.footnotes.count', $count === null ? CheckResult::UNKNOWN : ($count <= 3 ? CheckResult::PASS : CheckResult::FAIL),
                ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                    'document_sha256' => $this->document['sha256'] ?? null,
                    'scope' => 'linked_word_footnotes', 'maximum' => 3, 'inventory' => $inventory], '/manuscript',
                'Conferir até 3 notas de rodapé; fonte incompleta e vínculos ambíguos exigem revisão segundo STAGE9.md.'),
            new CheckResult('manuscript.footnotes.lines', $count === 0 ? CheckResult::PASS : CheckResult::UNKNOWN,
                ['implemented' => $count === 0, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                    'maximum_lines_per_note' => 2, 'note_count' => $count, 'renderer_available' => false,
                    'reason' => $count === 0 ? 'no_linked_footnotes' : 'rendered_lines_not_available'], '/manuscript',
                'Conferir até 2 linhas por nota no documento renderizado; quebras e parágrafos XML não comprovam o total visual.'),
        ];
    }
}
