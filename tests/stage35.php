<?php
declare(strict_types=1);
function stage35Ref(string $year,string $name='Silva',string $initial='A'):string { return $name.', '.$initial.'. ('.$year.'). Título '.$year.'. Editora Exemplo.'; }
foreach (['Texto (Silva, 2019, 2020).','Silva (2019, 2020).','Segundo Silva (2019, 2020).','Texto (Silva, 2020a, 2020b).'] as $text) {
 test('anos explícitos ordenados ligam cada obra '.md5($text),function()use($text){
  $years=strpos($text,'2020a')!==false?['2020a','2020b']:['2019','2020'];$r=apaResults(referenceDocument([$text],array_map('stage35Ref',$years)));
  same('conforme',$r['references.citation_matching']['status']);same(2,$r['references.apa7']['evidence']['analysis']['matched_citations']);same('conforme',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Texto (Silva, 2020, 2019).','Silva (2020, 2019).','Texto (Silva, 2020b, 2020a).'] as $text) {
 test('anos invertidos bloqueiam por formato '.md5($text),function()use($text){
  $years=strpos($text,'2020b')!==false?['2020a','2020b']:['2019','2020'];$r=apaResults(referenceDocument([$text],array_map('stage35Ref',$years)));
  same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['2020','2020c'] as $year) {
 test('sufixo não coincide com referências declaradas '.$year,function()use($year){
  $r=apaResults(referenceDocument(['Texto (Silva, '.$year.').'],[stage35Ref('2020a'),stage35Ref('2020b')]));
  same('indeterminado',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
  same(true,in_array('citation_year_suffix_mismatch',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
 });
}
test('sufixo correto não escolhe obra por título',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020b).'],[stage35Ref('2020a'),stage35Ref('2020b')]));same('conforme',$r['references.citation_matching']['status']);same([1],$r['references.apa7']['evidence']['analysis']['citation_samples'][0]['reference_indices']);
});
test('iniciais diferentes impedem atribuição automática de sufixo',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],[stage35Ref('2020a','Silva','A'),stage35Ref('2020b','Silva','B')]));
 same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('autoria declarada diferente impede impor cronologia',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020, 2019).'],[stage35Ref('2019','Silva','B'),stage35Ref('2020','Silva','A')]));
 same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('anos duplicados permanecem para revisão',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020, 2020).'],[stage35Ref('2020')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
foreach ([true,false] as $ordered) {
 test('ordem alfabética de obras distintas '.($ordered?'correta':'invertida'),function()use($ordered){
  $text=$ordered?'Texto (Silva, 2020; Souza, 2019).':'Texto (Souza, 2019; Silva, 2020).';$r=apaResults(referenceDocument([$text],[stage35Ref('2020'),stage35Ref('2019','Souza')]));
  same('conforme',$r['references.citation_matching']['status']);same($ordered?'conforme':'não conforme',$r['references.apa7.citation_format']['status']);
 });
}
foreach (['Texto (Sá, 2020; Lima, 2019).','Texto (da Silva, 2020; Lima, 2019).'] as $text) {
 test('acentos e partículas não recebem ordem ASCII '.md5($text),function()use($text){
  $name=strpos($text,'Sá')!==false?'Sá':'da Silva';$r=apaResults(referenceDocument([$text],[stage35Ref('2020',$name),stage35Ref('2019','Lima')]));same('indeterminado',$r['references.apa7.citation_format']['status']);
 });
}
test('grupo parcialmente analisável não prova inversão',function(){
 $r=apaResults(referenceDocument(['Texto (Souza, 2019; Silva, 2020; veja também outra fonte, 2020).'],[stage35Ref('2020'),stage35Ref('2019','Souza')]));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('referência fora da gramática impede prova de sufixo',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],[stage35Ref('2020a'),stage35Ref('2020b'),'Silva, Alberto. (2020). Título. Editora.']));same('indeterminado',$r['references.apa7.citation_format']['status']);
});
foreach (['Texto (Silva, 2020a,b).','Texto (Silva, 2019–2020).','Texto (Silva, 2019, 2020, p. 10).'] as $text) {
 test('formas de anos não suportadas exigem conferência '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage35Ref('2019'),stage35Ref('2020')]));same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
 });
}
test('obra de um dos anos ausente pode bloquear no subconjunto fechado',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2019, 2020).'],[stage35Ref('2019')]));same('não conforme',$r['references.citation_matching']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('empate de chaves sem sufixo não é resolvido inventando letras',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],[stage35Ref('2020'),stage35Ref('2020')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('apud continua bloqueado em grupo múltiplo',function(){
 $r=apaResults(referenceDocument(['Texto (Lima, 1980, apud Silva, 2020; Souza, 2019).'],[stage35Ref('2020'),stage35Ref('2019','Souza')]));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('regras de conjunção permanecem em listas de anos',function(){
 $refs=['Silva, A., & Souza, B. (2019). Título. Editora.','Silva, A., & Souza, B. (2020). Outro. Editora.'];
 $r=apaResults(referenceDocument(['Silva e Souza (2019, 2020).'],$refs));same('conforme',$r['references.apa7.citation_format']['status']);
 $r=apaResults(referenceDocument(['Texto (Silva e Souza, 2019, 2020).'],$refs));same('não conforme',$r['references.apa7.citation_format']['status']);
});
test('minuta inclui os três novos diagnósticos sem nomes',function(){
 $d=referenceDocument(['Texto (Souza, 2020; Silva, 2021, 2019).','Texto (Lima, 2020c).'],[stage35Ref('2020','Lima'),stage35Ref('2019'),stage35Ref('2021'),stage35Ref('2020','Souza'),stage35Ref('2020a','Lima')]);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);
 same(true,strpos($draft['body'],'alinhar o ano e seu sufixo')!==false);same(true,strpos($draft['body'],'ordenar cronologicamente')!==false);same(true,strpos($draft['body'],'ordenar alfabeticamente as obras')!==false);same(false,strpos($draft['body'],'Souza')!==false);
});
test('DOCX com sufixo divergente bloqueia liberação',function(){
 $d=referenceDocument(['Texto (Silva, 2020).'],[stage35Ref('2020a'),stage35Ref('2020b')]);
 withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});});
});
test('limite de anos não trunca a citação silenciosamente',function(){
 $years=[];$refs=[];for($i=0;$i<21;$i++){$years[]=(string)(2000+$i);$refs[]=stage35Ref((string)(2000+$i));}
 $r=apaResults(referenceDocument(['Texto (Silva, '.implode(', ',$years).').'],$refs));same('indeterminado',$r['references.citation_matching']['status']);
});
test('cauda bibliográfica não é corpo para conferir anos agrupados',function(){
 $tail=referenceDocument([],[stage35Ref('2020a'),stage35Ref('2020b')]);$r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);same('indeterminado',$r['references.apa7.citation_format']['status']);
});

