<?php
declare(strict_types=1);
use Estrabao\Rules\CreditContent;
function creditCheck(array $texts,?array $modify=null):array{
 $doc=referenceDocument([],$texts);$doc['paragraphs']=array_slice($doc['paragraphs'],-count($texts));
 foreach($doc['paragraphs']as$i=>&$p){$p['index']=$i;$p['outline']=['known'=>true,'level'=>9];$p['format']['bold']=false;}
 unset($p);if($modify!==null)$doc=array_replace($doc,$modify);
 return (new CreditContent($doc,'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize();
}
foreach([['Créditos (CRediT)','Referências'],['Créditos (CRediT)',' ','Referências'],['Créditos (CRediT)']]as$texts){
 test('seção CRediT sem descrição comprovada '.count($texts),function()use($texts){$c=creditCheck($texts);same('não conforme',$c['status']);same('credit_section_empty',$c['evidence']['reason']);same(0,$c['evidence']['content_paragraph_count']);});
}
test('texto presente não certifica atribuições ou veracidade CRediT',function(){
 $c=creditCheck(['Créditos (CRediT)','Conceituação: Autor 1. Revisão: Autor 2.','Referências']);same('conforme',$c['status']);
 foreach(['taxonomy_verified','author_assignment_verified','all_authors_covered_verified','contribution_veracity_verified']as$key){same(false,$c['evidence'][$key]);}
 same(true,$c['evidence']['human_review_required']);same(false,strpos(json_encode($c),'Autor 1'));
});
test('seção posterior não fornece texto para CRediT vazio',function(){same('não conforme',creditCheck(['Créditos (CRediT)','Fontes de Fomento','Agência sintética.','Referências'])['status']);});
test('cobertura parcial não comprova conteúdo nem ausência na seção',function(){
 $full=referenceDocument([],[]);$partial=$full;$partial['paragraphs']=[['text'=>'Créditos (CRediT)','index'=>0]];
 $c=(new CreditContent(['available'=>false,'main_text'=>array_merge($partial,['available'=>true])],'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);same('incomplete_document_coverage',$c['evidence']['reason']);
});
test('ausência de título fica na regra estrutural sem nova acusação duplicada',function(){same('indeterminado',creditCheck(['Introdução','Texto normal.','Referências'])['status']);});
test('título repetido impede seleção arbitrária da declaração',function(){same('indeterminado',creditCheck(['Créditos (CRediT)','Texto.','Créditos (CRediT)','Referências'])['status']);});
test('cabeçalho desconhecido após CRediT deixa fronteira inconclusiva',function(){
 $doc=referenceDocument([],[]);$doc['paragraphs']=[['index'=>0,'text'=>'Créditos (CRediT)'],['index'=>1,'text'=>'Outros dados','outline'=>['known'=>true,'level'=>1],'format'=>['bold'=>true]],['index'=>2,'text'=>'Texto.']];
 $c=(new CreditContent($doc,'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);same('section_boundary_uncertain',$c['evidence']['reason']);
});
foreach([null,'en','es']as$lang){test('idioma ou fonte sem cobertura não inventa tradução '.($lang??'null'),function()use($lang){$doc=referenceDocument([],[]);$doc['paragraphs']=[['index'=>0,'text'=>'Créditos (CRediT)']];$c=(new CreditContent($doc,$lang))->evaluate(new \Estrabao\Contracts\Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);});}
test('devolutiva descreve seção vazia sem expor autoria ou preencher contribuições',function(){
 $c=creditCheck(['Créditos (CRediT)','Referências']);$d=deskFromChecks([$c]);same(1,count($d['corrections']));same(true,strpos($d['body'],'não texto de contribuições')!==false);same(true,strpos($d['body'],'anonimização')!==false);
});
test('regra usa DOCX real do leitor e minuta/painel sem gravar decisão',function(){
 withFootnotePanel(docxParts(docxBody(['Créditos (CRediT)','Referências'])),function($r,$service,$request){same(true,strpos($r['draft']['body'],'não texto de contribuições')!==false);same([],$service->view($request,17)['events']);});
});
test('verificação humana permanece pendente mesmo com texto de contribuição presente',function(){
 $doc=referenceDocument([],[]);$doc['paragraphs']=[['index'=>0,'text'=>'Créditos (CRediT)'],['index'=>1,'text'=>'Conceituação: Autor 1.','outline'=>['known'=>true,'level'=>9],'format'=>['bold'=>false]]];
 $checks=array_map(function($c){return $c->jsonSerialize();},(new CreditContent($doc,'pt'))->evaluate(new \Estrabao\Contracts\Submission(fixture())));
 same('conforme',$checks[0]['status']);same('indeterminado',$checks[1]['status']);same(false,$checks[1]['evidence']['taxonomy_verified']);
 same(false,strpos(json_encode($checks),'Autor 1'));
});
