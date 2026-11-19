<?php
declare(strict_types=1);
foreach ([
 ['Estudo: Beta','Estudo: Alfa'],['Beta: NASA','Alfa: ONU'],
 ['Estudo, Beta','Estudo, Alfa'],['Estudo; Beta','Estudo; Alfa'],
 ['Estudo-Beta','Estudo-Alfa'],['Estudo – Beta','Estudo – Alfa'],
 ['Estudo—Beta','Estudo—Alfa'],['Estudo & Beta','Estudo & Alfa'],
 ['COVID-Estudo Beta','COVID-Estudo Alfa'],['D’Ávila: estudo','D’Álvares: estudo'],
 ["D'Ávila: estudo","D'Álvares: estudo"],['Beta estudo?','Alfa estudo?']
] as $titles) {
 test('pontuação compartilhada ou posterior à diferença segura '.md5(json_encode($titles)),function()use($titles){$a=stage49Audit([stage49Ref($titles[0],'2020b'),stage49Ref($titles[1])]);same('não conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_punctuation_pairs_checked']);same('literal_common_punctuation_and_letter_decision',$a['title_punctuation_strategy']);});
 test('títulos pontuados em ordem segura '.md5(json_encode($titles)),function()use($titles){$a=stage49Audit([stage49Ref($titles[1]),stage49Ref($titles[0],'2020b')]);same('conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);});
}
foreach ([
 ['Estudo: Alfa','Estudo Alfa'],['Estudo-Alfa','Estudo Alfa'],['Estudo: Alfa','Estudo; Alfa'],
 ['Alfa','Alfa!'],['Alfa!','Alfa?'],['D’Ávila estudo',"D'Ávila estudo"],
 ['Estudo:','Zebra estudo'],['Estudo:: Alfa','Zebra estudo'],['Estudo (Alfa)','Zebra estudo'],
 ['Estudo 2: Alfa','Zebra estudo'],['A: pesquisa','Zebra estudo'],['Estudo/Alfa','Zebra estudo']
] as $titles) {
 test('pontuação ou estrutura incerta não recebe ordem presumida '.md5(json_encode($titles)),function()use($titles){$a=stage49Audit([stage49Ref($titles[0]),stage49Ref($titles[1],'2020b')]);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);same(0,$a['title_punctuation_pairs_checked']);});
}
test('sufixos trocados em títulos com subtítulo são detectados',function(){
 $a=stage49Audit([stage49Ref('Estudo: Alfa','2020b'),stage49Ref('Estudo: Beta')]);same('conforme',$a['title_order_status']);same('não conforme',$a['year_suffix_order_status']);
});
test('artigos iniciais continuam exigindo concordância com subtítulos',function(){
 $a=stage49Audit([stage49Ref('The Alfa: estudo'),stage49Ref('Zebra: estudo','2020b')]);same('conforme',$a['title_order_status']);same(1,$a['title_article_pairs_checked']);same(1,$a['title_punctuation_pairs_checked']);
 $b=stage49Audit([stage49Ref('O Alfa: estudo'),stage49Ref('Beta: estudo','2020b')]);same('indeterminado',$b['title_order_status']);
});
test('conjunto com terceiro título de pontuação incerta não recebe letras parciais',function(){
 $a=stage49Audit([stage49Ref('Estudo: Alfa'),stage49Ref('Estudo: Beta','2020b'),stage49Ref('Estudo Alfa','2020c')]);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('minuta preserva nomes próprios siglas e texto original',function(){
 $refs=[stage49Ref('COVID-Estudo: Beta','2020b'),stage49Ref('COVID-Estudo: Alfa')];$d=italicDocument($refs,[['COVID-Estudo: Beta'],['COVID-Estudo: Alfa']],[]);$before=$d;$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same($before,$d);same(true,strpos($draft['body'],'ordenar pelo título as obras da mesma autoria e ano')!==false);foreach(['COVID','Silva','Beta','Alfa']as$v){same(false,strpos($draft['body'],$v)!==false);}
});