test('sufixo diferente de referência única gera correção específica',function(){
 $r=apaResults(referenceDocument(['Texto (Silva, 2020a).'],[stage35Ref('2020b')]));same('indeterminado',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
foreach (['Texto ((Silva, 2020)).','Texto (Silva, 2020). ('] as $text) {
 test('sintaxe incerta não prova erro de sufixo '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage35Ref('2020a'),stage35Ref('2020b')]));same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}

test('vinte anos explícitos são analisados sem truncamento',function(){
 $years=[];$refs=[];for($i=0;$i<20;$i++){$years[]=(string)(2000+$i);$refs[]=stage35Ref((string)(2000+$i));}
 $r=apaResults(referenceDocument(['Texto (Silva, '.implode(', ',$years).').'],$refs));same(20,$r['references.apa7']['evidence']['analysis']['matched_citations']);same('conforme',$r['references.citation_matching']['status']);
});
test('expansão de anos respeita limite global de citações',function(){
 $years=[];$refs=[];for($i=0;$i<20;$i++){$years[]=(string)(2000+$i);$refs[]=stage35Ref((string)(2000+$i));}
 $texts=array_fill(0,103,'Texto (Silva, '.implode(', ',$years).').');$r=apaResults(referenceDocument($texts,$refs));same(true,$r['references.apa7']['evidence']['analysis']['inspection_limit_reached']);same('indeterminado',$r['references.citation_matching']['status']);
});
foreach (['Resultados (2019, 2020).','Período (2019, 2020).','Ausente (2019, 2020).'] as $text) {
 test('lista narrativa não inventa autor a partir da prosa '.md5($text),function()use($text){
  $r=apaResults(referenceDocument([$text],[stage35Ref('2019'),stage35Ref('2020')]));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('DOCX preserva múltiplos vínculos literais em anos explícitos',function(){
 $d=referenceDocument(['Silva (2019, 2020a, 2020b).'],[stage35Ref('2019'),stage35Ref('2020a'),stage35Ref('2020b')]);
 withDocx(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($path){$r=apaResults((new \Estrabao\Document\DocxReader())->read($path));same('conforme',$r['references.citation_matching']['status']);same(3,$r['references.apa7']['evidence']['analysis']['matched_citations']);});
});

test('falha de sufixo tardia substitui amostra inconclusiva',function(){
 $texts=array_fill(0,40,'Texto (Silva, 2020, 2020).');$texts[]='Texto (Silva, 2020c).';
 $r=apaResults(referenceDocument($texts,[stage35Ref('2020')]));$samples=$r['references.apa7']['evidence']['analysis']['citation_style_samples'];same(32,count($samples));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(true,in_array('citation_year_suffix_mismatch',array_merge(...array_column($samples,'issues')),true));
});

foreach (['2020','2020c'] as $year) {
 test('forma parcial e sufixo não criam ausência comprovada '.$year,function()use($year){
  $r=apaResults(referenceDocument(['Texto (Silva, '.$year.').'],[stage35Ref('2020a','da Silva'),stage35Ref('2020b','da Silva')]));same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
