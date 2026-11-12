<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\DocumentSource;

/** Declared page/body formatting, with explicit coverage and no rendering claims. */
final class ManuscriptFormatting implements Rule
{
    private $document;
    public function __construct(?array $document = null) { $this->document = $document; }

    public function evaluate(Submission $submission): array
    {
        $buckets = [];
        foreach (['page.a4', 'page.margins', 'body.font', 'body.font_size', 'body.line_spacing'] as $id) {
            $buckets[$id] = ['counts' => [CheckResult::PASS => 0, CheckResult::FAIL => 0, CheckResult::UNKNOWN => 0], 'samples' => []];
        }
        $source = DocumentSource::mainText($this->document);
        $available = $source['available'] ?? false;
        $full = $source['full_document_text_available'] ?? false;
        if ($available) {
            foreach ($source['layout']['sections'] ?? [] as $section) {
                $page = null; $margin = null; $size = $section['size_twips']; $margins = $section['margins_twips'];
                if ($section['valid'] && $size !== null && $size['w'] !== null && $size['h'] !== null) {
                    $dimensions = [$size['w'], $size['h']]; sort($dimensions);
                    $page = abs($dimensions[0] - 11906) <= 2 && abs($dimensions[1] - 16838) <= 2;
                }
                if ($section['valid'] && !$section['special_margins'] && $margins !== null && $margins['gutter'] === 0) {
                    $states = [];
                    foreach (['top', 'right', 'bottom', 'left'] as $side) {
                        $states[] = $margins[$side] === null ? null : abs($margins[$side] - 1701) <= 2;
                    }
                    $margin = $this->combine($states);
                }
                $this->record($buckets['page.a4'], $page, ['section_index' => $section['section_index'], 'size_twips' => $size]);
                $this->record($buckets['page.margins'], $margin, ['section_index' => $section['section_index'], 'margins_twips' => $margins]);
            }
            foreach ($source['paragraphs'] as $paragraph) {
                if (!preg_match('/[^\s\p{Z}]/u', $paragraph['text'])) { continue; }
                $type = $paragraph['typography'] ?? ['available' => false];
                $position = ['paragraph_index' => $paragraph['index']];
                if (!$type['available'] || !$type['runs']) {
                    $this->record($buckets['body.font'], null, $position);
                    $this->record($buckets['body.font_size'], null, $position);
                } else {
                    foreach ($type['runs'] as $run) {
                        $states = [];
                        foreach ($run['fonts'] as $font) {
                            $states[] = $font === null ? null : preg_match('/^times new roman$/iD', trim($font)) === 1;
                        }
                        $where = $position + ['run_index' => $run['run_index']];
                        $this->record($buckets['body.font'], $this->combine($states), $where);
                        $size = $run['size_half_points'];
                        $this->record($buckets['body.font_size'], $size === null || $size === 0 ? null : $size === 24,
                            $where + ['size_half_points' => $size]);
                    }
                }
                $spacing = $type['spacing'] ?? ['line' => null, 'rule' => null];
                $line = $spacing['line']; $mode = $spacing['rule'] ?? 'auto';
                $result = $type['available'] && $line !== null && $line > 0 && $mode === 'auto'
                    && !($source['layout']['grid'] ?? true) ? $line === 360 : null;
                $this->record($buckets['body.line_spacing'], $result, $position + ['line_units' => $line, 'automatic' => $mode === 'auto']);
            }
        }
        $results = [];
        foreach ($buckets as $id => $bucket) {
            $counts = $bucket['counts']; $count = array_sum($counts);
            $complete = $available && (strpos($id, 'page.') === 0 ? ($source['layout']['complete'] ?? false) : $full);
            $status = $counts[CheckResult::FAIL] ? CheckResult::FAIL
                : ($complete && $count > 0 && !$counts[CheckResult::UNKNOWN] ? CheckResult::PASS : CheckResult::UNKNOWN);
            $results[] = new CheckResult('manuscript.' . $id, $status,
                ['source_profile'=>$source['source_profile']??'unavailable','full_document_text_available'=>$full,
                    'coverage'=>$source['coverage']??[], 'implemented' => true, 'policy_version' => 'diretrizes-fornecidas-2026-11-11',
                    'document_sha256' => $this->document['sha256'] ?? null,
                    'scope' => strpos($id, 'page.') === 0 ? 'document_sections' : (($full || !$available) ? 'main_body_text' : 'verified_direct_body_word_runs'),
                    'coverage_complete' => $complete, 'counts' => $counts, 'samples' => $bucket['samples'],
                    'samples_omitted' => max(0, $count - count($bucket['samples']))], '/manuscript',
                'Revisar as propriedades declaradas conforme STAGE5.md; exceções editoriais e renderização exigem avaliação humana.', '2.0.0');
        }
        return $results;
    }

    private function combine(array $states): ?bool
    {
        if (in_array(false, $states, true)) { return false; }
        return $states && !in_array(null, $states, true) ? true : null;
    }

    private function record(array &$bucket, ?bool $value, array $position): void
    {
        $status = $value === null ? CheckResult::UNKNOWN : ($value ? CheckResult::PASS : CheckResult::FAIL);
        $bucket['counts'][$status]++;
        $sample = $position + ['status' => $status];
        if (count($bucket['samples']) < 32) { $bucket['samples'][] = $sample; }
        elseif ($status !== CheckResult::PASS) {
            foreach ($bucket['samples'] as $index => $existing) {
                if ($existing['status'] === CheckResult::PASS || ($status === CheckResult::FAIL && $existing['status'] === CheckResult::UNKNOWN)) {
                    $bucket['samples'][$index] = $sample; break;
                }
            }
        }
    }
}
