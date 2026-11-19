<?php
declare(strict_types=1);
foreach (['n.d.','s.d.','n. d.','s. d.','in press','no prelo','n.d.-a','s.d.-b'] as $date) {
 foreach (['Texto (Silva, %s).','Silva (%s).'] as $shape) {
  test('data especial liga chave e exige conferência '.md5($date.$shape),function()use($date,$shape){
   $r=apaResults(referenceDocument([sprintf($shape,$date)],['Silva, A. ('.$date.'). Obra sintética. Editora.']));
   same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
   same(true,in_array('special_date_review',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
  });
 }
}
foreach ([['n.d.','s.d.'],['in press','no prelo'],['N. D.-A','s.d.-a']] as $dates) {
 test('marcadores equivalentes não são referência ausente '.md5(json_encode($dates)),function()use($dates){
  $r=apaResults(referenceDocument(['Texto (Silva, '.$dates[0].').'],['Silva, A. ('.$dates[1].'). Obra sintética. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach ([['s.d.','2020'],['2020','s.d.'],['no prelo','s.d.'],['s.d.-a','s.d.-b']] as $dates) {
 test('datas de estados divergentes da mesma autoria não provam ausência '.md5(json_encode($dates)),function()use($dates){
  $r=apaResults(referenceDocument(['Texto (Silva, '.$dates[0].').'],['Silva, A. ('.$dates[1].'). Obra sintética. Editora.']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['s.d.','no prelo'] as $date) {
 test('data especial não oculta citação ausente em contexto fechado '.$date,function()use($date){
  $r=apaResults(referenceDocument(['Texto (Ausente, '.$date.').'],['Silva, A. ('.$date.'). Obra sintética. Editora.']));same('não conforme',$r['references.citation_matching']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
 test('dois autores com data especial ainda exigem ampersand '.$date,function()use($date){
  $r=apaResults(referenceDocument(['Texto (Silva e Souza, '.$date.').'],['Silva, A., & Souza, B. ('.$date.'). Obra sintética. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
 test('instituição com data especial permanece revisável '.$date,function()use($date){
  $r=apaResults(referenceDocument(['Texto (Instituto de Estudos Sintéticos, '.$date.').'],['Instituto de Estudos Sintéticos. ('.$date.'). Obra sintética. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
 test('data especial com duplicatas não escolhe uma obra '.$date,function()use($date){
  $ref='Silva, A. ('.$date.'). Obra sintética. Editora.';$r=apaResults(referenceDocument(['Texto (Silva, '.$date.').'],[$ref,$ref]));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Silva (2020, s.d., no prelo).','Texto (Silva, no prelo, 2020, s.d.).'] as $text) {
 test('grupo com datas especiais não recebe ordem lexicográfica '.md5($text),function()use($text){
  $refs=[];foreach(['2020','s.d.','no prelo']as$date){$refs[]='Silva, A. ('.$date.'). Obra sintética. Editora.';}
  $r=apaResults(referenceDocument([$text],$refs));same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same(3,$r['references.apa7']['evidence']['analysis']['recognized_citations']);
 });
}
test('fonte secundária sem data liga só a obra consultada',function(){
 $r=apaResults(referenceDocument(['Texto (Original, 1990, como citado em Silva, s.d.).'],['Silva, A. (n.d.). Obra sintética. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('apud permanece vetado com fonte no prelo',function(){
 $r=apaResults(referenceDocument(['Texto (Original, 1990, apud Silva, no prelo).'],['Silva, A. (in press). Obra sintética. Editora.']));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('sufixo sem data não é atribuído ou removido',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, s.d.-b).'],['Silva, A. (n.d.-a). Obra sintética. Editora.','Silva, A. (n.d.-b). Outra obra sintética. Editora.']));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('data especial não prova extensão de et al',function(){
 $r=apaResults(referenceDocument(['Texto (Silva et al., s.d.).'],[stage36Ref(['Silva','Souza','Lima'],'n.d.')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('contexto incompleto impede falsa ausência com data especial',function(){
 $r=apaResults(referenceDocument(['Texto (Ausente, s.d.).'],['Silva, A. (s.d.). Obra sintética. Editora.','Referência incompleta.']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('minuta de citação especial usa correção sem copiar referência',function(){
 $d=referenceDocument(['Texto (Silva e Souza, s.d.).'],['Silva, A., & Souza, B. (n.d.). Obra sintética. Editora.']);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'usar & entre dois autores')!==false);same(false,strpos(json_encode($draft),'Souza')!==false);
});
test('DOCX com marcador equivalente liga obra sem reescrever arquivo',function(){
 $d=referenceDocument(['Texto (Silva, s.d.).'],['Silva, A. (n.d.). Obra sintética. Editora.']);
 withDocx(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($path){$hash=hash_file('sha256',$path);$r=apaResults((new \Estrabao\Document\DocxReader())->read($path));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same($hash,hash_file('sha256',$path));});
});

test('referência sem data protege contra imposição de sufixo numérico',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],['Silva, A. (2020a). Obra sintética. Editora.','Silva, A. (s.d.). Outra obra sintética. Editora.']));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('obra sem data não fortalece prova de desambiguação em ano numérico',function(){
 $r=apaResults(referenceDocument(['Texto (Silva et al., 2020).'],[stage36Ref(['Silva','Costa','Ramos']),stage36Ref(['Silva','Souza','Lima']),stage36Ref(['Silva','Souza','Ramos'],'s.d.')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
