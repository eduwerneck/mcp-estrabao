<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule,Submission,CheckResult};
use Estrabao\Document\{DocumentSource,ManuscriptSegments,ReferenceAnalysis};
use Estrabao\Text\PlainText;

/** Compare declared sources only; supportingAgencies may include nonfinancial support. */
final class FundingConsistency implements Rule
{
    private $document; private $language;
    public function __construct(?array $document=null,?string $language=null){$this->document=$document;$this->language=$language;}
    public function evaluate(Submission $submission):array
    {
        $source=DocumentSource::mainText($this->document)??[];
        $word=$this->word($source);$ojs=self::metadata($submission->field('supporting_agencies'));
        $status=CheckResult::UNKNOWN;$reason='source_or_declaration_unresolved';
        if($word==='declared'&&$ojs['state']==='absent'){$status=CheckResult::FAIL;$reason='word_declared_ojs_empty';}
        elseif($word==='declared'&&$ojs['state']==='none'){$status=CheckResult::FAIL;$reason='word_declared_ojs_explicit_none';}
        elseif(in_array($word,['absent','none'],true)&&in_array($ojs['state'],['absent','none'],true)){$status=CheckResult::PASS;$reason='no_observed_declaration_conflict';}
        elseif($ojs['state']==='declared'&&in_array($word,['absent','none'],true)){$reason='ojs_support_word_absent_or_none_review';}
        elseif($word==='declared'&&$ojs['state']==='declared'){$reason='both_declared_compare_agencies_and_awards';}
        return [new CheckResult('manuscript.funding',$status,
            ['implemented'=>true,'policy_version'=>'consistencia-word-ojs-confirmada-2026-11-13',
                'scope'=>'declared_funding_and_support_consistency_only','document_sha256'=>$this->document['sha256']??null,
                'source_profile'=>$source['source_profile']??'unavailable','full_document_text_available'=>$source['full_document_text_available']??false,
                'word_state'=>$word,'ojs_state'=>$ojs['state'],'metadata_entry_count'=>$ojs['count'],
                'reason'=>$reason,'funding_applicability_inferred'=>false,'agency_identity_verified'=>false,
                'grant_numbers_verified'=>false,'external_lookup_performed'=>false,'metadata_written'=>false],'/manuscript',
            'Comparar declarações no Word e supportingAgencies do OJS. O campo também admite apoio institucional; fontes incompletas e possíveis divergências exigem revisão, sem presumir financiamento.', '2.0.0')];
    }
    private static function normalized(string $text):?string
    {
        $text=PlainText::normalize($text);if($text===null){return null;}
        return trim(preg_replace('/[\s\p{Z}]+/u',' ',$text));
    }
    private static function none(string $text,bool $inField=false):bool
    {
        return preg_match('/^(?:não houve (?:financiamento|fomento)|(?:esta pesquisa|este estudo|este trabalho|a pesquisa|o estudo|o trabalho) não recebeu financiamento|sem financiamento'.($inField?'|não se aplica|nenhuma':'').')[.!]?$/iuD',$text)===1;
    }
    private static function statement(string $text,bool $section):string
    {
        if($text===''){return 'empty';}
        if(!preg_match('/\p{L}/u',$text)||preg_match('/^(?:n\/?a|n\.a\.)[.!]?$/iuD',$text)){return 'unresolved';}
        if(self::none($text,$section)){return 'none';}
        if(preg_match('/\b(?:não|sem|nenhum|nenhuma|recursos próprios|autofinanciamento|financiamento próprio|apoio institucional|apoio técnico|apoio logístico|inexistente|a preencher|pendente|informar)\b/iu',$text)){return 'unresolved';}
        $explicit=preg_match('/^(?:esta pesquisa|este estudo|este trabalho) (?:foi|é) financiad[oa] (?:pela|pelo|por|com recursos de) .{2,}[.!]?$/iuD',$text)===1;
        if($explicit){return 'declared';}
        if($section&&!preg_match('/agradec/iu',$text)){return 'declared';}
        return 'unresolved';
    }
    private function word(array $source):string
    {
        if(!($source['available']??false)||!($source['full_document_text_available']??false)||$this->language!=='pt'){return 'unavailable';}
        $p=$source['paragraphs'];if(count($p)>ReferenceAnalysis::MAX_PARAGRAPHS){return 'unavailable';}
        $starts=[];
        foreach($p as$i=>$row){if(self::label($row['text'])==='Fontes de Fomento'){$starts[]=$i;}}
        if(count($starts)>1){return 'unresolved';}
        if($starts){
            $texts=[];
            for($i=$starts[0]+1;$i<count($p);$i++){
                if(self::label($p[$i]['text'])!==null){break;}
                if(!PlainText::hasContent($p[$i]['text'])){continue;}
                $outline=$p[$i]['outline']??['known'=>false,'level'=>null];
                if(!$outline['known']||$outline['level']<9||($p[$i]['format']['bold']??null)!==false){return 'unresolved';}
                $texts[]=$p[$i]['text'];
            }
            if(strlen(implode(' ',$texts))>8192){return 'unavailable';}
            $text=self::normalized(implode(' ',$texts));
            return $text===null?'unavailable':self::statement($text,true);
        }
        $states=[];$unresolvedSignal=false;
        foreach($p as$row){
            if(self::label($row['text'])==='Referências'){break;}
            if(strlen($row['text'])>8192){return 'unavailable';}
            $text=self::normalized($row['text']);if($text===null){return 'unavailable';}
            $state=self::statement($text,false);
            if(in_array($state,['declared','none'],true)){$states[$state]=true;}
            elseif(preg_match('/financ|fomento|bolsa/iu',$text)){$unresolvedSignal=true;}
        }
        if(count($states)>1){return 'unresolved';}
        return $states?key($states):($unresolvedSignal?'unresolved':'absent');
    }
    private static function label(string $text):?string
    {
        foreach(array_merge(ManuscriptSegments::CORE,['Créditos (CRediT)','Referências','Fontes de Fomento'])as$label){
            if(preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?'.preg_quote($label,'/').'$/iuD',$text)){return $label;}
        }return null;
    }
    private static function metadata(?array $map):array
    {
        $unknown=['state'=>'unavailable','count'=>null];if($map===null){return $unknown;}
        $states=[];$count=0;
        foreach($map as$locale=>$list){
            if(!in_array($locale,['pt_BR','en_US','es_ES'],true)||count($list)>128){return $unknown;}
            foreach($list as$item){
                if(strlen($item)>8192){return $unknown;}$text=self::normalized($item);if($text===null){return $unknown;}
                if(!PlainText::hasContent($text)){continue;}$count++;
                $state=self::none($text,true)?'none':'declared';
                if($state==='declared'&&preg_match('/\b(?:não|sem|no|without|sin|nenhum|nenhuma|pendente|a preencher)\b/iu',$text)){return ['state'=>'unresolved','count'=>$count];}
                $states[$state]=true;
            }
        }
        return ['state'=>count($states)>1?'unresolved':($states?key($states):'absent'),'count'=>$count];
    }
}
