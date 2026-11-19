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

    public static function inspect(string $text,array $citation,int $matches): array
    {
        $r=['issues'=>[],'kind'=>null,'quote_candidate'=>false,'word_interval'=>null,'long'=>false,'boundary'=>false,'missing_locator'=>false];
        $locator=$citation['locator']??null;
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
        // Restrict attribution to one complete parenthetical immediately after one double-quoted paragraph.
        if (($citation['placement']??null)!=='parenthetical' || ($citation['group_item_count']??0)!==1
            || empty($citation['syntax_complete']) || empty($citation['group_complete']) || !empty($citation['secondary']) || strlen($text)>65536
            || !preg_match('/^(["“])([^"“”]+)(["”])\s*(?=\()/u',$text,$m)) { return $r; }
        if (!(($m[1]==='"'&&$m[3]==='"')||($m[1]==='“'&&$m[3]==='”'))
            || strlen($m[0])!==$citation['byte_offset']
            || !preg_match('/^[.!?]?$/uD',trim(substr($text,$citation['byte_offset']+$citation['group_length'])))) { return $r; }
        $r['quote_candidate']=true;$r['word_interval']=WordInterval::counts($m[2]);
        $r['long']=$r['word_interval'][0]>=40;
        $r['boundary']=$r['word_interval'][0]<40 && $r['word_interval'][1]>=40;
        $r['missing_locator']=$locator===null;
        $r['issues'][]='direct_quote_source_review';
        if ($r['long']) { $r['issues'][]='direct_quote_block_review'; }
        elseif ($r['boundary']) { $r['issues'][]='direct_quote_boundary_review'; }
        return $r;
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
