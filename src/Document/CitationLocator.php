<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Text\WordInterval;

/** Lexical locators and whole-paragraph quoted candidates, never source fidelity or rendered layout. */
final class CitationLocator
{
    const PATTERN='(?:pp?\.\s*[0-9]{1,9}(?:[-–][0-9]{1,9})?|(?:para\.|parág\.)\s*[0-9]{1,9}|[0-9]{1,2}:[0-5][0-9](?::[0-5][0-9])?)';

    public static function withinQuotes(string $text,int $offset): bool
    {
        $prefix=substr($text,0,$offset);
        return substr_count($prefix,'"')%2!==0 || substr_count($prefix,'“')!==substr_count($prefix,'”');
    }

    public static function inspect(string $text,array $citation,int $matches,string $language='pt'): array
    {
        $r=['issues'=>[],'kind'=>null,'quote_candidate'=>false,'word_interval'=>null,'long'=>false,'boundary'=>false,'missing_locator'=>false];
        $quote=self::quotedText($text,$citation,$language);
        $locator=$citation['locator']??($quote['locator']??null);
        if (is_string($locator)) {
            if (preg_match('/^(p|pp)\.\s*([0-9]{1,9})(?:[-–]([0-9]{1,9}))?$/iuD',$locator,$m)) {
                $r['kind']='pages';$start=(int)$m[2];$end=isset($m[3])?(int)$m[3]:null;
                if ($matches===1 && !empty($citation['syntax_complete']) && !empty($citation['group_complete']) && empty($citation['inside_quotes']) && empty($citation['secondary'])) {
                    if ($start===0 || $end===0 || $end===$start) { $r['issues'][]='citation_locator_review'; }
                    elseif ($end!==null && $end<$start) { $r['issues'][]='citation_page_range_order'; }
                    elseif (($end===null && strtolower($m[1])==='pp') || ($end!==null && strtolower($m[1])==='p')) { $r['issues'][]='citation_page_marker'; }
                }
            } else {
                $r['kind']=strpos($locator,':')!==false?'timestamp_candidate':'paragraph_candidate';
                $r['issues'][]='citation_locator_review';
            }
        }
        if ($quote===null) { return $r; }
        $r['quote_candidate']=true;$r['word_interval']=WordInterval::counts($quote['text']);
        $r['long']=$r['word_interval'][0]>=40;
        $r['boundary']=$r['word_interval'][0]<40 && $r['word_interval'][1]>=40;
        $r['missing_locator']=$locator===null;
        $r['issues'][]='direct_quote_source_review';
        if ($r['long']) { $r['issues'][]='direct_quote_block_review'; }
        elseif ($r['boundary']) { $r['issues'][]='direct_quote_boundary_review'; }
        return $r;
    }

    /** Local quote association only; returned text is never included in report evidence. */
    private static function quotedText(string $text,array $citation,string $language): ?array
    {
        if (($citation['group_item_count']??0)!==1 || empty($citation['syntax_complete'])
            || empty($citation['group_complete']) || !empty($citation['secondary'])
            || !empty($citation['inside_quotes']) || strlen($text)>65536) { return null; }
        $after=substr($text,$citation['byte_offset']+$citation['group_length']);
        if (($citation['placement']??null)==='parenthetical') {
            if (!preg_match('/^(["“])([^"“”]+)(["”])\s*(?=\()/u',$text,$m)
                || strlen($m[0])!==$citation['byte_offset'] || !preg_match('/^[.!?]?$/uD',trim($after))) { return null; }
            $locator=$citation['locator']??null;
        } elseif (($citation['placement']??null)==='narrative') {
            // The existing parser requires the author prefix at paragraph start. Do not search arbitrary prose.
            $verbs='afirma|declara|escreve|observa|states|writes'.($language==='es'?'|señala|sostiene|escribe':'');
            if (!preg_match('/^\s*(?:(?:'.$verbs.')\s*)?:?\s*(["“])([^"“”]+)(["”])(?:\s*\(('.self::PATTERN.')\))?\s*[.!?]?$/iuD',$after,$m)) { return null; }
            $tailLocator=isset($m[4]) && $m[4]!==''?$m[4]:null;
            if (($citation['locator']??null)!==null && $tailLocator!==null) { return null; }
            $locator=$citation['locator']??$tailLocator;
        } else { return null; }
        if (!(($m[1]==='"' && $m[3]==='"') || ($m[1]==='“' && $m[3]==='”'))) { return null; }
        return ['text'=>$m[2],'locator'=>$locator];
    }

    public static function reviewGuidance(array $counts): array
    {
        if (empty($counts['candidate_count']) && empty($counts['quoted_citation_candidates'])) { return []; }
        $guidance=['Confirme a função dos trechos entre aspas e a fidelidade à fonte. Confira a localização por página, parágrafo, seção ou marca temporal, conforme a obra.'];
        if (!empty($counts['quoted_citation_candidates'])) { $guidance[]='Confira as citações reproduzidas dentro da transcrição; não presuma consulta à obra interna nem altere o trecho citado automaticamente.'; }
        if (!empty($counts['long_candidates'])) { $guidance[]='Há trecho que pode exigir citação em bloco. Confira o limite de 40 palavras e o uso de aspas externas; a apresentação visual exige conferência.'; }
        if (!empty($counts['boundary_candidates'])) { $guidance[]='A extensão de um trecho exige conferência do limite de 40 palavras para decidir entre aspas e bloco.'; }
        if (!empty($counts['missing_locator_candidates'])) { $guidance[]='Confira o localizador apropriado na fonte, inclusive parágrafo, seção ou marca temporal quando não houver página. Não preencha uma localização presumida.'; }
        return $guidance;
    }
}
