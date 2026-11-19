<?php
declare(strict_types=1);
function stage45Quote(int $words=12,string $locator='p. 10',string $open='“',string $close='”'):string {
 return $open.implode(' ',array_fill(0,$words,'palavra')).$close.' (Silva, 2020'.($locator!==''?', '.$locator:'').').';
}
function stage45Results(string $text):array {return apaResults(referenceDocument([$text],['Silva, A. (2020). Obra sintética. Editora Exemplo.']));}
foreach (['p. 10','pp. 10–12','pp. 10-12'] as $locator) {
 test('citação aspada com localizador reconhecido '.md5($locator),function()use($locator){$r=stage45Results(stage45Quote(12,$locator));same('conforme',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);});
}
foreach (['pp. 30–10'=>'citation_page_range_order','p. 10–12'=>'citation_page_marker','pp. 10'=>'citation_page_marker'] as $locator=>$code) {
 test('incompatibilidade de localizador comprovada '.md5($locator),function()use($locator,$code){$r=stage45Results(stage45Quote(12,$locator));same('conforme',$r['references.citation_matching']['status']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(true,in_array($code,$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));});
}
foreach (['para. 2','parág. 2','01:30','1:02:03','p. 0','pp. 10–10'] as $locator) {
 test('localizador alternativo ou ambíguo exige revisão '.md5($locator),function()use($locator){$r=stage45Results(stage45Quote(12,$locator));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.citation_format']['status']);});
}
foreach ([39,40,41] as $words) {
 test('fronteira de aspas e bloco '.$words,function()use($words){$r=stage45Results(stage45Quote($words));$a=$r['references.apa7']['evidence']['analysis'];same($words>=40?1:0,$a['direct_quote_counts']['long_candidates']);same(0,$a['direct_quote_counts']['boundary_candidates']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same([$words,$words],$a['citation_style_samples'][0]['word_interval']);});
}
test('hífen no limite não decide bloco automaticamente',function(){
 $text='“'.implode(' ',array_fill(0,38,'palavra')).' duas-palavras” (Silva, 2020, p. 10).';$r=stage45Results($text);$a=$r['references.apa7']['evidence']['analysis'];same(0,$a['direct_quote_counts']['long_candidates']);same(1,$a['direct_quote_counts']['boundary_candidates']);same([39,40],$a['citation_style_samples'][0]['word_interval']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('ausência de p não comprova falta de localizador na fonte',function(){
 $r=stage45Results(stage45Quote(12,''));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['missing_locator_candidates']);
});
test('aspas ASCII reconhecidas sem copiar texto',function(){
 $r=stage45Results(stage45Quote(12,'p. 10','"','"'));same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);same(false,strpos(json_encode($r),'palavra palavra')!==false);
});
foreach (['Texto “um termo” indica conceito (Silva, 2020, p. 10).','“um “termo” interno” (Silva, 2020, p. 10).','“um trecho" (Silva, 2020, p. 10).'] as $text) {
 test('modelo fora do escopo não é classificado como citação completa '.md5($text),function()use($text){$r=stage45Results($text);same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);});
}
test('parágrafo longo sem aspas não é presumido bloco',function(){
 $r=stage45Results(implode(' ',array_fill(0,80,'palavra')).' (Silva, 2020, p. 10).');same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('grupo incompleto não prova defeito do localizador',function(){
 $r=stage45Results('“um trecho” (Silva, 2020, pp. 30–10; item incompleto).');same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);
});
test('fonte ambígua não sustenta reprovação do localizador',function(){
 $ref='Silva, A. (2020). Obra sintética. Editora Exemplo.';$r=apaResults(referenceDocument([stage45Quote(12,'pp. 30–10')],[$ref,$ref]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.citation_matching']['status']);
});
test('marcador de página independe de presumir transcrição',function(){
 $r=stage45Results('Texto (Silva, 2020, p. 10–12).');same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);
});
test('contagem e orientações não guardam o trecho citado',function(){
 $r=stage45Results(stage45Quote(40));same(false,strpos(json_encode($r['references.apa7']['evidence']['analysis']),'palavra palavra')!==false);same(true,strpos(implode(' ',$r['references.apa7']['evidence']['analysis']['direct_quote_review']),'bloco')!==false);
});
test('minuta de localizador corrige sem página ou transcrição',function(){
 $d=referenceDocument([stage45Quote(12,'pp. 30–10')],['Silva, A. (2020). Obra sintética. Editora Exemplo.']);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'conferir o intervalo de páginas da citação')!==false);foreach(['palavra palavra','Silva','30–10']as$value){same(false,strpos($draft['body'],$value)!==false);}
});
test('painel privado recebe orientação de candidato a bloco sem transcrição',function(){
 $d=referenceDocument([stage45Quote(40)],['Silva, A. (2020). Obra sintética. Editora Exemplo.']);withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){$v=$service->view($request,17);same(true,strpos(implode(' ',$v['analysis']['direct_quote_review']),'bloco')!==false);same(false,strpos(json_encode($v['analysis']['direct_quote_review']),'palavra palavra')!==false);same('manual_review_required',$v['analysis']['decision']);});
});

test('referência interna da transcrição não é presumida ausente',function(){
 $r=stage45Results('“um trecho com (Original, 1900) reproduzido da fonte” (Silva, 2020, p. 10).');same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.citation_matching']['status']);same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['quoted_citation_candidates']);
});
test('intervalo reproduzido dentro da transcrição não é corrigido automaticamente',function(){
 $r=stage45Results('“um trecho com (Silva, 2020, pp. 30–10) reproduzido” (Silva, 2020, p. 10).');same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('conjunção reproduzida dentro da transcrição permanece para revisão',function(){
 $ref='Silva, A., & Souza, B. (2020). Obra sintética. Editora Exemplo.';$r=apaResults(referenceDocument(['“um trecho com (Silva e Souza, 2020, p. 10) reproduzido” (Silva & Souza, 2020, p. 10).'],[$ref]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});

test('obra interna da transcrição impede prova de contexto et al completo',function(){
 $refs=[stage36Ref(['Silva','Souza','Lima','Ramos']),stage36Ref(['Silva','Costa','Alves','Paiva'])];$r=apaResults(referenceDocument(['Texto (Silva et al., 2020).','“um trecho com (Original, 1900) reproduzido” (Silva et al., 2020, p. 10).'],$refs));same(false,$r['references.apa7']['evidence']['analysis']['et_al_context_complete']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
