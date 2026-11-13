<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};
use Estrabao\Document\{DocumentSource, ManuscriptSegments, ReferenceAnalysis};
use Estrabao\Text\PlainText;

/** Presence of declaration text only; never validates roles, identities or contribution. */
final class CreditContent implements Rule
{
    private $document;
    private $language;
    public function __construct(?array $document = null, ?string $language = null)
    { $this->document=$document; $this->language=$language; }

    public function evaluate(Submission $submission): array
    {
        $source=DocumentSource::mainText($this->document);
        $e=['implemented'=>true,'policy_version'=>'diretrizes-fornecidas-2026-11-11',
            'document_sha256'=>$this->document['sha256']??null,'source_available'=>$source['available']??false,
            'source_profile'=>$source['source_profile']??'unavailable',
            'full_document_text_available'=>$source['full_document_text_available']??false,
            'scope'=>'credit_section_text_presence_only','section_count'=>0,'content_paragraph_count'=>null,
            'taxonomy_verified'=>false,'author_assignment_verified'=>false,'all_authors_covered_verified'=>false,
            'contribution_veracity_verified'=>false,'human_review_required'=>true,'reason'=>'source_unavailable'];
        $status=CheckResult::UNKNOWN;
        if (($source['available']??false) && $this->language==='pt') {
            $paragraphs=$source['paragraphs'];$starts=[];
            if(count($paragraphs)>ReferenceAnalysis::MAX_PARAGRAPHS){$e['reason']='inspection_limit';}
            else{
                foreach($paragraphs as$i=>$p){if(self::label($p['text'])==='Créditos (CRediT)'){$starts[]=$i;}}
                $e['section_count']=count($starts);
                if(count($starts)!==1){$e['reason']=$starts?'duplicate_credit_sections':'credit_heading_not_resolved';}
                elseif(!($source['full_document_text_available']??false)){$e['reason']='incomplete_document_coverage';}
                else{
                    $count=0;$known=true;$e['heading_paragraph_index']=$paragraphs[$starts[0]]['index'];
                    for($i=$starts[0]+1;$i<count($paragraphs);$i++){
                        $p=$paragraphs[$i];$text=$p['text'];
                        if(self::label($text)!==null){break;}
                        if(!PlainText::hasContent($text)){continue;}
                        $outline=$p['outline']??['known'=>false,'level'=>null];
                        // Unknown headings can delimit another section; do not borrow its text.
                        if(!$outline['known']||$outline['level']<9||($p['format']['bold']??null)!==false){$known=false;break;}
                        $count++;
                    }
                    $e['content_paragraph_count']=$known?$count:null;
                    $e['reason']=!$known?'section_boundary_uncertain':($count?'declaration_text_present':'credit_section_empty');
                    $status=!$known?CheckResult::UNKNOWN:($count?CheckResult::PASS:CheckResult::FAIL);
                }
            }
        }elseif(($source['available']??false)&&$this->language!=='pt'){$e['reason']='heading_translation_unconfirmed';}
        return [new CheckResult('manuscript.credit.content',$status,$e,'/manuscript',
            'Conferir descrição das contribuições na seção CRediT. Texto presente não valida taxonomia, atribuição a todos os autores ou veracidade; preservar anonimização.'),
            new CheckResult('manuscript.credit.verification',CheckResult::UNKNOWN,
                array_merge($e,['scope'=>'credit_taxonomy_author_assignment_and_veracity', 'reason'=>'editorial_verification_required']),'/manuscript',
                'Conferir taxonomia CRediT, atribuição a todos os autores e veracidade declarada pelo autor correspondente; sem aprovação automática.')];
    }
    private static function label(string $text):?string
    {
        foreach(array_merge(ManuscriptSegments::CORE,['Créditos (CRediT)','Referências','Fontes de Fomento'])as$label){
            if(preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?'.preg_quote($label,'/').'$/iuD',$text)){return $label;}
        }
        return null;
    }
}
