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
        'et_al_disambiguation_required' => 'A forma abreviada coincide com mais de uma referência. Incluir os nomes necessários para distinguir as obras e conferir a autoria da fonte consultada.',
        'et_al_minimum_omission' => 'Usar et al. apenas quando a expressão substituir pelo menos dois autores; apresentar os nomes necessários conforme a fonte consultada.',
        'et_al_period' => 'Escrever et al. com ponto após al.',
        'multi_author_disambiguation_review' => 'Conferir a necessidade e a extensão dos nomes adicionais para distinguir obras com o mesmo primeiro autor e ano.',
        'institutional_alias_review' => 'Conferir a apresentação do nome completo e da sigla institucional na primeira citação; vínculo literal não confirma identidade nem introdução correta.',
        'full_date_source_review' => 'Conferir a obra e a data completa declarada; a ligação autor–ano não confirma identidade, edição ou sufixos e não atribui letras automaticamente.',
        'citation_language_conjunction_review' => 'Conferir a conjunção narrativa no idioma declarado; não traduzir nomes nem presumir erro em trecho de outro idioma.',
        'secondary_source_review' => 'Conferir a fonte efetivamente consultada na citação secundária; não exigir a obra original não consultada na lista nem presumir o histórico de leitura.',
        'secondary_apud_not_allowed' => 'A Estrabão não aceita apud em citações secundárias. Usar como citado em e identificar a fonte efetivamente consultada.',
        'citation_year_suffix_mismatch' => 'Conferir o ano e seu sufixo na citação e na referência correspondente à obra efetivamente consultada.',
        'citation_year_list_order' => 'Apresentar em ordem cronológica os anos reunidos sob a mesma autoria na citação; conferir a autoria se os nomes representam pessoas diferentes.',
        'citation_group_alphabetical_order' => 'Apresentar em ordem alfabética de autoria as obras de autores distintos reunidas na citação parentética.',
        'citation_year_list_duplicates_review' => 'Conferir anos repetidos na mesma citação e eventuais localizadores; não remover obras automaticamente.',
        'citation_year_list_authorship_review' => 'Conferir se os anos agrupados pertencem à mesma autoria; sobrenomes iguais com iniciais diferentes não comprovam a mesma pessoa.',
        'citation_group_order_review' => 'Conferir a organização da citação múltipla; autorias ambíguas, secundárias, instituições, acentos e empates exigem revisão.',
        'author_year_disambiguation_review' => 'Conferir a identificação da autoria, ano, sufixos e possíveis duplicatas; não atribuir letras ou identidades automaticamente.',
        'special_date_review' => 'Conferir na fonte a ausência de data ou a situação no prelo e manter a identificação coerente entre citação e referência; o marcador reconhecido não confirma o estado de publicação.',
        'citation_page_range_order' => 'Conferir o intervalo de páginas informado: a página final não pode anteceder a inicial; verificar a localização na fonte.',
        'citation_page_marker' => 'Usar p. para uma página e pp. para um intervalo de páginas no localizador reconhecido.',
        'citation_locator_review' => 'Conferir o localizador informado e a localização do trecho na fonte; parágrafo ou marca temporal reconhecidos não confirmam o tipo de obra nem a fidelidade.',
        'quoted_citation_review' => 'Conferir a citação reproduzida dentro das aspas; não presumir consulta à obra interna nem corrigir automaticamente o conteúdo transcrito.',
        'direct_quote_source_review' => 'Confirmar se o trecho entre aspas é citação direta e conferir fidelidade, fonte e localizador apropriado; ausência de p. não comprova ausência de localização.',
        'direct_quote_block_review' => 'Conferir o candidato a citação longa: verificar o limite de 40 palavras, bloco e aspas externas; apresentação visual e fidelidade exigem revisão.',
        'direct_quote_boundary_review' => 'Conferir a contagem no limite de 40 palavras para decidir entre aspas e bloco; a tokenização disponível não resolve essa fronteira.',
        'citation_spacing' => 'Na parentética, inserir espaço após a vírgula; na narrativa, antes do parêntese do ano.',
        'personal_surname_full_uppercase' => 'Conferir capitalização do sobrenome; maiúsculas isoladas não provam ABNT.',
    ];
    private const REVIEW_ISSUES = ['full_date_source_review','citation_language_conjunction_review','personal_surname_full_uppercase','multi_author_disambiguation_review','institutional_alias_review','secondary_source_review','citation_year_list_duplicates_review','citation_year_list_authorship_review','citation_group_order_review','author_year_disambiguation_review','special_date_review','citation_locator_review','direct_quote_source_review','direct_quote_block_review','direct_quote_boundary_review','quoted_citation_review'];
    const SURNAME = '\p{L}[\p{L}\p{Pd}\x{2019}\x{0027}]{0,63}';
    private const FULL_DATE_HEAD = '\s*[0-9]{4}[a-z]?\s*,[^)]{1,8192}';
    const DATE_TOKEN = '(?:[0-9]{4}[a-z]?|[ns]\\.\\s*d\\.(?:-[a-z])?|in press|no prelo)';
    const YEAR_SIGNAL = '[0-9]{4}[a-z]?|n\.\s*d\.|s\.\s*d\.|in press|no prelo';

    public static function analyze(array $document, bool $etAlContextComplete = true, string $language = 'pt'): array
    {
        $sections = ReferenceSections::extract($document,$language);
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
                if($entry!==null && preg_match('/\('.self::FULL_DATE_HEAD.'\)\s*\.\s+/iu',$paragraph['text'])){$entry=null;}
                if($entry===null){$entry=self::fullDateEntry($paragraph['text']);}
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
        $fullDateCount=count(array_filter($entries,function($e){return !empty($e['full_date']);}));
        $aliases = InstitutionalAuthors::aliases($bodyTexts, $entries);
        $personalPattern = self::citationPattern($entries);
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
                    $parsed = []; $beforeUnparsed = $unparsedGroups;
                    $balanced = substr_count($text, '(') === substr_count($text, ')') && !preg_match('/\([^)]*\(/u', $text);
                    if (preg_match('/^\s*(' . self::DATE_TOKEN . '(?:,\s*' . self::DATE_TOKEN . '){0,19})(?:,\s*(' . CitationLocator::PATTERN . '))?\s*$/iuD', $inside, $year)) {
                        $prefix = trim(substr($text, 0, $offset));
                        $prefix = preg_replace('/^(?:Segundo|Conforme|According to|Según)\s+/iu', '', $prefix);
                        $yearList = self::yearList($year[1]);
                        $narrativeLocator = isset($year[2]) && $year[2] !== '' ? $year[2] : null;
                        if (count($yearList) > 1 && $narrativeLocator !== null) { $unparsedGroups++; continue; }
                        $secondary = count($yearList) === 1 ? self::secondaryCitation($prefix . ', ' . $year[1], '', $entries, $aliases, $personalPattern, $language) : null;
                        if ($secondary !== null) {
                            $secondary['placement'] = 'narrative';
                            $secondary['spacing_ok'] = $secondary['spacing_ok'] && $offset > 0 && preg_match('/\s$/u', substr($text, 0, $offset)) === 1;
                            $parsed[] = $secondary;
                        } else {
                            $author = InstitutionalAuthors::citation($prefix, $entries, $aliases) ?? self::citationAuthors($prefix, $personalPattern, $language);
                            if (count($yearList)>1 && $author!==null) {
                                $candidate=false;foreach ($entries as $entry) { if (self::matches($author,$entry)) { $candidate=true;break; } }
                                if (!$candidate) { $author=null; }
                            }
                            if ($author !== null) {
                                foreach ($yearList as $value) { $parsed[] = $author + ['locator'=>$narrativeLocator,'year'=>$value,'placement'=>'narrative','item_index'=>0,'multiple_years'=>count($yearList)>1,
                                    'spacing_ok'=>$offset>0 && preg_match('/\s$/u',substr($text,0,$offset))===1 && preg_match('/,\S/u',$inside)!==1]; }
                            }
                            else { $unparsedGroups++; }
                        }
                    } else {
                        foreach (explode(';', $inside) as $itemIndex => $item) {
                            if (preg_match('/^\s*(.+?),\s*(' . self::DATE_TOKEN . '(?:,\s*' . self::DATE_TOKEN . '){1,19})\s*$/iuD',$item,$years)) {
                                $author=InstitutionalAuthors::citation(trim($years[1]),$entries,$aliases) ?? self::citationAuthors(trim($years[1]),$personalPattern,$language);
                                if ($author!==null) {
                                    foreach (self::yearList($years[2]) as $value) { $parsed[]=$author+['year'=>$value,'placement'=>'parenthetical','item_index'=>$itemIndex,'multiple_years'=>true,'spacing_ok'=>preg_match('/,\S/u',$item)!==1]; }
                                    continue;
                                }
                            }
                            $secondary = self::secondaryCitation($item, substr($text, 0, $offset), $entries, $aliases, $personalPattern, $language);
                            if ($secondary !== null) { $parsed[] = $secondary + ['item_index'=>$itemIndex]; continue; }
                            if (preg_match('/^\s*(.+?),\s*(' . self::DATE_TOKEN . ')(?:,\s*(' . CitationLocator::PATTERN . '))?\s*$/iuD', $item, $match)) {
                                $author = InstitutionalAuthors::citation(trim($match[1]), $entries, $aliases) ?? self::citationAuthors(trim($match[1]), $personalPattern, $language);
                                if ($author !== null) { $parsed[] = $author + ['locator'=>(isset($match[3]) && $match[3]!==''?$match[3]:null), 'year' => self::dateKey($match[2]), 'placement' => 'parenthetical', 'item_index'=>$itemIndex, 'spacing_ok' => preg_match('/,\S/u', $item) !== 1]; continue; }
                            }
                            $unparsedGroups++;
                        }
                    }
                    $groupIssues = self::groupIssues($parsed,$entries,$boundaries && !$unparsed && !$fullDateCount && $balanced && $beforeUnparsed === $unparsedGroups);
                    foreach ($parsed as $parsedIndex => $citation) {
                        $citation['group_issues']=$groupIssues[$parsedIndex]??[];
                        $citation['syntax_complete']=$balanced;
                        $citation['group_complete']=$beforeUnparsed===$unparsedGroups;
                        $citation['inside_quotes']=CitationLocator::withinQuotes($text,$offset);
                        $citation['group_item_count']=count($parsed);$citation['group_length']=strlen($group[0][0]);
                        $citation['paragraph_index'] = $paragraph['index']; $citation['byte_offset'] = $offset;
                        $citations[] = $citation;
                        if (count($citations) > self::MAX_CITATIONS) { $limit = true; $reasons[] = 'inspection_limit'; break 3; }
                    }
                }
            }
        }
        $styleCounts = ['conforme' => 0, 'não conforme' => 0, 'indeterminado' => 0]; $styleSamples = [];
        $etAlComplete=$etAlContextComplete && !$fullDateCount && $boundaries && !$unparsed && !$unparsedGroups && !$limit && $personalPattern!==null && !count(array_filter($citations,function ($c) { return !empty($c['secondary']) || !empty($c['inside_quotes']); }));
        $etAlCache=[];
        $quoteCounts=['quoted_citation_candidates'=>0,'candidate_count'=>0,'long_candidates'=>0,'boundary_candidates'=>0,'missing_locator_candidates'=>0];
        $bodyByIndex=[];foreach($bodyTexts as $p){$bodyByIndex[$p['index']]=$p['text'];}
        $samples = []; $matched = 0; $missing = 0; $ambiguous = 0; $used = [];
        foreach ($limit ? [] : $citations as $citation) {
            // Unlinked institutional aliases cannot prove a reference absent, including in mixed lists.
            $matches = []; $matchParagraphs = []; $weak = $hasInstitutional || !empty($citation['institutional_ambiguous']) || !empty($citation['inside_quotes']);
            foreach ($entries as $entry) {
                if (substr($entry['year'],0,4) === substr($citation['year'],0,4) && self::weakName($entry['authors'][0], $citation['authors'][0])) { $weak = true; }
                if (self::originalCandidate($citation,$entry)) { $weak=true; }
                // A different date state for a possibly related author is inconclusive, never a missing-work proof.
                if ((self::specialDate($entry['year']) || self::specialDate($citation['year'])) && self::weakName($entry['authors'][0],$citation['authors'][0])) { $weak=true; }
                if ($entry['year'] !== $citation['year']) { continue; }
                if (self::matches($citation, $entry)) { $matches[] = $entry['reference_index']; $matchParagraphs[] = $entry['paragraph_index']; }
            }
            $suffixIssue=self::suffixIssue($citation,$entries,$boundaries && !$unparsed && !$fullDateCount);
            if ($suffixIssue!==null) { $weak=true; }
            $fullDateRelated=[];
            foreach($entries as $entry){if(!empty($entry['full_date']) && self::weakName($entry['authors'][0],$citation['authors'][0]) && (substr($entry['year'],0,4)===substr($citation['year'],0,4) || self::originalCandidate($citation,$entry))){$fullDateRelated[]=$entry;}}
            $fullDateAmbiguous=false;
            if($fullDateRelated){
                $cohort=0;foreach($entries as $entry){if(substr($entry['year'],0,4)===substr($citation['year'],0,4) && self::weakName($entry['authors'][0],$citation['authors'][0])){$cohort++;}}
                $fullDateAmbiguous=$unparsed>0 || !$boundaries || $cohort!==1 || preg_match('/^[0-9]{4}$/D',$citation['year'])!==1;
                foreach($fullDateRelated as $entry){if(!empty($entry['original_year']) || !empty($entry['original_year_review'])){$fullDateAmbiguous=true;}}
            }
            if (count($matches) === 1 && !$fullDateAmbiguous) { $state = 'matched'; $matched++; $used[$matches[0]] = true; }
            elseif (count($matches) > 1 || $weak || $fullDateAmbiguous) { $state = 'ambiguous'; $ambiguous++; }
            else { $state = 'unresolved'; $missing++; }
            $styleIssues = $citation['group_issues'] ?? [];
            if($fullDateRelated){$styleIssues[]='full_date_source_review';}
            $direct=CitationLocator::inspect($bodyByIndex[$citation['paragraph_index']],$citation,count($matches),$language);
            $styleIssues=array_merge($styleIssues,$direct['issues']);
            if($direct['quote_candidate']){$quoteCounts['candidate_count']++;if($direct['long']){$quoteCounts['long_candidates']++;}if($direct['boundary']){$quoteCounts['boundary_candidates']++;}if($direct['missing_locator']){$quoteCounts['missing_locator_candidates']++;}}
            if (self::specialDate($citation['year'])) { $styleIssues[]='special_date_review'; }
            if (($citation['secondary_marker'] ?? null) === 'apud') { $styleIssues[]='secondary_apud_not_allowed'; }
            if ($suffixIssue!==null) { $styleIssues[]=$suffixIssue; }
            $etAlIssue=null;
            if (in_array($citation['form'],['et_al','expanded_et_al','many_authors'],true)) {
                $cacheKey=hash('sha256',json_encode([$citation['form'],$citation['authors'],$citation['year']]));
                if (!array_key_exists($cacheKey,$etAlCache)) { $etAlCache[$cacheKey]=self::etAlIssue($citation,$entries,$aliases,$etAlComplete); }
                $etAlIssue=$etAlCache[$cacheKey];
                if ($etAlIssue!==null) { $styleIssues[]=$etAlIssue; }
            }
            if (count($matches)!==1 && isset($citation['et_al_period']) && !$citation['et_al_period'] && $etAlIssue!==null && !in_array($etAlIssue,self::REVIEW_ISSUES,true)) { $styleIssues[]='et_al_period'; }
            if (count($matches) === 1) {
                if (!empty($citation['secondary'])) { $styleIssues[] = 'secondary_source_review'; }
                if (!empty($citation['institutional_alias'])) { $styleIssues[] = 'institutional_alias_review'; }
                if ($citation['placement'] === 'parenthetical' && $citation['form'] === 'two_authors' && $citation['conjunction'] !== '&') { $styleIssues[] = 'parenthetical_authors_require_ampersand'; }
                if ($citation['placement'] === 'narrative' && $citation['form'] === 'two_authors' && $citation['conjunction'] === '&') { $styleIssues[] = 'narrative_authors_require_conjunction'; }
                if ($citation['placement']==='narrative' && $citation['form']==='two_authors' && $citation['conjunction']!=='&'
                    && (($language==='en' && strtolower($citation['conjunction'])!=='and')
                        || ($language==='es' && !in_array(strtolower($citation['conjunction']),['y','e'],true)))) { $styleIssues[]='citation_language_conjunction_review'; }
                if (isset($citation['et_al_period']) && !$citation['et_al_period']) { $styleIssues[] = 'et_al_period'; }
                if (!$citation['spacing_ok']) { $styleIssues[] = 'citation_spacing'; }
                foreach ($citation['form'] === 'institutional' ? [] : $citation['authors'] as $name) {
                    if (preg_match('/\p{Lu}/u', $name) && !preg_match('/[\p{Ll}\p{Lt}]/u', $name)) { $styleIssues[] = 'personal_surname_full_uppercase'; }
                }
            }
            if (!empty($citation['inside_quotes'])) { $styleIssues=['quoted_citation_review'];$quoteCounts['quoted_citation_candidates']++; }
            $definite=array_diff($styleIssues,self::REVIEW_ISSUES);
            $styleStatus=$definite?'não conforme':($styleIssues || count($matches)!==1?'indeterminado':'conforme');
            $styleCounts[$styleStatus]++;
            $styleSample = ['paragraph_index' => $citation['paragraph_index'], 'byte_offset' => $citation['byte_offset'],
                'status' => $styleStatus, 'locator_kind'=>$direct['kind'], 'quote_candidate'=>$direct['quote_candidate'], 'word_interval'=>$direct['word_interval'], 'issues' => array_values(array_unique($styleIssues)),
                'guidance' => array_map(function ($code) use ($language) {
                    if ($code==='secondary_apud_not_allowed' && $language!=='pt') { return 'A Estrabão não aceita apud em citações secundárias. Usar '.($language==='en'?'as cited in':'como se citó en').' e identificar a fonte efetivamente consultada.'; }
                    return self::STYLE_GUIDANCE[$code]; }, array_values(array_unique($styleIssues)))];
            if (count($styleSamples) < 32) { $styleSamples[] = $styleSample; }
            elseif ($styleStatus !== 'conforme') { foreach ($styleSamples as $i => $old) { if ($old['status'] === 'conforme' || ($styleStatus === 'não conforme' && $old['status'] === 'indeterminado')) { $styleSamples[$i] = $styleSample; break; } } }
            $sample = ['paragraph_index' => $citation['paragraph_index'], 'byte_offset' => $citation['byte_offset'],
                'form' => $citation['form'], 'state' => $state, 'secondary' => !empty($citation['secondary']), 'secondary_marker' => $citation['secondary_marker'] ?? null, 'multiple_years'=>!empty($citation['multiple_years']),
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
        return ['full_date_references'=>$fullDateCount,'full_date_matching_profile'=>'unique_personal_author_year_v1','language'=>$language,'citation_language_profile'=>'declared_pt_en_es_v1','direct_quote_counts'=>$quoteCounts,'direct_quote_review'=>CitationLocator::reviewGuidance($quoteCounts), 'citation_style_counts' => $styleCounts, 'citation_style_samples' => $styleSamples,
            'citation_style_samples_omitted' => max(0, array_sum($styleCounts) - count($styleSamples)), 'segmentation_complete' => $boundaries, 'closed_subset' => $closed, 'inspection_limit_reached' => $limit,
            'et_al_context_complete'=>$etAlComplete,'et_al_cohort_limit'=>32,'compound_name_lookup_complete' => $personalPattern !== null, 'reference_count' => count($referenceTexts), 'parsed_references' => count($entries), 'unparsed_references' => $unparsed,
            'recognized_citations' => count($citations), 'unparsed_citation_groups' => $unparsedGroups,
            'numeric_citation_candidates' => $numeric, 'matched_citations' => $matched, 'unresolved_citations' => $missing,
            'ambiguous_matches' => $ambiguous, 'references_not_observed_in_supported_citations' => count($entries) - count($used),
            'doi_signals' => $doi, 'reasons' => array_values(array_unique($reasons)), 'reference_samples' => $referenceSamples,
            'reference_samples_omitted' => max(0, count($referenceTexts) - count($referenceSamples)),
            'citation_samples' => $samples, 'citation_samples_omitted' => max(0, count($citations) - count($samples))];
    }

    /** Matcher-only projection of a valid, explicit day/month/year; never changes source text or formatting audit. */
    private static function fullDateEntry(string $text): ?array
    {
        $pattern='[0-9]{4}, (?:[\\p{L}]+ [0-9]{1,2}|[0-9]{1,2} de [\\p{L}]+)';
        $heads=self::DATE_TOKEN.'|'.self::FULL_DATE_HEAD;
        if(strlen($text)>8192 || preg_match_all('/\\((?:'.$heads.')\\)\\s*\\.\\s+/iu',$text)!==1
            || !preg_match('/\\(('.$pattern.')\\)\\.\\s+/iu',$text,$date,PREG_OFFSET_CAPTURE)){return null;}
        $parts=WebReference::publicationParts($date[1][0]);if($parts===null){return null;}
        $normalized=substr_replace($text,(string)$parts[0],$date[1][1],strlen($date[1][0]));
        $entry=self::parseEntry($normalized);if($entry===null){return null;}
        $entry['content_start']+=strlen($date[1][0])-4;$entry['full_date']=true;
        return $entry;
    }

    public static function parseEntry(string $text): ?array
    {
        if (strlen($text) > 8192 || !preg_match('/\((' . self::DATE_TOKEN . ')\)\.\s+/iu', $text, $date, PREG_OFFSET_CAPTURE)) { return null; }
        $prefix = trim(substr($text, 0, $date[0][1]));
        $rest = trim(substr($text, $date[0][1] + strlen($date[0][0])));
        if ($rest === '' || strlen($prefix) > 2048) { return null; }
        $person = '(' . ApaPersonalAuthors::surnamePattern() . '),\s*(?:\p{Lu}\.\s*){1,4}';
        preg_match_all('~' . $person . '~u', $prefix, $names, PREG_OFFSET_CAPTURE);
        if (!array_column($names[1], 0) || count(array_column($names[1], 0)) > 20) { return null; }
        $shape = preg_replace('~' . $person . '~u', '@', $prefix);
        if (!preg_match('/^@\s*$/D', $shape) && !preg_match('/^@\s*(?:,\s*@\s*)*,\s*&\s*@\s*$/D', $shape)) { return null; }
        if (preg_match_all('~' . $person . '\s+\(' . self::DATE_TOKEN . '\)\.~iu', $text) > 1) { return null; }
        $ranges = [];
        foreach ($names[1] as $name) { $ranges[] = ['start' => $name[1], 'end' => $name[1] + strlen($name[0])]; }
        $original=ReferenceRevisions::original($rest,0);
        return ['original_year'=>$original['year'],'original_year_review'=>$original['has_marker'] && $original['year']===null, 'authors' => array_column($names[1], 0), 'year' => self::dateKey($date[1][0]), 'author_ranges' => $ranges,
            'author_key' => $prefix, 'personal_keys'=>array_map('trim',array_column($names[0],0)), 'content' => $rest,
            'content_start' => $date[0][1] + strlen($date[0][0])];
    }

    /** Determine adequate literal representations, never infer which work was read. */
    private static function etAlIssue(array $citation,array $entries,array $aliases,bool $complete): ?string
    {
        $review='multi_author_disambiguation_review';
        if (!$complete || self::specialDate($citation['year'])) { return $review; }
        foreach ($aliases as $alias=>$names) { if (self::sameName($alias,$citation['authors'][0])) { return $review; } }
        foreach ($entries as $entry) { if (self::originalCandidate($citation,$entry) && $entry['year']!==$citation['year']) { return $review; } }
        // Newly recognized undated states must not strengthen a numeric-year disambiguation proof.
        foreach ($entries as $entry) { if (self::specialDate($entry['year']) && self::weakName($entry['authors'][0],$citation['authors'][0])) { return $review; } }
        $cohort=[];
        foreach ($entries as $entry) {
            if ($entry['year']!==$citation['year'] || !self::weakName($entry['authors'][0],$citation['authors'][0])) { continue; }
            if (($entry['kind']??null)==='institutional' || !self::sameName($entry['authors'][0],$citation['authors'][0]) || !isset($entry['personal_keys'][0])) { return $review; }
            $cohort[]=$entry;if (count($cohort)>32) { return $review; }
        }
        if (!$cohort) { return $review; }
        $first=$cohort[0]['personal_keys'][0];
        foreach ($cohort as $entry) { if (!self::sameName($first,$entry['personal_keys'][0])) { return $review; } }
        $prefix=$citation['authors'];$k=count($prefix);$base=[];$valid=[];$raw=[];
        foreach ($cohort as $i=>$entry) {
            if (count($entry['authors'])>=3) { $base[]=$i; }
            if (self::namePrefix($prefix,$entry['authors'])) {
                $raw[]=$i;
                if (count($entry['authors'])-$k>=2) { $valid[]=$i; }
            }
        }
        if ($citation['form']==='many_authors') {
            $full=[];foreach ($cohort as $i=>$entry) { if (count($entry['authors'])===$k && self::namePrefix($prefix,$entry['authors'])) { $full[]=$i; } }
            if (count($full)!==1) { return $review; }
            if (count($base)<=1) { return 'three_or_more_authors_require_et_al'; }
            return self::shorterEtAlWorks($cohort[$full[0]],$cohort,$k)?$review:null;
        }
        if (!$valid) {
            if ($citation['form']==='expanded_et_al' && count($raw)===1 && count($base)===1 && $raw[0]===$base[0]) { return 'three_or_more_authors_require_et_al'; }
            return $raw?'et_al_minimum_omission':$review;
        }
        if (count($valid)===1 && count($raw)>1) { return $review; }
        if (count($valid)>1) {
            // Identical surname lists need initials, year suffixes or duplicate review, not guessed coauthors.
            foreach ($valid as $i) {
                $same=0;
                foreach ($cohort as $entry) { if (count($entry['authors'])===count($cohort[$i]['authors']) && self::namePrefix($cohort[$i]['authors'],$entry['authors'])) { $same++; } }
                if ($same!==1) { return $review; }
            }
            return 'et_al_disambiguation_required';
        }
        if ($citation['form']==='et_al') { return null; }
        if (count($base)<=1) { return 'three_or_more_authors_require_et_al'; }
        return self::shorterEtAlWorks($cohort[$valid[0]],$cohort,$k)?$review:null;
    }

    private static function namePrefix(array $prefix,array $names): bool
    {
        if (count($prefix)>count($names)) { return false; }
        foreach ($prefix as $i=>$name) { if (!self::sameName($name,$names[$i])) { return false; } }
        return true;
    }

    private static function shorterEtAlWorks(array $target,array $cohort,int $length): bool
    {
        for ($k=2;$k<$length && $k<=count($target['authors'])-2;$k++) {
            $prefix=array_slice($target['authors'],0,$k);$count=0;
            foreach ($cohort as $entry) { if (count($entry['authors'])>$k && self::namePrefix($prefix,$entry['authors'])) { $count++; } }
            if ($count===1) { return true; }
        }
        return false;
    }

    /** Internal semantic key only; no manuscript text or language is rewritten. */
    public static function dateKey(string $token): string
    {
        $value=strtolower(preg_replace('/\\s+/u','',trim($token)));
        if (preg_match('/^[ns]\\.d\\.(?:-([a-z]))?$/D',$value,$m)) { return 'undated'.(isset($m[1])?'-'.$m[1]:''); }
        if ($value==='inpress' || $value==='noprelo') { return 'inpress'; }
        return $value;
    }

    private static function specialDate(string $key): bool
    {
        return preg_match('/^[0-9]{4}[a-z]?$/D',$key)!==1;
    }

    private static function yearList(string $text): array
    {
        return array_map(function ($value) { return self::dateKey($value); },explode(',',$text));
    }

    private static function originalCandidate(array $citation,array $entry): bool
    {
        return self::weakName($entry['authors'][0],$citation['authors'][0])
            && (!empty($entry['original_year_review']) || (isset($entry['original_year']) && $entry['original_year']===substr($citation['year'],0,4)));
    }

    /** Compare declared keys only; never select a work or identify a person. */
    private static function suffixIssue(array $citation,array $entries,bool $complete): ?string
    {
        if (self::specialDate($citation['year'])) { return null; }
        if (!$complete || empty($citation['syntax_complete']) || !empty($citation['institutional_ambiguous'])) { return null; }
        foreach ($entries as $entry) { if (self::originalCandidate($citation,$entry) && $entry['year']!==$citation['year']) { return 'author_year_disambiguation_review'; } }
        $cohort=[];$exact=0;
        foreach ($entries as $entry) {
            if (self::specialDate($entry['year']) && self::matches($citation,$entry)) { return 'author_year_disambiguation_review'; }
            if (substr($entry['year'],0,4)!==substr($citation['year'],0,4) || !self::matches($citation,$entry)) { continue; }
            $cohort[]=$entry;if ($entry['year']===$citation['year']) { $exact++; }
        }
        if (!$cohort) { return null; }
        $key=$cohort[0]['author_key']??null;
        foreach ($cohort as $entry) {
            if ($key===null || !isset($entry['author_key']) || !self::sameName($key,$entry['author_key'])) { return 'author_year_disambiguation_review'; }
        }
        if (!$exact) { return 'citation_year_suffix_mismatch'; }
        if ($exact>1) { return 'author_year_disambiguation_review'; }
        return null;
    }

    /** Ordering is checked only for complete groups with unique, personal reference links. */
    private static function groupIssues(array $citations,array $entries,bool $complete): array
    {
        if (count($citations)<2) { return []; }
        $issues=array_fill(0,count($citations),[]);$linked=[];
        if (!$complete) { return array_fill(0,count($citations),['citation_group_order_review']); }
        foreach ($citations as $i=>$citation) {
            if (self::specialDate($citation['year']) || !empty($citation['secondary']) || $citation['form']==='institutional') { return array_fill(0,count($citations),['citation_group_order_review']); }
            $matches=[];
            foreach ($entries as $entry) {
                if (self::originalCandidate($citation,$entry) && $entry['year']!==$citation['year']) { return array_fill(0,count($citations),['citation_group_order_review']); }
                if ($entry['year']===$citation['year'] && self::matches($citation,$entry)) { $matches[]=$entry; }
            }
            if (count($matches)!==1 || !isset($matches[0]['author_key'])) { return array_fill(0,count($citations),['citation_group_order_review']); }
            $linked[$i]=$matches[0];
        }
        $items=[];
        foreach ($citations as $i=>$citation) { $items[$citation['item_index']??0][]=$i; }
        foreach ($items as $indices) {
            if (count($indices)<2) { continue; }
            $years=[];$same=true;$key=$linked[$indices[0]]['author_key'];
            foreach ($indices as $i) { $years[]=$citations[$i]['year'];$same=$same && self::sameName($key,$linked[$i]['author_key']); }
            $code=null;
            if (!$same) { $code='citation_year_list_authorship_review'; }
            elseif (count(array_unique($years))!==count($years)) { $code='citation_year_list_duplicates_review'; }
            else { $ordered=$years;sort($ordered,SORT_STRING);if ($ordered!==$years) { $code='citation_year_list_order'; } }
            if ($code!==null) { foreach ($indices as $i) { $issues[$i][]=$code; } }
        }
        if (count($items)>1) {
            $keys=[];$safe=true;
            foreach ($items as $indices) {
                $first=$linked[$indices[0]]['authors'][0];
                if (!preg_match('/^[A-Za-z]+$/D',$first)) { $safe=false;break; }
                $keys[]=strtolower($first);
            }
            if (!$safe || count(array_unique($keys))!==count($keys)) { $code='citation_group_order_review'; }
            else { $ordered=$keys;sort($ordered,SORT_STRING);$code=$ordered!==$keys?'citation_group_alphabetical_order':null; }
            if ($code!==null) { foreach ($issues as &$values) { $values[]=$code; } unset($values); }
        }
        return $issues;
    }

    private static function secondaryCitation(string $text, string $prefix, array $entries, array $aliases, ?string $personalPattern, string $language): ?array
    {
        $markers='como citado em|as cited in|apud'.($language==='es'?'|como se citó en':'');
        if (strlen($text)>2048 || preg_match_all('/\b(?:'.$markers.')\b/iu',$text)!==1
            || !preg_match('/^\s*(.*?)\s*\b('.$markers.')\b\s+(.+?)\s*$/iuD',$text,$m)) { return null; }
        $original=trim($m[1], " ,\t\r\n");
        if ($original==='' || preg_match('/^' . self::DATE_TOKEN . '$/iuD',$original)) {
            $original=trim(preg_replace('/^(?:Segundo|Conforme|According to|Según)\s+/iu','',trim($prefix)));
        } else { $original=trim(preg_replace('/,\s*' . self::DATE_TOKEN . '$/iu','',$original)); }
        if (self::citationAuthors($original, ApaPersonalAuthors::surnamePattern(),$language)===null && !InstitutionalAuthors::name($original)) { return null; }
        if (!preg_match('/^(.+?),\s*(' . self::DATE_TOKEN . ')(?:,\s*pp?\.\s*[0-9]+(?:[-–][0-9]+)?)?$/iuD',$m[3],$source)) { return null; }
        $name=trim($source[1]);
        $author=InstitutionalAuthors::citation($name,$entries,$aliases) ?? self::citationAuthors($name, $personalPattern,$language);
        if ($author===null && InstitutionalAuthors::name($name)) { $author=['authors'=>[$name],'form'=>'institutional','institutional_alias'=>false]; }
        if ($author===null) { return null; }
        return $author+['year'=>self::dateKey($source[2]),'placement'=>'parenthetical','spacing_ok'=>preg_match('/,\S/u',$text)!==1,
            'secondary'=>true,'secondary_marker'=>preg_match('/^apud$/iuD',$m[2])?'apud':'as_cited_in'];
    }

    /** Only compound names literally present in parsed personal references; no prose-name inference. */
    private static function citationPattern(array $entries): ?string
    {
        $names=[];$bytes=0;
        foreach ($entries as $entry) {
            if (($entry['kind']??null)==='institutional') { continue; }
            foreach ($entry['authors'] as $name) {
                if (!preg_match('/\s/u',$name) || isset($names[$name])) { continue; }
                $quoted=implode('\\s+',array_map(function ($part) { return preg_quote($part,'~'); },preg_split('/\s+/u',$name)));$bytes+=strlen($quoted)+1;
                if (count($names)>=64 || $bytes>4096) { return null; }
                $names[$name]=$quoted;
            }
        }
        return $names?'(?:'.implode('|',array_values($names)).'|'.self::SURNAME.')':self::SURNAME;
    }

    /** A shortened compound name is ambiguous, never silently an alias or proof of absence. */
    private static function weakName(string $left,string $right): bool
    {
        if (self::sameName($left,$right)) { return true; }
        if (!preg_match('/\s/u',$left.$right)) { return false; }
        $particles=['da','das','de','del','della','di','do','dos','du','la','le','van','von','der','den','ten','ter','e'];
        $parts=[];
        foreach (preg_split('/\s+/u',$left) as $part) {
            if (!in_array(strtolower($part),$particles,true) && preg_match_all('/\p{L}/u',$part)>=2) { $parts[]=preg_quote($part,'~'); }
        }
        return $parts && preg_match('~(?:^|\s)(?:'.implode('|',$parts).')(?=\s|$)~iu',$right)===1;
    }

    private static function citationAuthors(string $text, ?string $personalPattern = null, string $language = 'pt'): ?array
    {
        if ($language!=='pt' && preg_match('/^(?:In|On|At|Year|Since|Until|Figure|Table|Appendix|En|Año|Desde|Hasta|Figura|Tabla|Cuadro|Anexo)$/iuD',$text)) { return null; }
        $name = $personalPattern ?? self::SURNAME;
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
        if ($left === $right) { return true; }
        $left=preg_replace('/\s+/u',' ',trim($left));$right=preg_replace('/\s+/u',' ',trim($right));
        return preg_match('/^' . preg_quote($left, '/') . '$/iuD', $right) === 1;
    }


}
