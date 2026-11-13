<?php
declare(strict_types=1);
use Estrabao\Rules\AiDeclaration;
use Estrabao\Contracts\Submission;
function aiChecks(array $texts,array $overrides=[]):array{
 $doc=referenceDocument([],[]);$doc['paragraphs']=[];
 foreach($texts as$i=>$text){$doc['paragraphs'][]=['index'=>$i,'text'=>$text,'outline'=>['known'=>true,'level'=>9],'format'=>['bold'=>false]];}
 $doc=array_replace($doc,$overrides);
 return array_map(function($c){return $c->jsonSerialize();},(new AiDeclaration($doc))->evaluate(new Submission(fixture())));
}
foreach([
 'Não utilizamos IA neste estudo.',
 'Não foi utilizada inteligência artificial neste trabalho.',
 'No generative AI was used in this study.',
 'No se utilizó inteligencia artificial en este estudio.',
]as$i=>$text){test('não uso explícito é declaração observada e não prova factual '.$i,function()use($text){
 $c=aiChecks([$text]);same('conforme',$c[0]['status']);same('explicit_non_use_text_observed',$c[0]['evidence']['reason']);same('indeterminado',$c[1]['status']);same(false,$c[0]['evidence']['actual_ai_use_verified']);same(false,$c[0]['evidence']['ojs_duplicate_required']);
});}
test('declaração estruturada presente não certifica finalidade ou interação',function(){
 $c=aiChecks(['Declaração de uso de IA','Ferramenta: PRIVATE TOOL. Finalidade: tradução. Nível de interação: revisão humana.','Referências']);same('conforme',$c[0]['status']);same('indeterminado',$c[1]['status']);same(false,$c[0]['evidence']['purpose_compliance_verified']);same(false,strpos(json_encode($c),'PRIVATE TOOL'));
});
foreach([['Declaração de IA','Referências'],['Declaração de IA',' ']]as$texts){test('título vazio exige conferir a declaração em todo o manuscrito '.count($texts),function()use($texts){$c=aiChecks($texts);same('indeterminado',$c[0]['status']);same('ai_section_empty',$c[0]['evidence']['reason']);$d=deskFromChecks($c);same([],$d['corrections']);});}
test('não exige título fixo para declaração explícita no corpo',function(){same('conforme',aiChecks(['Metodologia','Não usamos inteligência artificial neste artigo.','Referências'])[0]['status']);});
test('sem marcadores não acusa ausência nem detecta uso',function(){
 $c=aiChecks(['A pesquisa ia iniciar em janeiro.']);same('indeterminado',$c[0]['status']);same(0,$c[0]['evidence']['marker_paragraph_count']);same(true,$c[0]['evidence']['non_use_declaration_required']);$d=deskFromChecks($c);same([],$d['corrections']);
});
test('citação sobre IA não certifica declaração dos autores',function(){$c=aiChecks(['Estudos discutem inteligência artificial (Silva, 2020).','Referências']);same('indeterminado',$c[0]['status']);same(1,$c[0]['evidence']['marker_paragraph_count']);});
test('referência à IA e material após bibliografia não viram ausência comprovada',function(){
 $c=aiChecks(['Declaração de IA','Referências','ChatGPT na ciência.']);same('indeterminado',$c[0]['status']);same(1,$c[0]['evidence']['marker_paragraph_count']);
});
test('declaração depois das referências pode ser localizada por título',function(){same('conforme',aiChecks(['Referências','Silva, A. Livro.','Declaração de IA','Ferramenta: PRIVATE TOOL para tradução.'])[0]['status']);});
test('não exige duplicar declaração existente por causa de título vazio redundante',function(){same('conforme',aiChecks(['Não utilizamos IA neste artigo.','Declaração de IA','Referências'])[0]['status']);});
test('títulos duplicados ou fronteira desconhecida ficam inconclusivos',function(){
 same('indeterminado',aiChecks(['Declaração de IA','Texto.','Declaração de IA'])[0]['status']);
 $doc=referenceDocument([],[]);$doc['paragraphs']=[['index'=>0,'text'=>'Declaração de IA'],['index'=>1,'text'=>'Outra seção','outline'=>['known'=>true,'level'=>1],'format'=>['bold'=>true]]];
 $c=(new AiDeclaration($doc))->evaluate(new Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);
});
test('fonte parcial ou ausente não aprova ou reprova declaração',function(){
 $c=(new AiDeclaration())->evaluate(new Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);
 $doc=['available'=>false,'main_text'=>['available'=>true,'paragraphs'=>[['index'=>0,'text'=>'Não utilizamos IA neste estudo.']]]];
 $c=(new AiDeclaration($doc))->evaluate(new Submission(fixture()))[0]->jsonSerialize();same('indeterminado',$c['status']);same('incomplete_document_coverage',$c['evidence']['reason']);
});
test('limites e normalização mantêm revisão humana',function(){foreach([array_fill(0,4097,'Texto.'),[str_repeat('x',8193)],["intelige\u{0302}ncia artificial"]]as$texts){same('indeterminado',aiChecks($texts)[0]['status']);}});
test('amostras limitadas não expõem texto ou ferramenta',function(){
 $c=aiChecks(array_fill(0,40,'ChatGPT PRIVATE SECRET com protocolo 123.'));same(32,count($c[0]['evidence']['samples']));same(8,$c[0]['evidence']['samples_omitted']);same(false,strpos(json_encode($c),'PRIVATE'));
});
test('serviço mantém verificação interna e não grava aprovação',function(){
 withFootnotePanel(docxParts(docxBody(['Declaração de IA','Ferramenta PRIVATE TOOL para tradução.','Referências'])),function($r,$service,$request){same('aiReviewTextPresent',$r['ai_review']['message']);same(1,$r['pending_groups']['deskPendingAi']);same(false,strpos(json_encode($r),'PRIVATE TOOL'));same([],$service->view($request,17)['events']);});
});

test('marcadores provisórios não aprovam uma declaração de uso ou não uso',function(){
 foreach(['N/A','Não se aplica.','A preencher.']as$text){same('indeterminado',aiChecks(['Declaração de IA',$text,'Referências'])[0]['status']);}
});
