<?php
declare(strict_types=1);
function stage32Ref(string $name='Souza',string $year='2020'):string{return $name.', A. ('.$year.'). Título sintético. Editora Exemplo.';}
foreach ([
 'Texto (Silva, 1980, como citado em Souza, 2020).',
 'Texto (Silva, como citado em Souza, 2020).',
 'Silva (1980, como citado em Souza, 2020).',
 'Silva (como citado em Souza, 2020).',
 'Segundo Silva (1980, como citado em Souza, 2020).',
 'Texto (Silva, 1980, as cited in Souza, 2020, p. 10).',
] as $text) {
 test('fonte secundária consulta apenas obra consultada '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage32Ref()]));$a=$r['references.apa7']['evidence']['analysis'];
  same(1,$a['recognized_citations']);same(1,$a['matched_citations']);same(0,$a['unresolved_citations']);same(true,$a['citation_samples'][0]['secondary']);
  same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
  same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['apud','APUD','Apud'] as $marker) {
 foreach (['Souza','Ausente'] as $name) {
  test('regra editorial rejeita '.$marker.' mesmo sem vínculo '.$name,function()use($marker,$name){
   $r=apaResults(referenceDocument(['Texto (Silva, 1980, '.$marker.' '.$name.', 2020).'],[stage32Ref()]));
   same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
   same('proven_editorial_citation_incompatibility',$r['references.apa7']['evidence']['admission_policy']['reason']);
   same(false,$r['references.apa7']['evidence']['secondary_citation_policy']['apud_allowed']);
  });
 }
}
test('original na lista não é proibido nem tratado como obra consultada',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 1980, como citado em Souza, 2020).'],[stage32Ref('Silva','1980'),stage32Ref()]));
 $a=$r['references.apa7']['evidence']['analysis'];same(1,$a['matched_citations']);same([1],$a['citation_samples'][0]['reference_indices']);
 same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('obra consultada ausente bloqueia sem procurar original',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 1980, como citado em Souza, 2020).'],[stage32Ref('Lima')]));
 same('não conforme',$r['references.citation_matching']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('obra consultada duplicada exige desambiguação',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 1980, como citado em Souza, 2020).'],[stage32Ref(),stage32Ref()]));
 same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('sigla institucional consultada continua sem inferência de identidade',function(){
 $r=apaResults(referenceDocument(['Texto (Instituto de Estudos Sintéticos [IES], 2020).','Texto (Silva, 1980, como citado em IES, 2020).'],[stage31Ref()]));
 same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
foreach (['O termo apud é discutido aqui. Texto (Souza, 2020).','Souza (2020) discute o emprego da palavra apud.','Apud é uma expressão latina.','Texto (Souza, 2020).'] as $text) {
 test('menção explicativa não vira infração por apud '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage32Ref()]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
  foreach($r['references.apa7']['evidence']['analysis']['citation_style_samples']as$s){same(false,in_array('secondary_apud_not_allowed',$s['issues'],true));}
 });
}
foreach (['Texto (Silva, 1980, como citado em Souza).','Texto (Silva, 1980, como citado em Souza, 1990, como citado em Lima, 2020).','Texto (Silva, nota sobre apud, Souza, 2020).','Texto (Silva, 1980, apud de Souza, 2020).'] as $text) {
 test('forma secundária não suportada exige conferência '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage32Ref()]));same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
 });
}
test('minuta aponta veto editorial sem copiar bibliografia nem chamar apud de ABNT',function(){
 $doc=referenceDocument(['Texto privado (Silva, 1980, apud Souza, 2020).'],[stage32Ref()]);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
 $draft=\Estrabao\Reporting\DeskResponse::generate($report);
 same(true,strpos($draft['body'],'não utilizar apud, conforme a diretriz editorial da Estrabão')!==false);
 same(false,strpos($draft['body'],'padrões ABNT reconhecidos')!==false);same(false,strpos(json_encode($draft),'Texto privado')!==false);same(false,strpos(json_encode($draft),'Souza')!==false);
});
test('DOCX com apud não recebe liberação mesmo com referência da fonte consultada',function(){
 $doc=referenceDocument(['Texto (Silva, 1980, apud Souza, 2020).'],[stage32Ref()]);
 withFootnotePanel(docxParts(docxBody(array_column($doc['paragraphs'],'text'))),function($r,$service,$request){
  same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});
 });
});

test('adaptador informa fonte consultada ausente sem copiar nomes',function(){
 $doc=referenceDocument(['Texto (Silva, 1980, como citado em Souza, 2020).'],[stage32Ref('Lima')]);
 withFootnotePanel(docxParts(docxBody(array_column($doc['paragraphs'],'text'))),function($r,$service,$request){
  $analysis=$service->view($request,17)['analysis'];same('not_accepted',$analysis['decision']);
  $text=implode(' ',$analysis['corrections']);same(true,strpos($text,'fonte efetivamente consultada')!==false);same(false,strpos($text,'Souza')!==false);
 });
});

foreach (['Texto (Silva apud Souza, 2020).','Silva (apud Souza, 2020).','Silva apud Souza (2020).','Texto (SILVA, 1980, APUD SOUZA, 2020).'] as $text) {
 test('apud em formas narrativas e parentéticas comuns bloqueia '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage32Ref()]));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same('proven_editorial_citation_incompatibility',$r['references.apa7']['evidence']['admission_policy']['reason']);
 });
}
test('fonte secundária narrativa com dois autores mantém conjunção textual',function(){
 $r=apaResults(referenceDocument(['Silva como citado em Souza e Lima (2020).'],['Souza, A., & Lima, B. (2020). Título. Editora.']));
 same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});

test('orientação interna inconclusiva não é enviada como correção ao autor',function(){
 $doc=referenceDocument(['Texto (Silva, 1980, apud Souza, 2020).'],[stage32Ref()]);
 withFootnotePanel(docxParts(docxBody(array_column($doc['paragraphs'],'text'))),function($r,$service,$request){
  $text=implode(' ',$service->view($request,17)['analysis']['corrections']);same(true,strpos($text,'A Estrabão não aceita apud')!==false);same(false,strpos($text,'não exigir a obra original')!==false);
 });
});
