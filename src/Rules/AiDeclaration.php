<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule,Submission,CheckResult};
use Estrabao\Document\{DocumentSource,ReferenceAnalysis,ManuscriptSegments};
use Estrabao\Text\PlainText;

/** Checks declaration text, never detects actual AI use or certifies its purpose. */
final class AiDeclaration implements Rule
{
    private $document;
    public function __construct(?array $document=null){$this->document=$document;}
    public function evaluate(Submission $submission):array
    {
        $source=DocumentSource::mainText($this->document)??[];$status=CheckResult::UNKNOWN;
        $e=['implemented'=>true,'policy_version'=>'ia-manuscrito-uso-ou-nao-uso-confirmado-2026-11-13',
            'scope'=>'ai_declaration_text_presence_only','document_sha256'=>$this->document['sha256']??null,
            'source_available'=>$source['available']??false,'source_profile'=>$source['source_profile']??'unavailable',
            'full_document_text_available'=>$source['full_document_text_available']??false,
            'declaration_location'=>'manuscript','non_use_declaration_required'=>true,
            'ojs_duplicate_required'=>false,'actual_ai_use_verified'=>false,'purpose_compliance_verified'=>false,
            'tool_and_interaction_verified'=>false,'external_detection_performed'=>false,'human_review_required'=>true,
            'heading_count'=>0,'content_paragraph_count'=>null,'explicit_non_use_statement_count'=>0,
            'marker_paragraph_count'=>0,'samples'=>[],'samples_omitted'=>0,'reason'=>'source_unavailable'];
        if($source['available']??false){
            $p=$source['paragraphs'];$heads=[];$valid=true;$bibliography=false;$otherMention=false;
            if(count($p)>ReferenceAnalysis::MAX_PARAGRAPHS){$valid=false;$e['reason']='inspection_limit';}
            if($valid){foreach($p as$i=>$row){
                $text=$row['text'];
                if(strlen($text)>8192){$valid=false;$e['reason']='inspection_limit';break;}
                if(preg_match('//u',$text)!==1||preg_match('/\p{M}/u',$text)){$valid=false;$e['reason']='normalization_unavailable';break;}
                if(self::heading($text)){$heads[]=$i;}
                if(self::references($text)){$bibliography=true;}
                if(!self::heading($text)&&self::marker($text)){$otherMention=true;}
                if(!$bibliography){
                    if(self::nonUse($text)){$e['explicit_non_use_statement_count']++;}
                    if(self::marker($text)){
                        $e['marker_paragraph_count']++;
                        if(count($e['samples'])<32){$e['samples'][]=['paragraph_index'=>$row['index']];}
                    }
                }
            }}
            $e['heading_count']=count($heads);$e['samples_omitted']=max(0,$e['marker_paragraph_count']-count($e['samples']));
            if($valid){
                $e['reason']='declaration_requires_editorial_review';
                if(!($source['full_document_text_available']??false)){$e['reason']='incomplete_document_coverage';}
                elseif($e['explicit_non_use_statement_count']){$status=CheckResult::PASS;$e['reason']='explicit_non_use_text_observed';}
                elseif(count($heads)===1){
                    $count=0;$known=true;$placeholder=false;
                    for($i=$heads[0]+1;$i<count($p);$i++){
                        if(self::boundary($p[$i]['text'])){break;}
                        if(!PlainText::hasContent($p[$i]['text'])){continue;}
                        $outline=$p[$i]['outline']??['known'=>false,'level'=>null];
                        if(!$outline['known']||$outline['level']<9||($p[$i]['format']['bold']??null)!==false){$known=false;break;}
                        $count++;
                        if(preg_match('/^(?:N\/?A|não se aplica|a preencher|pendente)[.!]?$/iuD',trim($p[$i]['text']))){$placeholder=true;}
                    }
                    $e['content_paragraph_count']=$known?$count:null;
                    $status=!$known||$placeholder||(!$count&&$otherMention)?CheckResult::UNKNOWN:($count?CheckResult::PASS:CheckResult::UNKNOWN);
                    $e['reason']=!$known?'section_boundary_uncertain':($placeholder?'declaration_text_needs_review':($count?'declaration_text_present':($otherMention?'other_mentions_require_review':'ai_section_empty')));
                }elseif(count($heads)>1){$e['reason']='multiple_declaration_headings_require_review';}
            }
        }
        return [new CheckResult('manuscript.ai.declaration',$status,$e,'/manuscript',
            'Conferir declaração de uso ou não uso de IA no manuscrito. Texto presente não comprova uso real ou finalidade permitida; não exigir repetição no OJS.'),
            new CheckResult('manuscript.ai.verification',CheckResult::UNKNOWN,
                array_merge($e,['scope'=>'ai_tool_purpose_interaction_and_policy_review','reason'=>'editorial_verification_required']),'/manuscript',
                'Quando houver uso declarado, conferir ferramenta, finalidade e nível de interação; a política permite apenas apoio técnico. Quando houver não uso declarado, não exigir detalhes de ferramenta.')];
    }
    private static function heading(string $text):bool
    {return preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?(?:Declaração de (?:uso de )?IA|Declaração de uso de inteligência artificial|Uso de inteligência artificial|AI use declaration|Declaration of AI use|Declaración de uso de IA)$/iuD',$text)===1;}
    private static function nonUse(string $text):bool
    {return preg_match('/^(?:(?:Não utilizamos|Não usamos) (?:IA|inteligência artificial)(?: generativa)? neste (?:estudo|trabalho|artigo)|Não foi utilizada inteligência artificial(?: generativa)? neste (?:estudo|trabalho|artigo)|No (?:generative )?AI was used in this (?:study|article)|No se utilizó inteligencia artificial(?: generativa)? en este (?:estudio|artículo))[.!]?$/iuD',$text)===1;}
    private static function marker(string $text):bool
    {return preg_match('/\b(?:inteligência artificial|inteligencia artificial|artificial intelligence|generative AI|ChatGPT)\b/iu', $text)===1 || preg_match('/\b(?:IA|LLM|AI)\b/u',$text)===1;}
    private static function references(string $text):bool
    {return preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?(?:Referências|References|Referencias|Bibliografia|Bibliography|Bibliografía)$/iuD',$text)===1;}
    private static function boundary(string $text):bool
    {
        if(self::heading($text)||self::references($text)){return true;}
        foreach(array_merge(ManuscriptSegments::CORE,['Créditos (CRediT)','Fontes de Fomento','Aspectos éticos'])as$label){
            if(preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?'.preg_quote($label,'/').'$/iuD',$text)){return true;}
        }return false;
    }
}
