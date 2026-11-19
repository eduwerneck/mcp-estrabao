<?php
declare(strict_types=1);
function stage36Ref(array $names,string $year='2020',string $first='A'):string {
 $authors=[];foreach($names as$i=>$name){$authors[]=$name.', '.($i===0?$first:chr(65+$i)).'.';}$last=array_pop($authors);
 return ($authors?implode(', ',$authors).', & ':'').$last.' ('.$year.'). Obra sintética. Editora Exemplo.';
}
$stage36A=['Silva','Souza','Lima','Ramos','Nunes'];$stage36B=['Silva','Costa','Alves','Paiva','Rocha'];
foreach (['Texto (Silva et al., 2020).','Silva et al. (2020).'] as $text) {
 test('et al colidente exige desambiguação '.md5($text),function()use($text,$stage36A,$stage36B){
  $r=apaResults(referenceDocument([$text],[stage36Ref($stage36A),stage36Ref($stage36B)]));same('indeterminado',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
  same(true,in_array('et_al_disambiguation_required',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
 });
}
foreach (['Texto (Silva, Souza, et al., 2020).','Silva, Souza, et al. (2020).'] as $text) {
 test('prefixo suficiente com pelo menos dois omitidos '.md5($text),function()use($text,$stage36A,$stage36B){
  $r=apaResults(referenceDocument([$text],[stage36Ref($stage36A),stage36Ref($stage36B)]));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('prefixo expandido ainda colidente é bloqueado',function(){
 $a=['Silva','Souza','Lima','Ramos','Nunes'];$b=['Silva','Souza','Costa','Paiva','Rocha'];
 $r=apaResults(referenceDocument(['Texto (Silva, Souza, et al., 2020).'],[stage36Ref($a),stage36Ref($b)]));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('et al não substitui apenas um autor numa expansão necessária',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, Souza, et al., 2020).'],[stage36Ref(['Silva','Souza','Lima']),stage36Ref(['Silva','Costa','Ramos'])]));
 same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same(true,in_array('et_al_minimum_omission',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
});
foreach ([['Silva'],['Silva','Souza']] as $names) {
 test('forma et al exige pelo menos dois nomes omitidos '.count($names),function()use($names){
  $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($names)]));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(true,in_array('et_al_minimum_omission',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
 });
}
foreach (['Texto (Silva, Souza, & Lima, 2020).','Silva, Souza e Lima (2020).'] as $text) {
 test('nomes por extenso necessários podem desambiguar '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage36Ref(['Silva','Souza','Lima']),stage36Ref(['Silva','Costa','Ramos'])]));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);
 });
}
test('nomes a mais permanecem para revisão da extensão',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva, Souza, Lima, et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]));same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('lista integral desnecessária em contexto de colisão permanece revisável',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva, Souza, Lima, Ramos, & Nunes, 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('iniciais distintas não são a mesma pessoa declarada',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B,'2020','B')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
foreach ([false,true] as $differentInitial) {
 test('listas indistinguíveis por sobrenomes não ganham nomes inventados '.(int)$differentInitial,function()use($stage36A,$differentInitial){
  $a=stage36Ref($stage36A);$b=$differentInitial?str_replace('Souza, B.','Souza, F.',$a):$a;
  $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[$a,$b]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('referência não reconhecida impede diagnóstico de colisão',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B),'Silva, Alberto. (2020). Obra. Editora.']));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('fontes secundárias preservam revisão de contexto não bibliografado',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).','Texto (Silva, 2020, como citado em Souza, 2021).'],[stage36Ref($stage36A),stage36Ref($stage36B),'Souza, A. (2021). Obra. Editora.']));same(false,$r['references.apa7']['evidence']['analysis']['et_al_context_complete']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('corpo parcial com notas não permite afirmar desambiguação completa',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]);
 $r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_body'=>$d]);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('coorte maior que 32 fica para revisão',function(){
 $refs=[];for($i=0;$i<33;$i++){$refs[]=stage36Ref(['Silva','Autor'.chr(65+intdiv($i,26)).chr(65+$i%26),'Lima']);}
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],$refs));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('quantidades diferentes não escolhem automaticamente a fonte',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, Souza, et al., 2020).'],[stage36Ref(['Silva','Souza','Lima','Ramos']),stage36Ref(['Silva','Souza','Costa'])]));same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('anos diferentes não criam colisão et al',function()use($stage36A,$stage36B){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B,'2021')]));same('conforme',$r['references.apa7.citation_format']['status']);
});
test('sufixos distintos já distinguem chaves declaradas',function()use($stage36A){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020a).'],[stage36Ref($stage36A,'2020a'),stage36Ref($stage36A,'2020b')]));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);
});
test('apelido institucional não vira pessoa para desambiguar',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Instituto de Estudos Sintéticos [SILVA], 2020).','Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B),stage31Ref()]);
 $r=apaResults($d);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('forma parcial não cria referência ausente nem força expansão',function(){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref(['da Silva','Souza','Lima']),stage36Ref(['Silva','Costa','Ramos'])]));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('minuta fornece correção sem copiar listas de autores',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'desambiguar a citação abreviada')!==false);same(false,strpos(json_encode($draft),'Souza')!==false);
});
test('DOCX com abreviação ambígua bloqueia liberação',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]);
 withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});});
});
test('coorte de 32 ainda diagnostica abreviação colidente',function(){
 $refs=[];for($i=0;$i<32;$i++){$refs[]=stage36Ref(['Silva','Autor'.chr(65+intdiv($i,26)).chr(65+$i%26),'Lima']);}
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],$refs));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('inventário de notas não confirmado impede prova de contexto completo',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]);$d['footnotes']=['coverage_complete'=>false,'note_count'=>0];
 $r=apaResults($d);same(false,$r['references.apa7']['evidence']['analysis']['et_al_context_complete']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('DOCX confirma expansão suficiente sem copiar ou mudar autores',function()use($stage36A,$stage36B){
 $d=referenceDocument(['Texto (Silva, Souza, et al., 2020).'],[stage36Ref($stage36A),stage36Ref($stage36B)]);
 withDocx(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($path){$r=apaResults((new \Estrabao\Document\DocxReader())->read($path));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);});
});
