<?php
declare(strict_types=1);
function stage52Ref(string $date,string $title='Página sintética',string $author='Silva, A.',string $tail=''):string {return $author.' ('.$date.'). '.$title.'. Portal Sintético.'.$tail.' https://portal.synthetic.invalid/item';}
function stage52Audit(array $refs):array {return apaAudit(italicDocument($refs,[],[]));}
foreach (['2020, May 2'=>[2020,5,2],'2020, 2 de maio'=>[2020,5,2],'2020, February 29'=>[2020,2,29],'2020, 2 de MARÇO'=>[2020,3,2]] as $date=>$expected){test('data completa possui três campos explícitos '.$date,function()use($date,$expected){same($expected,\Estrabao\Document\WebReference::publicationParts($date));});}
foreach (['2020','2020, May','2021, February 29','2020, April 31','2020, Foo 2','2020, May 0','0000, May 2','2020a, May 2','2020-05-02','2020, mayo 2'] as $date){test('data incompleta inválida ou fora da gramática não recebe dia presumido '.$date,function()use($date){same(null,\Estrabao\Document\WebReference::publicationParts($date));});}
foreach ([['2020, May 10','2020, May 2'],['2020, June 2','2020, May 30'],['2021, January 1','2020, December 31'],['2020, 2 de junho','2020, 30 de maio'],['2020, March 1','2020, February 29']] as $dates){
 test('datas explícitas em ordem inversa '.md5(json_encode($dates)),function()use($dates){$a=stage52Audit([stage52Ref($dates[0]),stage52Ref($dates[1],'Outra página')]);same('não conforme',$a['full_date_order_status']);same(1,$a['full_date_pairs_checked']);same([[5,6]],$a['full_date_order_samples']);same('não conforme',$a['recognized_formats_status']);same('indeterminado',$a['year_suffix_order_status']);});
 test('datas explícitas em ordem crescente '.md5(json_encode($dates)),function()use($dates){$a=stage52Audit([stage52Ref($dates[1]),stage52Ref($dates[0],'Outra página')]);same('conforme',$a['full_date_order_status']);same(1,$a['full_date_pairs_checked']);same('indeterminado',$a['year_suffix_order_status']);});
}
foreach ([['2020, May 2','2020'],['2020, May 2','2020, May'],['2020, May 2','2020, May 2'],['2020, May 2','2020, Foo 3'],['2020, May 2','s.d.']] as $dates){
 test('precisão mista empate ou falta de data exige revisão '.md5(json_encode($dates)),function()use($dates){$a=stage52Audit([stage52Ref($dates[0]),stage52Ref($dates[1],'Outra página')]);same('indeterminado',$a['full_date_order_status']);same(0,$a['full_date_pairs_checked']);same([],$a['full_date_order_samples']);});
}
test('sufixos com datas completas são explicitamente humanos',function(){
 $a=stage52Audit([stage52Ref('2020, May 2'),stage52Ref('2020, May 10','Outra página')]);same(true,$a['full_date_suffix_review_required']);same('manual_review_for_precise_dates',$a['full_date_suffix_policy']);same(true,strpos(implode(' ',$a['full_date_review']),'sufixos')!==false);same('indeterminado',$a['year_suffix_order_status']);
});
test('autoria distinta não recebe comparação cronológica por sobrenome',function(){
 $a=stage52Audit([stage52Ref('2020, May 10','Página sintética','Silva, A.'),stage52Ref('2020, May 2','Outra página','Silva, B.')]);same('indeterminado',$a['full_date_order_status']);same(0,$a['full_date_pairs_checked']);
});
test('publicação original real é preservada no fallback de data completa',function(){
 $a=stage52Audit([stage52Ref('2020, May 10','Página sintética','Silva, A.',' (Original work published 1900)'),stage52Ref('2020, May 2','Outra página')]);same('indeterminado',$a['full_date_order_status']);same(0,$a['full_date_pairs_checked']);
});
test('publicação original ambígua protege a comparação de edições',function(){
 $a=stage52Audit([stage52Ref('2020, May 10','Página sintética','Silva, A.',' (Original work published desconhecido)'),stage52Ref('2020, May 2','Outra página')]);same('indeterminado',$a['full_date_order_status']);
});
test('número de ano isolado não vira data completa',function(){
 $a=stage52Audit([stage52Ref('2019'),stage52Ref('2020','Outra página')]);same('indeterminado',$a['full_date_order_status']);same(0,$a['full_date_pairs_checked']);same(false,$a['full_date_suffix_review_required']);same([],$a['full_date_review']);
});
test('incerteza posterior não apaga inversão de datas completas',function(){
 $a=stage52Audit([stage52Ref('2020, May 10'),stage52Ref('2020, May 2','Outra página'),stage52Ref('2020','Terceira página')]);same('não conforme',$a['full_date_order_status']);same(1,$a['full_date_pairs_checked']);
});
test('minuta de datas não copia dia mês ano ou fonte',function(){
 $refs=[stage52Ref('2020, May 10'),stage52Ref('2020, May 2','Outra página')];$d=italicDocument($refs,[],[]);$before=$d;$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same($before,$d);same(true,strpos($draft['body'],'conferir a ordem das datas completas declaradas')!==false);foreach(['May','2020','Silva','Página sintética','portal.synthetic.invalid'] as $v){same(false,strpos($draft['body'],$v)!==false);}
});
test('painel privado informa revisão de datas sem exportar valores',function(){
 $d=referenceDocument([], [stage52Ref('2020, May 2'),stage52Ref('2020, May 10','Outra página')]);withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){$v=$service->view($request,17);same(true,strpos(implode(' ',$v['analysis']['full_date_review']),'sufixos')!==false);same(false,strpos(json_encode($v['analysis']['full_date_review']),'May')!==false);same('manual_review_required',$v['analysis']['decision']);});
});
