<?php
declare(strict_types=1);
foreach (['da Silva','García Márquez','van der Meer','Castelo Branco','De Souza'] as $name) {
 foreach (['parenthetical','narrative','et_al'] as $form) {
  test('nome composto literal corresponde '.$name.$form,function()use($name,$form){
   $authors=$name.', A.'.($form==='et_al'?', Souza, B., & Lima, C.':'');
   $text=$form==='narrative'?'Segundo '.$name.' (2020).':'Texto ('.$name.($form==='et_al'?' et al.':'').', 2020).';
   $r=apaResults(referenceDocument([$text],[$authors.' (2020). Título. Editora.']));
   same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
  });
 }
}
foreach (['Texto (da Silva & García Márquez, 2020).','Segundo da Silva e García Márquez (2020).'] as $text) {
 test('dois autores compostos literais '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],['da Silva, A., & García Márquez, B. (2020). Título. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);
 });
}
foreach (['Texto (da Silva e García Márquez, 2020).','Segundo da Silva & García Márquez (2020).','Texto (da Silva, Souza, & Lima, 2020).'] as $text) {
 test('erro de formato composto chega ao bloqueio '.md5($text),function()use($text){
  $ref=strpos($text,'García')!==false?'da Silva, A., & García Márquez, B.':'da Silva, A., Souza, B., & Lima, C.';
  $r=apaResults(referenceDocument([$text],[$ref.' (2020). Título. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Estudo de Silva (2020).','A pesquisa de Silva (2020).','Segundo Outro de Silva (2020).'] as $text) {
 test('não infere sobrenome composto a partir da prosa '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],['Silva, A. (2020). Título. Editora.']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach ([['Texto (Silva, 2020).','da Silva'],['Texto (Márquez, 2020).','García Márquez'],['Texto (Meer et al., 2020).','van der Meer']] as $case) {
 test('forma parcial não vira alias nem ausência '.md5(json_encode($case)),function()use($case){
  $r=apaResults(referenceDocument([$case[0]],[$case[1].', A., Souza, B., & Lima, C. (2020). Título. Editora.']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('sobrenome diferente ausente é comprovável no subconjunto ampliado',function(){
 $r=apaResults(referenceDocument(['Texto (Ausente, 2020).'],['da Silva, A. (2020). Título. Editora.']));same('não conforme',$r['references.citation_matching']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('mesmo sobrenome composto e ano preservam ambiguidade',function(){
 $r=apaResults(referenceDocument(['Texto (da Silva, 2020).'],['da Silva, A. (2020). Título. Editora.','da Silva, B. (2020). Outro título. Editora.']));same('indeterminado',$r['references.citation_matching']['status']);
});
foreach (['como citado em','apud'] as $marker) {
 test('fonte secundária composta '.$marker,function()use($marker){
  $r=apaResults(referenceDocument(['Texto (García Márquez, 1980, '.$marker.' da Silva, 2020).'],['da Silva, A. (2020). Título. Editora.']));same('conforme',$r['references.citation_matching']['status']);same($marker==='apud'?'not_accepted':'manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('bibliografia em negrito com nome composto não se torna invisível',function(){
 $d=referenceDocument(['Texto (da Silva, 2020).'],['da Silva, A. (2020). Título. Editora.']);$last=count($d['paragraphs'])-1;$d['paragraphs'][$last]['format']['bold']=true;
 $r=apaResults($d);same('conforme',$r['references.citation_matching']['status']);
});
test('vocabulário composto excedido mantém revisão sem regex ilimitada',function(){
 $refs=[];for($i=0;$i<65;$i++){$refs[]='Autor'.chr(65+intdiv($i,26)).chr(65+$i%26).' Composto, A. (2020). Título. Editora.';}
 $r=apaResults(referenceDocument(['Texto (AutorAA Composto, 2020).'],$refs));$a=$r['references.apa7']['evidence']['analysis'];same(false,$a['compound_name_lookup_complete']);same('indeterminado',$r['references.citation_matching']['status']);
});
test('cauda não passa a certificar citações de corpo ausente',function(){
 $tail=referenceDocument([],['da Silva, A. (2020). Título. Editora.']);$r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);same('indeterminado',$r['references.citation_matching']['status']);
});
test('minuta por citação composta não copia nomes da referência',function(){
 $d=referenceDocument(['Texto (da Silva e García Márquez, 2020).'],['da Silva, A., & García Márquez, B. (2020). Título. Editora.']);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'usar & entre dois autores')!==false);same(false,strpos(json_encode($draft),'García')!==false);
});
test('DOCX composto incompatível não recebe liberação',function(){
 $d=referenceDocument(['Texto (da Silva e García Márquez, 2020).'],['da Silva, A., & García Márquez, B. (2020). Título. Editora.']);
 withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});});
});

test('espaços entre componentes não alteram a chave literal',function(){
 $r=apaResults(referenceDocument(['Texto (da Silva, 2020).'],['da  Silva, A. (2020). Título. Editora.']));same('conforme',$r['references.citation_matching']['status']);
});
test('sufixos de ano distinguem obras com o mesmo nome composto',function(){
 $r=apaResults(referenceDocument(['Texto (da Silva, 2020b).'],['da Silva, A. (2020a). Título. Editora.','da Silva, A. (2020b). Outro. Editora.']));same('conforme',$r['references.citation_matching']['status']);
});
test('DOCX real preserva vínculo literal composto e offsets Unicode',function(){
 $d=referenceDocument(['Texto (da Silva & García Márquez, 2020).'],['da Silva, A., & García Márquez, B. (2020). Título. Editora.']);
 withDocx(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($path){
  $r=apaResults((new \Estrabao\Document\DocxReader())->read($path));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
});

foreach ([['Silva','Silva Neto'],['Castelo','Castelo Branco']] as $pair) {
 test('componente parcial significativo exige revisão '.implode(' ',$pair),function()use($pair){
  $r=apaResults(referenceDocument(['Texto ('.$pair[0].', 2020).'],[$pair[1].', A. (2020). Título. Editora.']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
