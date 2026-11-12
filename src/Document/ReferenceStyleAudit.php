<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Contracts\CheckResult;

/** Deterministic incompatibilities and typed-layout checks; never full APA certification. */
final class ReferenceStyleAudit
{
    public static function analyze(array $document, string $language): array
    {
        $list = self::referenceList($document, $language); $counts = [CheckResult::PASS => 0, CheckResult::FAIL => 0, CheckResult::UNKNOWN => 0];
        $samples = []; $abnt = 0; $types = []; $orderKeys = []; $orderUnknown = false;
        foreach ($list['paragraphs'] as $index => $paragraph) {
            $text = $paragraph['text']; $checks = []; $type = 'unresolved'; $head = self::head($text);
            if (strlen($text) > 8192) {
                self::check($checks, 'reference_size', null, 'Entrada extensa demais para esta análise; revisar manualmente.');
            } elseif ($head === null && self::abntLike($text)) {
                $abnt++; $type = 'abnt_like';
                self::check($checks, 'author_date_position', false, 'Padrão semelhante à ABNT: a data está após a fonte. Em APA 7, autor e data iniciam a referência.');
                self::check($checks, 'style_family', false, 'Há vários sinais de outro padrão bibliográfico; reestruturar a entrada em APA 7. Maiúsculas isoladas não determinam a norma.');
            } elseif ($head === null) {
                // A numbered reference can be diagnosed when removing the index yields a clear APA author/date head.
                $stripped = preg_replace('/^(?:\[[0-9]+\]|[0-9]+\.)\s+/u', '', $text, 1, $removed);
                if ($removed && self::head($stripped) !== null) {
                    self::check($checks, 'numbered_reference_list', false, 'A lista de referências APA 7 não é numerada; conferir a organização alfabética.');
                } else { self::check($checks, 'author_date_unresolved', null, 'Não foi possível identificar com segurança autoria e data; conferir o formato e o tipo de obra.'); }
            } else {
                self::check($checks, 'author_date_head', true, 'Bloco inicial de nome e data reconhecido; identidade e autoria exigem conferência.');
                self::check($checks, 'date_token_format', $head['date_format_resolved'] ? true : null, 'Conferir data completa e localização; reconhecer o formato não confirma a data real da publicação.');
                if ($head['kind'] !== 'personal') { self::check($checks, 'group_author_role', null, 'Confirmar autoria institucional ou obra sem autor antes de tratar o primeiro campo como autoria.'); }
                if ($head['kind'] === 'personal') {
                    $uppercase = false;
                    foreach ($head['authors'] as $name) { $uppercase = $uppercase || (preg_match('/\p{Lu}/u', $name) && !preg_match('/[\p{Ll}\p{Lt}]/u', $name)); }
                    self::check($checks, 'personal_author_case', $uppercase ? null : true, 'Conferir capitalização dos nomes; sobrenome em maiúsculas sozinho não prova ABNT nem autoriza reescrita.');
                    $first = $head['authors'][0];
                    if (preg_match('/^[A-Za-z]+$/D', $first)) { $orderKeys[] = ['key' => strtolower($first), 'paragraph_index' => $paragraph['index']]; }
                    else { $orderUnknown = true; }
                } else { $orderUnknown = true; }
                $content = $head['content']; $offset = $head['content_start'];
                $link = self::stripLink($content);
                if ($link['legacy_doi'] && strpos($link['base'], '. ') !== false) {
                    self::check($checks, 'doi_url_format', false, 'O DOI informado deve ser apresentado como https://doi.org/...; substituir o prefixo antigo sem alterar o identificador.');
                } elseif ($link['modern_doi']) { self::check($checks, 'doi_url_format', true, 'DOI apresentado como URL doi.org; existência e metadados não foram consultados.'); }
                // Missing DOI never fails: the journal recommends DOI when available.
                $base = $link['base']; $fields = self::fields($base, $offset);
                if ($fields !== null && $fields['type'] === 'journal_article') {
                    $type = 'journal_article';
                    self::check($checks, 'article_title_plain', self::emphasis($paragraph, $fields['title'], false), 'No artigo de periódico, o título do artigo não fica em itálico.');
                    self::check($checks, 'journal_italic', self::emphasis($paragraph, $fields['source'], true), 'O nome do periódico fica em itálico.');
                    self::check($checks, 'volume_italic', self::emphasis($paragraph, $fields['volume'], true), 'O volume fica em itálico; o número do fascículo não.');
                    self::check($checks, 'journal_page_prefix', !$fields['locator_prefix_present'], 'Em referência de artigo de periódico, a paginação não recebe prefixo p./pp.');
                    if ($fields['issue'] !== null) { self::check($checks, 'issue_plain', self::emphasis($paragraph, $fields['issue'], false), 'O fascículo entre parênteses não fica em itálico.'); }
                    self::check($checks, 'article_locator', true, 'Volume e localização do artigo reconhecidos; não confirma os dados da publicação.');
                } elseif ($fields !== null && $fields['type'] === 'chapter') {
                    $type = 'chapter';
                    self::check($checks, 'chapter_title_plain', self::emphasis($paragraph, $fields['title'], false), 'O título do capítulo não fica em itálico.');
                    self::check($checks, 'book_title_italic', self::emphasis($paragraph, $fields['source'], true), 'O título do livro que contém o capítulo fica em itálico.');
                    self::check($checks, 'chapter_publisher_plain', self::emphasis($paragraph, $fields['publisher'], false), 'O nome da editora do livro não fica em itálico.');
                    self::check($checks, 'chapter_editor_label', $fields['editor_label_valid'], 'Usar Ed. para um editor e Eds. para dois ou mais no modelo em inglês.');
                    self::check($checks, 'chapter_source', true, 'Editoria, título do livro, páginas e fonte reconhecidos; conferir a exatidão desses dados.');
                } elseif ($fields !== null && $fields['type'] === 'standalone_candidate') {
                    $titleItalic = self::emphasis($paragraph, $fields['title'], true);
                    $sourcePlain = self::emphasis($paragraph, $fields['source'], false);
                    // Text alone cannot distinguish a book/report/webpage from a serial without volume.
                    if ($titleItalic === true && $sourcePlain === true) {
                        $type = $link['web_url'] ? 'webpage_layout_candidate' : 'standalone_work_layout_candidate';
                        self::check($checks, 'standalone_title_italic', true, 'Título e fonte seguem o padrão visual de obra autônoma; confirmar o tipo bibliográfico.');
                        self::check($checks, 'standalone_source_plain', true, 'Fonte da obra autônoma sem itálico.');
                    } else { self::check($checks, 'work_type_and_italic', null, 'Confirmar se é livro, relatório, página ou periódico sem volume antes de exigir itálico em um campo específico.'); }
                } else { self::check($checks, 'work_type_unresolved', null, 'Tipo ou campos da obra não reconhecidos; revisar com o modelo APA 7 adequado.'); }
                if ($fields !== null && $type !== 'unresolved') {
                    $title = substr($text, $fields['title']['start'], $fields['title']['end'] - $fields['title']['start']);
                    $case = \Estrabao\Text\TitleCase::inspect($title, $language);
                    self::check($checks, 'work_title_full_uppercase', $case['status'] === CheckResult::UNKNOWN ? null : $case['status'] === CheckResult::PASS, 'Evitar caixa alta integral no título; sentence case, nomes próprios e siglas exigem conferência.');
                }
                if ($link['punctuation_review']) { self::check($checks, 'identifier_punctuation', null, 'Conferir a pontuação final do identificador; o ponto pode pertencer ao próprio DOI/URL.'); }
            }
            if ($head === null) { $orderUnknown = true; }
            $status = self::status($checks); $counts[$status]++; $types[$type] = ($types[$type] ?? 0) + 1;
            $sample = ['reference_index' => $index, 'paragraph_index' => $paragraph['index'], 'type' => $type, 'status' => $status, 'checks' => $checks];
            self::sample($samples, $sample);
        }
        $order = CheckResult::UNKNOWN; $orderSamples = [];
        if ($list['complete'] && $orderKeys && !$orderUnknown) {
            $order = CheckResult::PASS;
            for ($i = 1; $i < count($orderKeys); $i++) {
                $comparison = strcmp($orderKeys[$i - 1]['key'], $orderKeys[$i]['key']);
                if ($comparison > 0) { $order = CheckResult::FAIL; if (count($orderSamples) < 16) { $orderSamples[] = [$orderKeys[$i - 1]['paragraph_index'], $orderKeys[$i]['paragraph_index']]; } }
                elseif ($comparison === 0 && $order !== CheckResult::FAIL) { $order = CheckResult::UNKNOWN; }
            }
        }
        $status = !$list['complete'] || !$list['paragraphs'] ? CheckResult::UNKNOWN
            : ($counts[CheckResult::FAIL] || $order === CheckResult::FAIL ? CheckResult::FAIL
                : (!$counts[CheckResult::UNKNOWN] && $order === CheckResult::PASS && !$list['heading_review_required'] ? CheckResult::PASS : CheckResult::UNKNOWN));
        return ['coverage_complete' => $list['complete'], 'heading_review_required' => $list['heading_review_required'], 'inspection_limit_reached' => $list['limit'],
            'reference_count' => count($list['paragraphs']), 'counts' => $counts, 'types' => $types,
            'abnt_like_entries' => $abnt, 'recognized_formats_status' => $status,
            'alphabetical_order_status' => $order, 'order_scope' => 'distinct_ascii_first_personal_surnames',
            'order_samples' => $orderSamples, 'samples' => $samples, 'samples_omitted' => max(0, count($list['paragraphs']) - count($samples))];
    }

