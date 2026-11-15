<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule,Submission,CheckResult};
use Estrabao\Document\{DocumentSource,ReferenceAnalysis};

/** Locates possible declarations for a human; never infers ethics applicability. */
final class EthicsReview implements Rule
{
    private $document;
    public function __construct(?array $document=null){$this->document=$document;}
    public function evaluate(Submission $submission):array
    {
        $source=DocumentSource::mainText($this->document)??[];
        $e=['implemented'=>true,'policy_version'=>'diretrizes-fornecidas-2026-11-11',
            'scope'=>'ethics_declaration_locator_only','document_sha256'=>$this->document['sha256']??null,
            'source_available'=>$source['available']??false,'source_profile'=>$source['source_profile']??'unavailable',
            'full_document_text_available'=>$source['full_document_text_available']??false,
            'applicability_known'=>false,'approval_verified'=>false,'protocol_identifier_verified'=>false,
            'non_applicability_verified'=>false,'external_lookup_performed'=>false,
            'footnotes_or_other_ojs_documents_analyzed'=>false,'human_review_required'=>true,
            'marker_scan_complete'=>false,'matched_paragraph_count'=>0,'samples'=>[],'samples_omitted'=>0,
            'reason'=>'source_unavailable'];
        if($source['available']??false){
            $paragraphs=$source['paragraphs'];
            if(count($paragraphs)>ReferenceAnalysis::MAX_PARAGRAPHS){$e['reason']='inspection_limit';}
            else{
                $complete=true;
                foreach($paragraphs as$p){
                    $text=$p['text'];
                    if(strlen($text)>8192){$e['reason']='inspection_limit';$complete=false;break;}
                    if(preg_match('//u',$text)!==1||preg_match('/\p{M}/u',$text)){$e['reason']='normalization_unavailable';$complete=false;break;}
                    if(preg_match('/^(?:[0-9]+(?:\.[0-9]+)*[.)]?\s+)?(?:Referências|References|Referencias|Bibliografia|Bibliography|Bibliografía)$/iuD',$text)){break;}
                    if(preg_match('/\b(?:comit[eêé] de [eé]tica|comiss[aã]o de [eé]tica|ethics committee|institutional review board|research ethics board|aprova[cç][aã]o [eé]tica|ethical approval|ethics approval|aprobaci[oó]n [eé]tica|aspectos [eé]ticos|ethical considerations|consideraciones [eé]ticas|declara[cç][aã]o de [eé]tica|ethics statement|CAAE)\b/iu',$text)){
                        $e['matched_paragraph_count']++;
                        if(count($e['samples'])<32){$e['samples'][]=['paragraph_index'=>$p['index']];}
                    }
                }
                $e['marker_scan_complete']=$complete;
                $e['samples_omitted']=max(0,$e['matched_paragraph_count']-count($e['samples']));
                if($complete){$e['reason']=$e['matched_paragraph_count']?'candidates_require_editorial_review':'no_markers_in_scanned_text';}
            }
        }
        return [new CheckResult('manuscript.ethics.review',CheckResult::UNKNOWN,$e,'/manuscript',
            'Conferir aplicabilidade ética. Quando aplicável, verificar aprovação por comitê e número do parecer; caso não aplicável, conferir declaração formal. Indícios textuais não validam aprovação, documento ou dispensa.')];
    }
}
