<?php
declare(strict_types=1);
function stage49Ref(string $title,string $year='2020a',string $author='Silva, A.'):string {return $author.' ('.$year.'). '.$title.'. Editora Exemplo.';}
function stage49Audit(array $rows):array {return apaAudit(italicDocument($rows,[],[]));}
foreach ([
 [['Beta estudo','2020b'],['Alfa estudo','2020a']],
 [['Zebra estudo','2020b'],['García estudo','2020a']],
 [['Ávila estudo','2020b'],['Álvares estudo','2020a']]
] as $rows) {
 test('inversão de títulos com letras já alinhadas '.md5(json_encode($rows)),function()use($rows){$a=stage49Audit(array_map(function($r){return stage49Ref($r[0],$r[1]);},$rows));same('não conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_cohorts_checked']);same(1,$a['title_pairs_checked']);same('não conforme',$a['recognized_formats_status']);});
}
foreach ([['Alfa estudo','2020b','Beta estudo','2020a'],['Alfa estudo','2020','Beta estudo','2020'],['Alfa estudo','2020a','Beta estudo','2020a'],['Alfa estudo','2020a','Beta estudo','2020c']] as $case) {
 test('sufixos incoerentes no conjunto seguro '.md5(json_encode($case)),function()use($case){$a=stage49Audit([stage49Ref($case[0],$case[1]),stage49Ref($case[2],$case[3])]);same('conforme',$a['title_order_status']);same('não conforme',$a['year_suffix_order_status']);same('não conforme',$a['recognized_formats_status']);});
}
test('sequência completa de três títulos e letras',function(){
 $a=stage49Audit([stage49Ref('Alfa estudo'),stage49Ref('Beta estudo','2020b'),stage49Ref('Gama estudo','2020c')]);same('conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(3,$a['title_pairs_checked']);
});
foreach ([['A pesquisa','Beta estudo'],['O Alfa','Beta estudo'],['Estudo: Alfa','Estudo Alfa'],['Alfa 2','Beta estudo'],['Estudo igual','Estudo igual'],['García estudo','Garcia estudo'],['Alves estudo','Ávila estudo']] as $titles) {
 test('títulos incertos ou duplicados não recebem letras '.md5(json_encode($titles)),function()use($titles){$a=stage49Audit([stage49Ref($titles[0],'2020b'),stage49Ref($titles[1],'2020a')]);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);});
}
foreach (['2020, May 2','s.d.','no prelo'] as $year) {
 test('data fora do ano numérico não recebe desempate '.$year,function()use($year){$a=stage49Audit([stage49Ref('Beta estudo',$year),stage49Ref('Alfa estudo',$year)]);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);});
}
test('referência com indicação original impede escolher letras da coorte',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020b').' (Original work published 1900)',stage49Ref('Alfa estudo')]);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);
});
test('terceiro título incerto impede atribuição parcial de letras',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020b'),stage49Ref('Alfa estudo'),stage49Ref('O Alfa','2020c')]);same('indeterminado',$a['year_suffix_order_status']);same('indeterminado',$a['title_order_status']);
});
test('coautorias diferentes não formam coorte de títulos',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020a','Silva, A., & Costa, B.'),stage49Ref('Alfa estudo','2020b','Silva, A., & Souza, B.')]);same('indeterminado',$a['title_order_status']);same(0,$a['title_cohorts_checked']);
});
test('iniciais diferentes não formam coorte de títulos',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020a','Silva, A.'),stage49Ref('Alfa estudo','2020b','Silva, B.')]);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('coorte maior que alfabeto simples permanece para revisão',function(){
 $refs=[];for($i=0;$i<27;$i++){$refs[]=stage49Ref('Titulo '.chr(65+intdiv($i,26)).chr(65+$i%26));}$a=stage49Audit($refs);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('falha de coorte segura não desaparece com outra incerta',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020b'),stage49Ref('Alfa estudo'),stage49Ref('O Alfa','2021a'),stage49Ref('A pesquisa','2021b')]);same('não conforme',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);
});
test('minuta orienta títulos e letras sem copiar bibliografia',function(){
 $d=italicDocument([stage49Ref('Beta estudo','2020a'),stage49Ref('Alfa estudo','2020b')],[],[]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'ordenar pelo título as obras da mesma autoria e ano')!==false);same(true,strpos($draft['body'],'conferir a sequência dos sufixos')!==false);foreach(['Silva','Alfa estudo','Beta estudo','2020']as$v){same(false,strpos($draft['body'],$v)!==false);}
});
test('republicação de outra edição protege ano original no conjunto',function(){
 $a=stage49Audit([stage49Ref('Alfa estudo'),stage49Ref('Beta estudo','2020'),stage49Ref('Outra obra','2021').' (Original work published 2020)']);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('conjunto incerto não apaga defeito comprovado independente',function(){
 $a=stage49Audit([stage49Ref('Alfa estudo','2020b'),stage49Ref('Beta estudo','2020a'),stage49Ref('O Alfa','2021a'),stage49Ref('Outra pesquisa','2021b')]);same('não conforme',$a['year_suffix_order_status']);same(1,$a['title_cohorts_checked']);
});
test('mais de vinte e seis obras não recebe sufixo inventado',function(){
 $refs=[];for($i=0;$i<27;$i++){$refs[]=stage49Ref('Estudo '.chr(65+intdiv($i,26)).chr(65+$i%26));}$a=stage49Audit($refs);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('iniciais distintas não formam grupo de títulos',function(){
 $a=stage49Audit([stage49Ref('Beta estudo','2020a','Silva, A.'),stage49Ref('Alfa estudo','2020b','Silva, B.')]);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('título de artigo reconhecido não se confunde com o periódico',function(){
 $a=stage49Audit(['Silva, A. (2020b). Beta estudo. Alfa Revista, 1(2), 10–12.','Silva, A. (2020a). Alfa estudo. Zebra Revista, 1(2), 10–12.']);same('não conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_cohorts_checked']);
});
