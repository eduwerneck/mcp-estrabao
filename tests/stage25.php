<?php
declare(strict_types=1);
use Estrabao\Rules\EthicsReview;
use Estrabao\Contracts\Submission;
function ethicsCheck(array $texts,?array $overrides=null):array{
 $doc=referenceDocument([],[]);$doc['paragraphs']=[];
 foreach($texts as$i=>$text){$doc['paragraphs'][]=['index'=>$i,'text'=>$text];}
 if($overrides!==null)$doc=array_replace($doc,$overrides);
 return (new EthicsReview($doc))->evaluate(new Submission(fixture()))[0]->jsonSerialize();
}
foreach([
 'O estudo foi aprovado pelo Comitê de Ética, parecer PRIVATE-123.',
 'Não houve aprovação pelo comitê de ética.',
 'A discussão sobre comitê de ética é histórica.',
 'A aprovação ética não se aplica, conforme declaração dos autores.',
 'Institutional review board approval was obtained.',
 'Ethical considerations',
 'Comité de ética de la institución.',
 'CAAE PRIVATE-456',
]as$i=>$text){test('menção ética é candidato e nunca aprovação '.$i,function()use($text){
 $c=ethicsCheck([$text]);same('indeterminado',$c['status']);same(1,$c['evidence']['matched_paragraph_count']);
 foreach(['applicability_known','approval_verified','protocol_identifier_verified','non_applicability_verified','external_lookup_performed']as$flag){same(false,$c['evidence'][$flag]);}
 same(false,strpos(json_encode($c),$text));same(false,strpos(json_encode($c),'PRIVATE-'));
});}
test('sem menções não presume dispensa nem infração',function(){
 $c=ethicsCheck(['Pesquisa bibliográfica com documentos públicos.','CEP postal 12345.']);same('indeterminado',$c['status']);same('no_markers_in_scanned_text',$c['evidence']['reason']);same(0,$c['evidence']['matched_paragraph_count']);
});
foreach(['Referências','References','Referencias','Bibliography']as$heading){test('referências não fornecem declaração ética '.$heading,function()use($heading){$c=ethicsCheck([$heading,'Ethics committee in historical perspective.']);same(0,$c['evidence']['matched_paragraph_count']);});}
test('perfil parcial localiza menções sem transformar cobertura em completa',function(){
 $doc=['available'=>false,'sha256'=>str_repeat('a',64),'main_text'=>['available'=>true,'paragraphs'=>[['index'=>7,'text'=>'Comitê de Ética.']]]];
 $c=(new EthicsReview($doc))->evaluate(new Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);same(false,$c['evidence']['full_document_text_available']);same([['paragraph_index'=>7]],$c['evidence']['samples']);
});
test('fonte ausente exige conferência sem inventar declaração',function(){
 $c=(new EthicsReview())->evaluate(new Submission(fixture()))[0]->jsonSerialize();same('source_unavailable',$c['evidence']['reason']);same('indeterminado',$c['status']);same(false,$c['evidence']['marker_scan_complete']);
});
test('amostras limitadas não expõem parecer ou nomes',function(){
 $c=ethicsCheck(array_fill(0,40,'Comitê de Ética PRIVATE SECRET parecer 123.'));same(40,$c['evidence']['matched_paragraph_count']);same(32,count($c['evidence']['samples']));same(8,$c['evidence']['samples_omitted']);same(false,strpos(json_encode($c),'PRIVATE'));
});
test('limites e normalização não são ausência de declaração',function(){
 foreach([array_fill(0,4097,'Texto.'),[str_repeat('x',8193)],["Aprovac\u{0327}a\u{0303}o ética"]]as$texts){$c=ethicsCheck($texts);same('indeterminado',$c['status']);same(false,$c['evidence']['marker_scan_complete']);same(false,$c['evidence']['reason']==='no_markers_in_scanned_text');}
});
test('minuta nunca acusa infração com base em indícios éticos',function(){
 foreach([['Comitê de Ética PRIVATE protocolo 123.'],['Pesquisa bibliográfica.']]as$texts){$d=deskFromChecks([ethicsCheck($texts)]);same([],$d['corrections']);same(1,$d['pending_review_count']);same(false,strpos($d['body'],'PRIVATE'));same(false,strpos($d['body'],'comitê de ética'));}
});
test('serviço do painel mantém ética interna e não grava aprovação',function(){
 withFootnotePanel(docxParts(docxBody(['Aspectos éticos','Comitê de Ética PRIVATE parecer 123.','Referências'])),function($r,$service,$request){
  same('ethicsReviewCandidates',$r['ethics_review']['message']);same(1,$r['pending_groups']['deskPendingEthics']);same(false,strpos(json_encode($r),'PRIVATE'));same([],$service->view($request,17)['events']);
 });
});
