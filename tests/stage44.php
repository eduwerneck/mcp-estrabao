<?php
declare(strict_types=1);
function stage44Audit(array $refs):array { return apaAudit(italicDocument($refs,array_fill(0,count($refs),['Obra sintética']),[])); }
function stage44Ref(string $authors='Silva, A.',string $year='2020',string $tail=''):string {return $authors.' ('.$year.'). Obra sintética. Editora Exemplo.'.$tail;}
foreach ([[['Silva, A.','2021'],['Silva, A.','2019']], [['Silva, A., & Souza, B.','2021'],['Silva, A., & Souza, B.','2019']]] as $case) {
 test('mesma autoria declarada em ordem cronológica inversa '.md5(json_encode($case)),function()use($case){$refs=array_map(function($v){return stage44Ref($v[0],$v[1]);},$case);$a=stage44Audit($refs);same('não conforme',$a['chronological_order_status']);same('não conforme',$a['recognized_formats_status']);same([[5,6]],$a['chronological_order_samples']);});
}
foreach ([['2019','2020','2021'],['0001','0010','2020']] as $years) {
 test('mesma autoria em cronologia crescente '.md5(json_encode($years)),function()use($years){$a=stage44Audit(array_map(function($y){return stage44Ref('Silva, A.',$y);},$years));same('conforme',$a['chronological_order_status']);same(2,$a['chronological_pairs_checked']);});
}
foreach ([['2020','2020'],['2020b','2020a'],['2020','s.d.'],['no prelo','2019']] as $years) {
 test('empates sufixos e datas especiais não são ordenados automaticamente '.md5(json_encode($years)),function()use($years){$a=stage44Audit(array_map(function($y){return stage44Ref('Silva, A.',$y);},$years));same('indeterminado',$a['chronological_order_status']);same('indeterminado',$a['recognized_formats_status']);});
}
foreach ([['Silva, A.','Silva, B.'],['Silva, A., & Souza, B.','Silva, A., & Souza, C.'],['Silva, A.','Silva, A., & Souza, B.'],['Silva, A., & de Souza, B.','Silva, A., & deSouza, B.']] as $authors) {
 test('iniciais e listas distintas não são a mesma autoria '.md5(json_encode($authors)),function()use($authors){$a=stage44Audit([stage44Ref($authors[0],'2021'),stage44Ref($authors[1],'2019')]);same('indeterminado',$a['chronological_order_status']);same('indeterminado',$a['recognized_formats_status']);});
}
foreach ([['Silva, A.','García, B.'],['da Silva, A.','da Costa, B.'],['Ávila, A.','Álvares, B.'],["D’Ávila, A.","D’Álvares, B."],['Souza, A.','García Márquez, B.']] as $authors) {
 test('prefixo literal decide antes de colação incerta '.md5(json_encode($authors)),function()use($authors){$a=stage44Audit([stage44Ref($authors[0]),stage44Ref($authors[1])]);same('não conforme',$a['alphabetical_order_status']);same([[5,6]],$a['order_samples']);});
}
foreach ([['García, A.','Silva, B.'],['da Costa, A.','da Silva, B.'],['Álvares, A.','Ávila, B.']] as $authors) {
 test('prefixo seguro em ordem '.md5(json_encode($authors)),function()use($authors){$a=stage44Audit([stage44Ref($authors[0]),stage44Ref($authors[1])]);same('conforme',$a['alphabetical_order_status']);});
}
foreach ([['Alves, A.','Ávila, B.'],['García, A.','Garcia, B.'],['de Souza, A.','deSouza, B.'],['D-Silva, A.',"D’Silva, B."]] as $authors) {
 test('acentos partículas e pontuação não são removidos '.md5(json_encode($authors)),function()use($authors){$a=stage44Audit([stage44Ref($authors[0]),stage44Ref($authors[1])]);same('indeterminado',$a['alphabetical_order_status']);});
}
test('declaração original impede escolher ano de ordenação',function(){
 $a=stage44Audit([stage44Ref('Silva, A.','2021',' (Original work published 1900)'),stage44Ref('Silva, A.','2019')]);same('indeterminado',$a['chronological_order_status']);same('indeterminado',$a['recognized_formats_status']);
});
test('data completa mantém comparação alfabética já disponível',function(){
 $a=stage44Audit([stage44Ref('Zebra, A.','2020, May 2'),stage44Ref('Alves, A.','2020')]);same('não conforme',$a['alphabetical_order_status']);
});
test('data completa não recebe cronologia por ano presumido',function(){
 $a=stage44Audit([stage44Ref('Silva, A.','2021, May 2'),stage44Ref('Silva, A.','2019')]);same('indeterminado',$a['chronological_order_status']);
});
test('falha conhecida não desaparece por comparação acentuada inconclusiva',function(){
 $a=stage44Audit([stage44Ref('Zebra, A.'),stage44Ref('Silva, A.'),stage44Ref('Sá, A.')]);same('não conforme',$a['alphabetical_order_status']);
});
test('autor institucional preserva revisão global da ordenação',function(){
 $a=stage44Audit([stage44Ref('Zebra, A.'),'Instituto de Estudos Sintéticos. (2020). Obra sintética. Editora Exemplo.',stage44Ref('Alves, A.')]);same('indeterminado',$a['alphabetical_order_status']);
});
test('lista incompleta não prova falha de ordenação',function(){
 $d=italicDocument([stage44Ref('Zebra, A.'),stage44Ref('Alves, A.')],array_fill(0,2,['Obra sintética']),[]);foreach($d['paragraphs'] as &$p){if($p['text']==='Referências'){$p['text']='Lista não identificada';}}unset($p);$a=apaAudit($d);same(false,$a['coverage_complete']);same('indeterminado',$a['alphabetical_order_status']);
});
test('normalização conserva limites entre sobrenome e iniciais',function(){
 $a=stage44Audit([stage44Ref('Silva, A.B.','2019'),stage44Ref('Silva, A. B.','2020')]);same('conforme',$a['chronological_order_status']);same(1,$a['chronological_pairs_checked']);
});
test('minuta explica cronologia sem copiar autoria ou anos',function(){
 $d=italicDocument([stage44Ref('Silva, A.','2021'),stage44Ref('Silva, A.','2019')],array_fill(0,2,['Obra sintética']),[]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'ordenar cronologicamente as obras com a mesma autoria declarada')!==false);foreach(['Silva','2021','2019','Obra sintética']as$value){same(false,strpos($draft['body'],$value)!==false);}
});

foreach ([['Silva, A.','da Silva, A.'],['da Silva, A.','de Silva, A.'],['Márquez, A.','García Márquez, A.']] as $authors) {
 test('grafia potencialmente parcial não prova ordem inversa '.md5(json_encode($authors)),function()use($authors){$a=stage44Audit([stage44Ref($authors[0]),stage44Ref($authors[1])]);same('indeterminado',$a['alphabetical_order_status']);same('indeterminado',$a['recognized_formats_status']);});
}
