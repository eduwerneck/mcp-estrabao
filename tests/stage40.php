<?php
declare(strict_types=1);
function stage40Ref(string $date='2020, May 2',string $retrieval='',string $url='https://portal.synthetic.invalid/item',string $site='Portal Sintético'):string {
 return 'Silva, A. ('.$date.'). Página sintética.'.($site!==''?' '.$site.'.':'').($retrieval!==''?' '.$retrieval:'').' '.$url;
}
foreach (['2020, May 2'=>true,'2020, February 29'=>true,'2021, February 29'=>false,'2020, April 31'=>false,'2020, May 0'=>false,'2020, May 32'=>false,'2020, May'=>true,'2020, 29 de fevereiro'=>true,'2021, 29 de fevereiro'=>false,'2020, 2 de MARÇO'=>true,'2020, Foo 2'=>null] as $date=>$expected) {
 test('calendário declarado de referência com URL '.$date,function()use($date,$expected){
  $r=apaResults(italicDocument([stage40Ref($date)],[['Página sintética']]));$a=$r['references.apa7']['evidence']['style_audit'];$c=auditCodes($a);same($expected===null?false:true,isset($c['web_publication_calendar']));
  if($expected!==null){same($expected?'conforme':'não conforme',$c['web_publication_calendar']);}
  same($expected===false?'not_accepted':'manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Retrieved February 29, 2020, from'=>true,'Retrieved February 30, 2020, from'=>false,'Recuperado em 29 de fevereiro de 2020, de'=>true,'Recuperado em 31 de abril de 2020, de'=>false,'Retrieved Foo 2, 2020, from'=>null] as $retrieval=>$expected) {
 test('data de consulta informada '.md5($retrieval),function()use($retrieval,$expected){
  $r=apaResults(italicDocument([stage40Ref('2020',$retrieval)],[['Página sintética']]));$a=$r['references.apa7']['evidence']['style_audit'];same($expected===null?'indeterminado':($expected?'conforme':'não conforme'),auditCodes($a)['web_retrieval_calendar']);same($expected===false?'not_accepted':'manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['https://','https:///item','https://:80/item','https://portal.synthetic.invalid:65536/item','http://portal.synthetic.invalid:abc/item'] as $url) {
 test('URL HTTP estruturalmente inválida '.md5($url),function()use($url){
  $r=apaResults(italicDocument([stage40Ref('2020','',$url)],[['Página sintética']]));same('não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['web_url_syntax']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['https://portal.synthetic.invalid/item','http://portal.synthetic.invalid/item','https://127.0.0.1:8080/item','http://[::1]/item','http://0/item','https://portal.synthetic.invalid/item?x=1#part','https://münchen.synthetic.invalid/item','https://portal.synthetic.invalid./item'] as $url) {
 test('URL não é acessada nem presumida existente '.md5($url),function()use($url){
  $r=apaResults(italicDocument([stage40Ref('2020','',$url)],[['Página sintética']]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(false,$r['references.apa7']['evidence']['external_lookup_performed']);same(false,strpos(json_encode($r),$url)!==false);
 });
}
foreach (['2020','s.d.'] as $date) {
 test('ano ou ausência de data não exige data completa '.$date,function()use($date){
  $r=apaResults(italicDocument([stage40Ref($date)],[['Página sintética']]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(false,isset(auditCodes($r['references.apa7']['evidence']['style_audit'])['web_publication_calendar']));
 });
}
test('nome do site omitido não vira campo obrigatório presumido',function(){
 $r=apaResults(italicDocument([stage40Ref('2020','','https://portal.synthetic.invalid/item','')],[['Página sintética']]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('data de consulta ausente não é erro',function(){
 $a=apaAudit(italicDocument([stage40Ref()],[['Página sintética']]));same(false,isset(auditCodes($a)['web_retrieval_calendar']));same('indeterminado',auditCodes($a)['web_source_review']);
});
test('formatação ou URL não presume tipo para impor itálico',function(){
 $r=apaResults(italicDocument([stage40Ref()]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.recognized_formats']['status']);
});
test('artigo com URL continua artigo em vez de página',function(){
 $r=apaResults(italicDocument(['Silva, A. (2020). Artigo sintético. Revista Sintética, 8(2), 10–20. https://portal.synthetic.invalid/item'],[['Revista Sintética','8']]));same('journal_article',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('data impossível com URL não é ocultada por metadados desconhecidos',function(){
 $r=apaResults(italicDocument([stage40Ref('2020, February 30')],[]));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('minuta de calendário fornece correção sem copiar título endereço ou data',function(){
 $d=italicDocument([stage40Ref('2020, February 30')],[['Página sintética']]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'corrigir a data impossível')!==false);foreach(['Página sintética','portal.synthetic.invalid','February 30']as$value){same(false,strpos($draft['body'],$value)!==false);}
});
test('descrição de tese com URL mantém seu modelo explícito',function(){
 $r=apaResults(italicDocument([stage39Ref().' https://repository.synthetic.invalid/item'],[['Obra sintética']]));same('repository_thesis_declared',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
