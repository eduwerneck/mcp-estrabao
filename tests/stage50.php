<?php
declare(strict_types=1);
foreach (['A Alfa','An Alfa','The Alfa','O Alfa','Os Alfa','As Alfa','Um Alfa','Uma Alfa','El Alfa','La Alfa','Los Alfa','Las Alfa','Un Alfa','Una Alfa'] as $title) {
 test('artigo inicial com comparação concordante '.md5($title),function()use($title){$a=stage49Audit([stage49Ref($title),stage49Ref('Zebra estudo','2020b')]);same('conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_article_pairs_checked']);same('literal_and_single_initial_article_consensus',$a['title_article_strategy']);});
}
foreach (['The Zebra','O Zebra','Uma Zebra','El Zebra'] as $title) {
 test('inversão independente da leitura do artigo '.md5($title),function()use($title){$a=stage49Audit([stage49Ref($title,'2020b'),stage49Ref('Alfa estudo')]);same('não conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_article_pairs_checked']);});
}
foreach ([['O Alfa','Beta estudo'],['An Zebra','Beta estudo'],['A Zebra','Beta estudo'],['The Alfa','Beta estudo'],['The pesquisa','Pesquisa'],['The Alfa','The Beta'],['A','Zebra estudo'],['The A pesquisa','Zebra estudo'],['O Ávila','Beta estudo']] as $titles) {
 test('idioma papel lexical empate ou colação muda decisão '.md5(json_encode($titles)),function()use($titles){$a=stage49Audit([stage49Ref($titles[0]),stage49Ref($titles[1],'2020b')]);same('indeterminado',$a['title_order_status']);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);same(0,$a['title_article_pairs_checked']);});
}
test('artigo concordante permite detectar sufixos trocados',function(){
 $a=stage49Audit([stage49Ref('The Alfa','2020b'),stage49Ref('Zebra estudo')]);same('conforme',$a['title_order_status']);same('não conforme',$a['year_suffix_order_status']);
});
test('terceiro artigo ambíguo impede letras parciais',function(){
 $a=stage49Audit([stage49Ref('Alfa estudo'),stage49Ref('Beta estudo','2020b'),stage49Ref('O Alfa','2020c')]);same('indeterminado',$a['year_suffix_order_status']);same(0,$a['title_cohorts_checked']);
});
test('diagnóstico não exporta chave sem artigo',function(){
 $a=stage49Audit([stage49Ref('The Alfa','2020b'),stage49Ref('Zebra estudo')]);foreach(['The Alfa','Zebra estudo']as$t){same(false,strpos(json_encode([$a['title_order_samples'],$a['year_suffix_order_samples'],$a['title_article_strategy']]),$t)!==false);}
});
test('minuta conserva título e não presume idioma da referência',function(){
 $d=italicDocument([stage49Ref('The Alfa','2020b'),stage49Ref('Zebra estudo')],[['The Alfa'],['Zebra estudo']],[]);$before=$d;$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same($before,$d);same(true,strpos($draft['body'],'conferir a sequência dos sufixos')!==false);foreach(['The Alfa','Zebra estudo']as$t){same(false,strpos($draft['body'],$t)!==false);}
});
test('dois títulos com artigos podem ter ordem concordante em todas as leituras',function(){
 $a=stage49Audit([stage49Ref('The Alfa'),stage49Ref('The Zebra','2020b')]);same('conforme',$a['title_order_status']);same('conforme',$a['year_suffix_order_status']);same(1,$a['title_article_pairs_checked']);
});