    private static function referenceList(array $document, string $language): array
    {
        $headings = ['pt' => ['Referências', 'Bibliografia'], 'en' => ['References', 'Bibliography'], 'es' => ['Referencias', 'Bibliografía']];
        $result = ['complete' => false, 'limit' => false, 'heading_review_required' => false, 'paragraphs' => []];
        if (!isset($headings[$language]) || count($document['paragraphs']) > ReferenceAnalysis::MAX_PARAGRAPHS) { $result['limit'] = isset($headings[$language]); return $result; }
        $seen = 0; $active = false; $uncertain = false;
        foreach ($document['paragraphs'] as $paragraph) {
            $text = trim($paragraph['text']);
            if (preg_match('//u', $text) !== 1 || preg_match('/\p{M}/u', $text)) { return $result; }
            if (preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . '(?:' . implode('|', array_map(function ($value) { return preg_quote($value, '/'); }, $headings[$language])) . ')' . '$/iuD', $text)) { $seen++; $active = true;
                if (!preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($headings[$language][0], '/') . '$/iuD', $text)) { $result['heading_review_required'] = true; }
                continue; }
            if (!$active || $text === '') { continue; }
            foreach (array_merge(ManuscriptSegments::CORE, ['Créditos (CRediT)', 'Fontes de Fomento']) as $heading) {
                if (preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?' . preg_quote($heading, '/') . '$/iuD', $text)) { $active = false; continue 2; }
            }
            $outline = $paragraph['outline'] ?? ['known' => false, 'level' => null];
            $bold = $paragraph['format']['bold'] ?? null;
            $lead = preg_match('/^' . ReferenceAnalysis::SURNAME . ',\s*\p{L}/u', $text) && preg_match('/(?:' . ReferenceAnalysis::YEAR_SIGNAL . ')/iu', $text);
            if (!$lead && (!$outline['known'] || $outline['level'] < 9 || $bold !== false)) { $active = false; $uncertain = true; continue; }
            $result['paragraphs'][] = $paragraph;
            if (count($result['paragraphs']) > ReferenceAnalysis::MAX_ENTRIES) { $result['limit'] = true; $result['paragraphs'] = []; return $result; }
        }
        $result['complete'] = $seen === 1 && !$uncertain;
        return $result;
    }

    private static function head(string $text): ?array
    {
        if (strlen($text) > 8192) { return null; }
        $head = ReferenceAnalysis::parseEntry($text);
        if ($head !== null) { $head['kind'] = 'personal'; $head['date_format_resolved'] = true; return $head; }
        if (strlen($text) > 8192 || !preg_match('/\((n\.d\.|s\.d\.|in press|no prelo|[0-9]{4}, [\p{L}]+(?: [0-9]{1,2})?)\)\.\s+/iu', $text, $date, PREG_OFFSET_CAPTURE)) {
            // Institutional author with an ordinary year.
            if (!preg_match('/\(([0-9]{4}[a-z]?)\)\.\s+/iu', $text, $date, PREG_OFFSET_CAPTURE)) { return null; }
        }
        $dateResolved = self::dateFormat($date[1][0]);
        $prefix = trim(substr($text, 0, $date[0][1])); $contentStart = $date[0][1] + strlen($date[0][0]);
        $content = trim(substr($text, $contentStart)); if ($content === '' || strlen($prefix) > 2048) { return null; }
        $synthetic = ReferenceAnalysis::parseEntry($prefix . ' (2020). Placeholder. Publisher.');
        if ($synthetic !== null) { return ['kind' => 'personal', 'date_format_resolved' => $dateResolved, 'authors' => $synthetic['authors'], 'content' => $content, 'content_start' => $contentStart]; }
        if (preg_match('/^[\p{L}\p{N}\s&\p{Pd}\x{2019}\x{0027}]{2,256}\.$/uD', $prefix)) {
            return ['kind' => 'group_candidate', 'date_format_resolved' => $dateResolved, 'authors' => [], 'content' => $content, 'content_start' => $contentStart];
        }
        return null;
    }

    private static function dateFormat(string $date): bool
    {
        if (preg_match('/^(?:[0-9]{4}[a-z]?|n\.d\.|s\.d\.|in press|no prelo)$/iuD', $date)) { return true; }
        if (!preg_match('/^([0-9]{4}), ([A-Za-z]+)(?: ([0-9]{1,2}))?$/D', $date, $m)) { return false; }
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        $index = array_search(strtolower($m[2]), $months, true);
        return $index !== false && checkdate($index + 1, isset($m[3]) ? (int) $m[3] : 1, (int) $m[1]);
    }

    private static function abntLike(string $text): bool
    {
        if (preg_match('/\b(?:Lei|Decreto|Constituição|Resolução|Acórdão|Portaria|Código)\b/iu', substr($text, 0, 256))) { return false; }
        $surname = ReferenceAnalysis::SURNAME;
        $lead = preg_match('~^' . $surname . ',\s*(?:\p{Lu}\.\s*){1,4}~u', $text)
            || preg_match('~^' . $surname . ',\s*\p{Lu}[\p{L}\p{Pd}]{1,40}(?:\s+\p{L}[\p{L}\p{Pd}]{0,40}){0,5}\.\s+~u', $text);
        if (!$lead) { return false; }
        $base = trim(preg_split('~(?:Disponível em|Acesso em|Available at|Accessed):|\bDOI:\s*10\.|https?://~iu', $text, 2)[0]);
        if (!preg_match('/(?:,|\.)\s*[0-9]{4}[a-z]?\.?$/iuD', $base)) { return false; }
        $place = preg_match('/\.\s+\p{Lu}[\p{L}\s.\p{Pd}]{1,64}:\s*[^:;]{2,160},\s*[0-9]{4}[a-z]?\.?$/uD', $base);
        $serial = (int) preg_match('/\bv\.\s*[0-9]+/iu', $base) + (int) preg_match('/\bn\.\s*[0-9]+/iu', $base) + (int) preg_match('/\bp\.\s*[0-9]+[-–][0-9]+/iu', $base);
        $web = preg_match('/Disponível em:\s*https?:\/\//iu', $text) && preg_match('/Acesso em:/iu', $text);
        return $place || $serial >= 2 || $web;
    }

    private static function stripLink(string $content): array
    {
        $result = ['base' => rtrim($content), 'modern_doi' => false, 'legacy_doi' => false, 'web_url' => false, 'punctuation_review' => false];
        if (preg_match('~\s+((?:doi:\s*10\.[0-9]{4,9}/|https?://)[^\s<>]+)\s*$~iuD', $content, $match, PREG_OFFSET_CAPTURE)) {
            $url = $match[1][0]; $result['base'] = rtrim(substr($content, 0, $match[0][1]));
            $result['modern_doi'] = preg_match('~^https://doi\.org/10\.[0-9]{4,9}/~i', $url) === 1;
            $result['legacy_doi'] = preg_match('~^(?:doi:\s*10\.|http://(?:dx\.)?doi\.org/10\.|https://dx\.doi\.org/10\.)~i', $url) === 1;
            $result['web_url'] = !$result['modern_doi'] && !$result['legacy_doi'];
            $result['punctuation_review'] = substr($url, -1) === '.';
        }
        return $result;
    }

    private static function fields(string $content, int $offset): ?array
    {
        $content = rtrim($content); if (substr($content, -1) === '.') { $content = substr($content, 0, -1); }
        // Chapter with a single/two editor source. More complex editors/editions are left unresolved.
        $editor = '(?:\p{Lu}\.\s*){1,4}' . ReferenceAnalysis::SURNAME;
        if (preg_match('~^(.+)[.?!] In (' . $editor . '(?: & ' . $editor . ')?) \((Ed\.|Eds\.)\), ([^()]+) \(pp\. [0-9]+[-–][0-9]+\)\. ([^.]+)$~uD', $content, $m, PREG_OFFSET_CAPTURE)) {
            return ['type' => 'chapter', 'title' => self::range($m[1], $offset), 'source' => self::range($m[4], $offset),
                'publisher' => self::range($m[5], $offset), 'editor_label_valid' => (strpos($m[2][0], '&') !== false) === ($m[3][0] === 'Eds.')];
        }
        $candidates = [];
        preg_match_all('/[.?!]\s+/u', $content, $separators, PREG_OFFSET_CAPTURE);
        foreach ($separators[0] as $separator) {
            $start = $separator[1] + strlen($separator[0]); $tail = substr($content, $start);
            if (preg_match('/^([^,()]+),\s*([0-9]+)(?:\(([^()]+)\))?,\s*(?:(pp?\.)\s*)?(?:[0-9]+[-–][0-9]+|[0-9]+|[Ee][0-9]+|Article\s+[A-Za-z0-9]+)$/uD', $tail, $m, PREG_OFFSET_CAPTURE)) {
                $candidates[] = ['type' => 'journal_article', 'title' => ['start' => $offset, 'end' => $offset + $separator[1]],
                    'source' => self::range($m[1], $offset + $start), 'volume' => self::range($m[2], $offset + $start),
                    'locator_prefix_present' => isset($m[4]) && $m[4][1] >= 0 && $m[4][0] !== '',
                    'issue' => isset($m[3]) && $m[3][1] >= 0 ? self::range($m[3], $offset + $start) : null];
            }
        }
        if (count($candidates) === 1) { return $candidates[0]; }
        if (count($candidates) > 1) { return null; }
        if (count($separators[0]) === 1) {
            $separator = $separators[0][0]; $source = $separator[1] + strlen($separator[0]);
            if ($source < strlen($content) && !preg_match('/[()]/u', substr($content, 0, $separator[1])) && !preg_match('/[():]/u', substr($content, $source))) {
                return ['type' => 'standalone_candidate', 'title' => ['start' => $offset, 'end' => $offset + $separator[1]], 'source' => ['start' => $offset + $source, 'end' => $offset + strlen($content)]];
            }
        }
        return null;
    }

    private static function range(array $capture, int $offset): array { return ['start' => $offset + $capture[1], 'end' => $offset + $capture[1] + strlen($capture[0])]; }

    private static function emphasis(array $paragraph, array $range, bool $expected): ?bool
    {
        $format = $paragraph['reference_formatting'] ?? ['available' => false];
        if (!$format['available']) { return null; }
        $covered = 0; $hasContent = false;
        foreach ($format['italic_spans'] as $span) {
            $start = max($range['start'], $span['start']); $end = min($range['end'], $span['end']);
            if ($start >= $end) { continue; }
            $text = substr($paragraph['text'], $start, $end - $start);
            if (!preg_match('/[^\s\p{Z}\p{P}]/u', $text)) { continue; }
            $hasContent = true; $covered += $end - $start;
            if ($span['italic'] !== $expected) { return false; }
        }
        return $hasContent && $covered > 0 ? true : null;
    }

    private static function check(array &$checks, string $code, ?bool $value, string $message): void
    {
        $checks[] = ['check' => $code, 'status' => $value === null ? CheckResult::UNKNOWN : ($value ? CheckResult::PASS : CheckResult::FAIL), 'message' => $message];
    }
    private static function status(array $checks): string
    {
        $states = array_column($checks, 'status');
        return in_array(CheckResult::FAIL, $states, true) ? CheckResult::FAIL : (in_array(CheckResult::UNKNOWN, $states, true) ? CheckResult::UNKNOWN : CheckResult::PASS);
    }
    private static function sample(array &$samples, array $sample): void
    {
        if (count($samples) < 32) { $samples[] = $sample; return; }
        if ($sample['status'] === CheckResult::PASS) { return; }
        foreach ($samples as $i => $old) { if ($old['status'] === CheckResult::PASS || ($sample['status'] === CheckResult::FAIL && $old['status'] === CheckResult::UNKNOWN)) { $samples[$i] = $sample; break; } }
    }
}
