<?php
declare(strict_types=1);
foreach ([
 ['Silva, A., & Souza, B.','Silva, A.'],
 ['Silva, A., & Souza, B.','Silva, A., & Costa, C.'],
 ['Silva, A., Souza, B., & Ramos, C.','Silva, A., Souza, B., & Costa, D.'],
 ['Silva, A., & da Silva, B.','Silva, A., & da Costa, C.'],
 ['Silva, A., & Ávila, B.','Silva, A., & Álvares, C.'],
 ['Silva, A., & Souza, B.','Silva, A., & García, C.']
] as $authors) {
 test('inversão comprovada entre listas declaradas '.md5(json_encode($authors)),function()use($authors){
  $a=stage44Audit([stage44Ref($authors[0],'2019'),stage44Ref($authors[1],'2021')]);same('não conforme',$a['alphabetical_order_status']);same('não conforme',$a['author_list_order_status']);same('não conforme',$a['recognized_formats_status']);same(1,$a['author_list_pairs_checked']);same([[5,6]],$a['author_list_order_samples']);same(0,$a['chronological_pairs_checked']);
 });
 test('ordem segura entre listas declaradas '.md5(json_encode($authors)),function()use($authors){
  $a=stage44Audit([stage44Ref($authors[1],'2021'),stage44Ref($authors[0],'2019')]);same('conforme',$a['alphabetical_order_status']);same('conforme',$a['author_list_order_status']);same(1,$a['author_list_pairs_checked']);same(0,$a['chronological_pairs_checked']);same('indeterminado',$a['chronological_order_status']);
 });
}
foreach ([
 ['Silva, B., & Souza, B.','Silva, A.'],
 ['Silva, A., & Souza, C.','Silva, A., & Souza, B.'],
 ['Silva, A., Souza, C., & Ramos, A.','Silva, A., Souza, B., & Costa, A.'],
 ['Silva, A., & García, B.','Silva, A., & Garcia, B.'],
 ['Silva, A., & da Silva, B.','Silva, A., & Silva, B.'],
 ['Silva, A., & de Souza, B.','Silva, A., & deSouza, B.'],
 ['Silva, A., & Souza, B.','Silva, A., Souza, B., & Costa, C.'],
 ['Silva, A., Souza, B., & Costa, C.','Silva, A., & Souza, B.']
] as $authors) {
 test('diferença incerta não é desempate por coautor posterior '.md5(json_encode($authors)),function()use($authors){
  $a=stage44Audit([stage44Ref($authors[0],'2021'),stage44Ref($authors[1],'2019')]);same('indeterminado',$a['alphabetical_order_status']);same('indeterminado',$a['author_list_order_status']);same('indeterminado',$a['recognized_formats_status']);same(0,$a['author_list_pairs_checked']);same([],$a['author_list_order_samples']);
 });
}
test('espaços em iniciais preservam prefixo comum declarado',function(){
 $a=stage44Audit([stage44Ref('Silva, A. B., & Souza, C.'),stage44Ref('Silva, A.B.')]);same('não conforme',$a['author_list_order_status']);
});
test('cronologia só compara listas inteiras idênticas',function(){
 $a=stage44Audit([stage44Ref('Silva, A., & Souza, B.','2019'),stage44Ref('Silva, A., & Souza, B.','2021')]);same('conforme',$a['chronological_order_status']);same(1,$a['chronological_pairs_checked']);same(0,$a['author_list_pairs_checked']);same('indeterminado',$a['author_list_order_status']);
});
test('inversão de listas independe de data especial',function(){
 $a=stage44Audit([stage44Ref('Silva, A., & Souza, B.','no prelo'),stage44Ref('Silva, A.','n.d.')]);same('não conforme',$a['author_list_order_status']);same(0,$a['chronological_pairs_checked']);
});
test('incerteza não apaga inversão conhecida de listas',function(){
 $a=stage44Audit([stage44Ref('Silva, A., & Souza, B.'),stage44Ref('Silva, A.'),stage44Ref('Silva, B.')]);same('não conforme',$a['author_list_order_status']);same('não conforme',$a['alphabetical_order_status']);same('não conforme',$a['recognized_formats_status']);
});
test('autoria institucional impede presumir ordenação global pessoal',function(){
 $a=stage44Audit([stage44Ref('Silva, A., & Souza, B.'),'Instituto Sintético. (2020). Obra sintética. Editora Exemplo.',stage44Ref('Silva, A.')]);same('indeterminado',$a['author_list_order_status']);same(0,$a['author_list_pairs_checked']);
});
test('lista abreviada impede prova de ordem entre coautorias',function(){
 $a=stage44Audit([stage44Ref('Silva, A., et al.'),stage44Ref('Silva, A.')]);same('indeterminado',$a['author_list_order_status']);same(0,$a['author_list_pairs_checked']);
});
test('limites de amostras de listas não copiam autoria',function(){
 $refs=[];for($i=0;$i<40;$i++){$refs[]=stage44Ref($i%2?'Silva, A.':'Silva, A., & Souza, B.');}$a=stage44Audit($refs);same('não conforme',$a['author_list_order_status']);same(16,count($a['author_list_order_samples']));same(39,$a['author_list_pairs_checked']);same(false,strpos(json_encode($a['author_list_order_samples']),'Silva')!==false);
});
test('coautoria em falta de limite de extração permanece indeterminada',function(){
 $a=\Estrabao\Document\ReferenceOrder::analyze([['text'=>stage44Ref('Silva, A., & Souza, B.'),'index'=>1],['text'=>stage44Ref(),'index'=>2]],false);same('indeterminado',$a['author_list_order_status']);same(0,$a['author_list_pairs_checked']);
});
test('minuta orienta ordenação das obras sem mudar autoria interna',function(){
 $d=italicDocument([stage44Ref('Silva, A., & Souza, B.'),stage44Ref()],[['Obra sintética'],['Obra sintética']],[]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'apresentar autor único antes de coautoria')!==false);same(true,strpos($draft['body'],'sem alterar a ordem dos autores de cada obra')!==false);foreach(['Silva','Souza','2020','Obra sintética']as$v){same(false,strpos($draft['body'],$v)!==false);}
});
