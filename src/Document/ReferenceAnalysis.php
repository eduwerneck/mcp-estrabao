<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Local author-date preflight. No bibliographic authority, network or full APA validation. */
final class ReferenceAnalysis
{
    const MAX_PARAGRAPHS = 4096;
    const MAX_ENTRIES = 512;
    const MAX_CITATIONS = 2048;
    const MAX_BODY_BYTES = 1048576;
    const MAX_PARAGRAPH_BYTES = 65536;
    private const STYLE_GUIDANCE = [
        'parenthetical_authors_require_ampersand' => 'Usar & entre dois autores na citação parentética.',
        'narrative_authors_require_conjunction' => 'Na narrativa, usar conjunção textual entre os autores, não &.',
        'three_or_more_authors_require_et_al' => 'Para obra de três ou mais autores, usar o primeiro sobrenome seguido de et al. desde a primeira citação, salvo necessidade de desambiguação.',
        'et_al_period' => 'Escrever et al. com ponto após al.',
        'multi_author_disambiguation_review' => 'Conferir a necessidade de nomes adicionais para distinguir obras com o mesmo primeiro autor e ano.',
        'institutional_alias_review' => 'Conferir a apresentação do nome completo e da sigla institucional na primeira citação; vínculo literal não confirma identidade nem introdução correta.',
        'secondary_source_review' => 'Conferir a fonte efetivamente consultada na citação secundária; não exigir a obra original não consultada na lista nem presumir o histórico de leitura.',
        'secondary_apud_not_allowed' => 'A Estrabão não aceita apud em citações secundárias. Usar como citado em e identificar a fonte efetivamente consultada.',
        'citation_spacing' => 'Na parentética, inserir espaço após a vírgula; na narrativa, antes do parêntese do ano.',
        'personal_surname_full_uppercase' => 'Conferir capitalização do sobrenome; maiúsculas isoladas não provam ABNT.',
    ];
    const SURNAME = '\p{L}[\p{L}\p{Pd}\x{2019}\x{0027}]{0,63}';
    const YEAR_SIGNAL = '[0-9]{4}[a-z]?|n\.\s*d\.|s\.\s*d\.|in press|no prelo';

