<?php
declare(strict_types=1);
function stage46Quote(int $words=12,string $locator='p. 10',bool $inline=false,string $author='Silva'):string {
    $quoted='“'.implode(' ',array_fill(0,$words,'trecho')).'”';
    return $author.' (2020'.($inline && $locator!==''?', '.$locator:'').') afirma '.$quoted.(!$inline && $locator!==''?' ('.$locator.')':'').'.';
}
foreach ([false,true] as $inline) {
    foreach (['p. 10','pp. 10–12'] as $locator) {
        test('narrativa localiza fonte e candidato '.(int)$inline.' '.$locator,function()use($inline,$locator){
            $r=stage45Results(stage46Quote(12,$locator,$inline));$a=$r['references.apa7']['evidence']['analysis'];
            same('conforme',$r['references.citation_matching']['status']);same(1,$a['recognized_citations']);same(1,$a['direct_quote_counts']['candidate_count']);same(0,$a['direct_quote_counts']['missing_locator_candidates']);same([12,12],$a['citation_style_samples'][0]['word_interval']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
        });
    }
    foreach (['pp. 30–10'=>'citation_page_range_order','p. 10–12'=>'citation_page_marker','pp. 10'=>'citation_page_marker'] as $locator=>$code) {
        test('narrativa bloqueia defeito inequívoco '.(int)$inline.' '.$locator,function()use($inline,$locator,$code){
            $r=stage45Results(stage46Quote(12,$locator,$inline));same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(true,in_array($code,$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
        });
    }
    foreach (['para. 2','parág. 2','01:30','p. 0','pp. 10–10',''] as $locator) {
        test('narrativa com localização revisável '.(int)$inline.' '.$locator,function()use($inline,$locator){
            $r=stage45Results(stage46Quote(12,$locator,$inline));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same($locator===''?1:0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['missing_locator_candidates']);
        });
    }
}
foreach ([39,40,41] as $n) {
    test('extensão da narrativa '.$n,function()use($n){$r=stage45Results(stage46Quote($n));same($n>=40?1:0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['long_candidates']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);});
}
test('narrativa com hífen preserva incerteza de contagem',function(){
    $r=stage45Results('Silva (2020) afirma “'.implode(' ',array_fill(0,38,'trecho')).' duas-palavras” (p. 10).');same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['boundary_candidates']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
foreach (['Silva (2020, pp. 30–10) apresenta um argumento.','Segundo Silva (2020, pp. 30–10) há um argumento.'] as $text) {
    test('localizador junto ao ano não exige presumir transcrição '.md5($text),function()use($text){$r=stage45Results($text);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);});
}
foreach ([
    'Silva (2020) apresenta outra obra e depois “um trecho” (pp. 30–10).',
    'Silva (2020) afirma “um trecho” e outro argumento (pp. 30–10).',
    'Silva (2020) afirma “um “termo” interno” (pp. 30–10).',
    'Silva (2020) afirma “um trecho" (pp. 30–10).',
    'Silva (2020, p. 10) afirma “um trecho” (pp. 30–10).',
    'Silva (2020, 2021, pp. 30–10) afirma “um trecho”.',
    'Silva (2020) afirma “um trecho” (pp. 30–10; p. 11).',
    'Silva (2020) afirma “um trecho” (pp. 30–10). Continuação.',
    'Silva (2020) afirma “um trecho” (pp. 30–10)).'
] as $text) {
    test('não associa localizador narrativo fora do modelo '.md5($text),function()use($text){$r=stage45Results($text);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(0,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);});
}
test('fonte narrativa ambígua não prova erro de página',function(){
    $ref='Silva, A. (2020). Obra sintética. Editora Exemplo.';$r=apaResults(referenceDocument([stage46Quote(12,'pp. 30–10')],[$ref,$ref]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.citation_matching']['status']);
});
test('citação interna narrativa não provoca correção da transcrição',function(){
    $r=stage45Results('“Silva (2020, pp. 30–10) apresenta dados” (Silva, 2020, p. 10).');same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(true,$r['references.apa7']['evidence']['analysis']['unparsed_citation_groups']>0);
});
test('narrativa com citação interna preserva proveniência',function(){
    $r=stage45Results('Silva (2020) afirma “um trecho com (Ausente, 1900, pp. 30–10) reproduzido” (p. 10).');same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['quoted_citation_candidates']);same(false,$r['references.apa7']['evidence']['analysis']['et_al_context_complete']);
});
test('narrativa com dois autores conserva conjunção própria',function(){
    $r=apaResults(referenceDocument([stage46Quote(12,'p. 10',false,'Silva e Souza')],['Silva, A., & Souza, B. (2020). Obra sintética. Editora Exemplo.']));same('conforme',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(1,$r['references.apa7']['evidence']['analysis']['direct_quote_counts']['candidate_count']);
});
test('guia e minuta narrativa preservam dados privados',function(){
    $d=referenceDocument([stage46Quote(12,'pp. 30–10')],['Silva, A. (2020). Obra sintética. Editora Exemplo.']);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'conferir o intervalo de páginas da citação')!==false);foreach(['trecho trecho','Silva','30–10']as$value){same(false,strpos($draft['body'],$value)!==false);}
});
test('painel privado recebe orientação de narrativa longa',function(){
    $d=referenceDocument([stage46Quote(40)],['Silva, A. (2020). Obra sintética. Editora Exemplo.']);withFootnotePanel(docxParts(docxBody(array_column($d['paragraphs'],'text'))),function($r,$service,$request){$v=$service->view($request,17);same(true,strpos(implode(' ',$v['analysis']['direct_quote_review']),'bloco')!==false);same(false,strpos(json_encode($v['analysis']['direct_quote_review']),'trecho trecho')!==false);same('manual_review_required',$v['analysis']['decision']);});
});
