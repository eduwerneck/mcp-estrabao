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
        $endnotes = $inventory['endnote_indicators'] ?? false;
        $noNotes = $count === 0 && !$endnotes;
        $review = ['required'=>!$noNotes, 'inventory_complete'=>$complete, 'note_count'=>$count,
            'endnote_indicators'=>$endnotes, 'manual_notes_inspected'=>false,
            'samples'=>$complete ? $inventory['samples'] : [],
            'samples_omitted'=>$complete ? $inventory['samples_omitted'] : 0];
        return [
            new CheckResult('manuscript.footnotes.count', $count === null ? CheckResult::UNKNOWN : ($count <= 3 ? CheckResult::PASS : CheckResult::FAIL),
                ['implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                    'document_sha256' => $this->document['sha256'] ?? null,
                    'scope' => 'linked_word_footnotes', 'maximum' => 3, 'inventory' => $inventory], '/manuscript',
                'Conferir até 3 notas de rodapé; fonte incompleta e vínculos ambíguos exigem revisão segundo STAGE9.md.'),
            new CheckResult('manuscript.footnotes.lines', $noNotes ? CheckResult::PASS : CheckResult::UNKNOWN,
                ['implemented' => $noNotes, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                    'maximum_lines_per_note' => 2, 'note_count' => $count, 'renderer_available' => false,
                    'document_sha256' => $this->document['sha256'] ?? null,
                    'scope' => 'linked_word_footnotes_with_out_of_scope_review', 'review_plan'=>$review,
                    'reason' => $endnotes ? 'endnote_indicators_require_review' : ($noNotes ? 'no_linked_footnotes' : 'rendered_lines_not_available')], '/manuscript',
                'Conferir até 2 linhas por nota no documento paginado, incluindo continuações; notas de fim e manuais exigem conferência separada. Não inferir linhas de quebras XML.', '2.0.0'),
        ];
    }
}