    public static function analyze(array $document): array
    {
        $sections = ReferenceSections::extract($document);
        $referenceTexts = $sections['references']; $bodyTexts = $sections['body']; $boundaries = $sections['complete'];
        $reasons = []; $limit = count($referenceTexts) > self::MAX_ENTRIES; $bodyBytes = 0;
        foreach ($bodyTexts as $paragraph) {
            $bodyBytes += strlen($paragraph['text']);
            if (strlen($paragraph['text']) > self::MAX_PARAGRAPH_BYTES) { $limit = true; }
        }
        if ($bodyBytes > self::MAX_BODY_BYTES) { $limit = true; }
        if ($limit) { $reasons[] = 'inspection_limit'; }
        if (!$boundaries) { $reasons[] = 'section_boundaries_unresolved'; }
        $entries = []; $referenceSamples = []; $unparsed = 0; $doi = ['url_candidates' => 0, 'legacy_candidates' => 0, 'not_detected' => 0];
        if (!$limit) {
            foreach ($referenceTexts as $index => $paragraph) {
                $entry = self::parseEntry($paragraph['text']) ?? InstitutionalAuthors::entry($paragraph['text']);
                if ($entry === null) { $unparsed++; }
                else { $entry['reference_index'] = $index; $entry['paragraph_index'] = $paragraph['index']; $entries[] = $entry; }
                $url = preg_match('~https://doi\.org/10\.[0-9]{4,9}/\S+~iu', $paragraph['text']);
                $legacy = preg_match('~doi:\s*10\.|http://(?:dx\.)?doi\.org/|https://dx\.doi\.org/~iu', $paragraph['text']);
                if ($url) { $doi['url_candidates']++; }
                if ($legacy) { $doi['legacy_candidates']++; }
                if (!$url && !$legacy) { $doi['not_detected']++; }
                $sample = ['reference_index' => $index, 'paragraph_index' => $paragraph['index'],
                    'author_date_recognized' => $entry !== null, 'author_count' => $entry === null ? null : count($entry['authors'])];
                if (count($referenceSamples) < 32) { $referenceSamples[] = $sample; }
                elseif ($entry === null) {
                    foreach ($referenceSamples as $sampleIndex => $existing) {
                        if ($existing['author_date_recognized']) { $referenceSamples[$sampleIndex] = $sample; break; }
                    }
                }
            }
        }
        $aliases = InstitutionalAuthors::aliases($bodyTexts, $entries);
        $hasInstitutional = count(array_filter($entries, function ($entry) { return ($entry['kind'] ?? null) === 'institutional'; })) > 0;
        $citations = []; $unparsedGroups = 0; $numeric = 0;
        if (!$limit) {
            foreach ($bodyTexts as $paragraph) {
                $text = $paragraph['text'];
                $numeric += preg_match_all('/\[[0-9][0-9,;\s–-]{0,128}\]/u', $text);
                if (preg_match('/(?:' . self::YEAR_SIGNAL . ')/iu', $text)
                    && (substr_count($text, '(') !== substr_count($text, ')') || preg_match('/\([^)]*\(/u', $text))) { $unparsedGroups++; }
                preg_match_all('/\(([^()]*)\)/u', $text, $groups, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
                foreach ($groups as $group) {
                    $inside = $group[1][0]; $offset = $group[0][1];
                    if (!preg_match('/(?:' . self::YEAR_SIGNAL . ')/iu', $inside)) { continue; }
                    if (strlen($inside) > 2048) { $unparsedGroups++; continue; }
                    $parsed = [];
                    if (preg_match('/^\s*([0-9]{4}[a-z]?)\s*$/iuD', $inside, $year)) {
                        $prefix = trim(substr($text, 0, $offset));
                        $prefix = preg_replace('/^(?:Segundo|Conforme|According to|Según)\s+/iu', '', $prefix);
                        $secondary = self::secondaryCitation($prefix . ', ' . $year[1], '', $entries, $aliases);
                        if ($secondary !== null) {
                            $secondary['placement'] = 'narrative';
                            $secondary['spacing_ok'] = $secondary['spacing_ok'] && $offset > 0 && preg_match('/\s$/u', substr($text, 0, $offset)) === 1;
                            $parsed[] = $secondary;
                        } else {
                            $author = InstitutionalAuthors::citation($prefix, $entries, $aliases) ?? self::citationAuthors($prefix);
                            if ($author !== null) { $parsed[] = $author + ['year' => strtolower($year[1]), 'placement' => 'narrative',
                                'spacing_ok' => $offset > 0 && preg_match('/\s$/u', substr($text, 0, $offset)) === 1]; }
                            else { $unparsedGroups++; }
                        }
                    } else {
                        foreach (explode(';', $inside) as $item) {
                            $secondary = self::secondaryCitation($item, substr($text, 0, $offset), $entries, $aliases);
                            if ($secondary !== null) { $parsed[] = $secondary; continue; }
                            if (preg_match('/^\s*(.+?),\s*([0-9]{4}[a-z]?)(?:,\s*pp?\.\s*[0-9]+(?:[-–][0-9]+)?)?\s*$/iuD', $item, $match)) {
                                $author = InstitutionalAuthors::citation(trim($match[1]), $entries, $aliases) ?? self::citationAuthors(trim($match[1]));
                                if ($author !== null) { $parsed[] = $author + ['year' => strtolower($match[2]), 'placement' => 'parenthetical', 'spacing_ok' => preg_match('/,\S/u', $item) !== 1]; continue; }
                            }
                            $unparsedGroups++;
                        }
                    }
                    foreach ($parsed as $citation) {
                        $citation['paragraph_index'] = $paragraph['index']; $citation['byte_offset'] = $offset;
                        $citations[] = $citation;
                        if (count($citations) > self::MAX_CITATIONS) { $limit = true; $reasons[] = 'inspection_limit'; break 3; }
                    }
                }
            }
        }
        $styleCounts = ['conforme' => 0, 'não conforme' => 0, 'indeterminado' => 0]; $styleSamples = [];
        $samples = []; $matched = 0; $missing = 0; $ambiguous = 0; $used = [];
        foreach ($limit ? [] : $citations as $citation) {
            // Unlinked institutional aliases cannot prove a reference absent, including in mixed lists.
            $matches = []; $matchParagraphs = []; $weak = $hasInstitutional || !empty($citation['institutional_ambiguous']);
            foreach ($entries as $entry) {
                if ($entry['year'] !== $citation['year']) { continue; }
                if (self::sameName($entry['authors'][0], $citation['authors'][0])) { $weak = true; }
                if (self::matches($citation, $entry)) { $matches[] = $entry['reference_index']; $matchParagraphs[] = $entry['paragraph_index']; }
            }
            if (count($matches) === 1) { $state = 'matched'; $matched++; $used[$matches[0]] = true; }
            elseif (count($matches) > 1 || $weak) { $state = 'ambiguous'; $ambiguous++; }
            else { $state = 'unresolved'; $missing++; }
            $styleIssues = ($citation['secondary_marker'] ?? null) === 'apud' ? ['secondary_apud_not_allowed'] : [];
            if (count($matches) === 1) {
                if (!empty($citation['secondary'])) { $styleIssues[] = 'secondary_source_review'; }
                if (!empty($citation['institutional_alias'])) { $styleIssues[] = 'institutional_alias_review'; }
                if ($citation['placement'] === 'parenthetical' && $citation['form'] === 'two_authors' && $citation['conjunction'] !== '&') { $styleIssues[] = 'parenthetical_authors_require_ampersand'; }
                if ($citation['placement'] === 'narrative' && $citation['form'] === 'two_authors' && $citation['conjunction'] === '&') { $styleIssues[] = 'narrative_authors_require_conjunction'; }
                if (in_array($citation['form'], ['many_authors', 'expanded_et_al'], true)) {
                    $cohort = 0;
                    foreach ($entries as $candidate) {
                        if ($candidate['year'] === $citation['year'] && self::sameName($candidate['authors'][0], $citation['authors'][0])) { $cohort++; }
                    }
                    // A missing/unparsed reference could require disambiguation; never guess the exception away.
                    if ($boundaries && !$unparsed && $cohort === 1) { $styleIssues[] = 'three_or_more_authors_require_et_al'; }
                    else { $styleIssues[] = 'multi_author_disambiguation_review'; }
                }
                if (isset($citation['et_al_period']) && !$citation['et_al_period']) { $styleIssues[] = 'et_al_period'; }
                if (!$citation['spacing_ok']) { $styleIssues[] = 'citation_spacing'; }
                foreach ($citation['form'] === 'institutional' ? [] : $citation['authors'] as $name) {
                    if (preg_match('/\p{Lu}/u', $name) && !preg_match('/[\p{Ll}\p{Lt}]/u', $name)) { $styleIssues[] = 'personal_surname_full_uppercase'; }
                }
                $definite = array_diff($styleIssues, ['personal_surname_full_uppercase', 'multi_author_disambiguation_review', 'institutional_alias_review', 'secondary_source_review']);
                $styleStatus = $definite ? 'não conforme' : ($styleIssues ? 'indeterminado' : 'conforme');
            } else { $styleStatus = in_array('secondary_apud_not_allowed', $styleIssues, true) ? 'não conforme' : 'indeterminado'; }
            $styleCounts[$styleStatus]++;
            $styleSample = ['paragraph_index' => $citation['paragraph_index'], 'byte_offset' => $citation['byte_offset'],
                'status' => $styleStatus, 'issues' => array_values(array_unique($styleIssues)),
                'guidance' => array_map(function ($code) { return self::STYLE_GUIDANCE[$code]; }, array_values(array_unique($styleIssues)))];
            if (count($styleSamples) < 32) { $styleSamples[] = $styleSample; }
            elseif ($styleStatus !== 'conforme') { foreach ($styleSamples as $i => $old) { if ($old['status'] === 'conforme') { $styleSamples[$i] = $styleSample; break; } } }
            $sample = ['paragraph_index' => $citation['paragraph_index'], 'byte_offset' => $citation['byte_offset'],
                'form' => $citation['form'], 'state' => $state, 'secondary' => !empty($citation['secondary']), 'secondary_marker' => $citation['secondary_marker'] ?? null,
                'guidance' => $state === 'matched' ? 'Chave ligada a uma referência reconhecida; conferir a exatidão dos dados da obra.'
                    : ($state === 'ambiguous' ? 'Conferir autores, ano, sufixo e possíveis obras com a mesma chave.' : 'Conferir autoria, ano e sufixo, e se a obra citada consta da lista nos formatos reconhecidos.'), 'reference_indices' => array_slice($matches, 0, 8), 'reference_paragraph_indices' => array_slice($matchParagraphs, 0, 8),
                'reference_matches_omitted' => max(0, count($matches) - 8)];
            if (count($samples) < 32) { $samples[] = $sample; }
            elseif ($state !== 'matched') {
                foreach ($samples as $index => $existing) { if ($existing['state'] === 'matched') { $samples[$index] = $sample; break; } }
            }
        }
        $closed = $boundaries && !$limit && count($referenceTexts) > 0 && !$unparsed && !$unparsedGroups && !$numeric && !$ambiguous;
        if ($unparsed) { $reasons[] = 'unparsed_reference_entries'; }
        if ($unparsedGroups) { $reasons[] = 'unparsed_citation_groups'; }
        if ($numeric) { $reasons[] = 'numeric_citation_candidates'; }
        if ($ambiguous) { $reasons[] = 'ambiguous_author_date_match'; }
        return ['citation_style_counts' => $styleCounts, 'citation_style_samples' => $styleSamples,
            'citation_style_samples_omitted' => max(0, array_sum($styleCounts) - count($styleSamples)), 'segmentation_complete' => $boundaries, 'closed_subset' => $closed, 'inspection_limit_reached' => $limit,
            'reference_count' => count($referenceTexts), 'parsed_references' => count($entries), 'unparsed_references' => $unparsed,
            'recognized_citations' => count($citations), 'unparsed_citation_groups' => $unparsedGroups,
            'numeric_citation_candidates' => $numeric, 'matched_citations' => $matched, 'unresolved_citations' => $missing,
            'ambiguous_matches' => $ambiguous, 'references_not_observed_in_supported_citations' => count($entries) - count($used),
            'doi_signals' => $doi, 'reasons' => array_values(array_unique($reasons)), 'reference_samples' => $referenceSamples,
            'reference_samples_omitted' => max(0, count($referenceTexts) - count($referenceSamples)),
            'citation_samples' => $samples, 'citation_samples_omitted' => max(0, count($citations) - count($samples))];
    }

    public static function parseEntry(string $text): ?array
    {
        if (strlen($text) > 8192 || !preg_match('/\(([0-9]{4}[a-z]?)\)\.\s+/iu', $text, $date, PREG_OFFSET_CAPTURE)) { return null; }
        $prefix = trim(substr($text, 0, $date[0][1]));
        $rest = trim(substr($text, $date[0][1] + strlen($date[0][0])));
        if ($rest === '' || strlen($prefix) > 2048) { return null; }
        $person = '(' . self::SURNAME . '),\s*(?:\p{Lu}\.\s*){1,4}';
        preg_match_all('~' . $person . '~u', $prefix, $names, PREG_OFFSET_CAPTURE);
        if (!array_column($names[1], 0) || count(array_column($names[1], 0)) > 20) { return null; }
        $shape = preg_replace('~' . $person . '~u', '@', $prefix);
        if (!preg_match('/^@\s*$/D', $shape) && !preg_match('/^@\s*(?:,\s*@\s*)*,\s*&\s*@\s*$/D', $shape)) { return null; }
        if (preg_match_all('~' . $person . '\s+\([0-9]{4}[a-z]?\)\.~u', $text) > 1) { return null; }
        $ranges = [];
        foreach ($names[1] as $name) { $ranges[] = ['start' => $name[1], 'end' => $name[1] + strlen($name[0])]; }
        return ['authors' => array_column($names[1], 0), 'year' => strtolower($date[1][0]), 'author_ranges' => $ranges,
            'author_key' => $prefix, 'content' => $rest,
            'content_start' => $date[0][1] + strlen($date[0][0])];
    }

    private static function secondaryCitation(string $text, string $prefix, array $entries, array $aliases): ?array
    {
        if (strlen($text)>2048 || preg_match_all('/\b(?:como citado em|as cited in|apud)\b/iu',$text)!==1
            || !preg_match('/^\s*(.*?)\s*\b(como citado em|as cited in|apud)\b\s+(.+?)\s*$/iuD',$text,$m)) { return null; }
        $original=trim($m[1], " ,\t\r\n");
        if ($original==='' || preg_match('/^[0-9]{4}[a-z]?$/iuD',$original)) {
            $original=trim(preg_replace('/^(?:Segundo|Conforme|According to|Según)\s+/iu','',trim($prefix)));
        } else { $original=trim(preg_replace('/,\s*[0-9]{4}[a-z]?$/iu','',$original)); }
        if (self::citationAuthors($original)===null && !InstitutionalAuthors::name($original)) { return null; }
        if (!preg_match('/^(.+?),\s*([0-9]{4}[a-z]?)(?:,\s*pp?\.\s*[0-9]+(?:[-–][0-9]+)?)?$/iuD',$m[3],$source)) { return null; }
        $name=trim($source[1]);
        $author=InstitutionalAuthors::citation($name,$entries,$aliases) ?? self::citationAuthors($name);
        if ($author===null && InstitutionalAuthors::name($name)) { $author=['authors'=>[$name],'form'=>'institutional','institutional_alias'=>false]; }
        if ($author===null) { return null; }
        return $author+['year'=>strtolower($source[2]),'placement'=>'parenthetical','spacing_ok'=>preg_match('/,\S/u',$text)!==1,
            'secondary'=>true,'secondary_marker'=>preg_match('/^apud$/iuD',$m[2])?'apud':'as_cited_in'];
    }

    private static function citationAuthors(string $text): ?array
    {
        $name = self::SURNAME;
        if (preg_match('~^(' . $name . ')\s+et\s+al(\.)?$~iuD', $text, $match) && self::citationName($match[1])) { return ['authors' => [$match[1]], 'form' => 'et_al', 'et_al_period' => !empty($match[2])]; }
        // Multi-author forms remain bounded and use only the existing single-surname grammar.
        if (strlen($text) <= 2048) {
            $expanded = preg_match('~^((?:' . $name . ',\s*){1,18}' . $name . '),?\s+et\s+al(\.)?$~iuD', $text, $match);
            if ($expanded) {
                $authors = preg_split('/,\s*/u', $match[1]);
                if (count(array_filter($authors, [self::class, 'citationName'])) === count($authors)) {
                    return ['authors'=>$authors, 'form'=>'expanded_et_al', 'et_al_period'=>!empty($match[2])];
                }
            }
            if (preg_match('~^((?:' . $name . ',\s*){1,18}' . $name . ')(?:,?\s+(&|e|and|y)\s+(' . $name . '))?$~iuD', $text, $match)) {
                $authors = preg_split('/,\s*/u', $match[1]);
                if (isset($match[3]) && $match[3] !== '') { $authors[] = $match[3]; }
                if (count($authors) >= 3 && count(array_filter($authors, [self::class, 'citationName'])) === count($authors)) {
                    return ['authors'=>$authors, 'form'=>'many_authors'];
                }
            }
        }
        if (preg_match('~^(' . $name . ')\s+(&|e|and|y)\s+(' . $name . ')$~iuD', $text, $match) && self::citationName($match[1]) && self::citationName($match[3])) { return ['authors' => [$match[1], $match[3]], 'form' => 'two_authors', 'conjunction' => $match[2]]; }
        if (preg_match('~^(' . $name . ')$~uD', $text, $match) && self::citationName($match[1])) {
            if (preg_match('/^(?:Em|No|Na|Ano|Desde|Até|Dados|Figura|Tabela|Gráfico|Quadro)$/iuD', $match[1])) { return null; }
            return ['authors' => [$match[1]], 'form' => 'single_author'];
        }
        return null;
    }

    private static function citationName(string $name): bool
    {
        return preg_match_all('/\p{L}/u', $name) >= 2; // Single-letter symbols are ambiguous.
    }

    private static function matches(array $citation, array $entry): bool
    {
        if (!empty($citation['institutional_ambiguous'])) { return false; }
        if (($entry['kind'] ?? null) === 'institutional' || $citation['form'] === 'institutional') {
            return ($entry['kind'] ?? null) === 'institutional' && $citation['form'] === 'institutional' && self::sameName($citation['authors'][0], $entry['authors'][0]);
        }
        if ($citation['form'] === 'expanded_et_al') {
            if (count($entry['authors']) <= count($citation['authors'])) { return false; }
            foreach ($citation['authors'] as $index=>$name) { if (!self::sameName($name, $entry['authors'][$index])) { return false; } }
            return true;
        }
        if ($citation['form'] === 'et_al') { return count($entry['authors']) >= 3 && self::sameName($citation['authors'][0], $entry['authors'][0]); }
        if (count($citation['authors']) !== count($entry['authors'])) { return false; }
        foreach ($citation['authors'] as $index => $name) { if (!self::sameName($name, $entry['authors'][$index])) { return false; } }
        return true;
    }

    private static function sameName(string $left, string $right): bool
    {
        return preg_match('/^' . preg_quote($left, '/') . '$/iuD', $right) === 1;
    }


}
