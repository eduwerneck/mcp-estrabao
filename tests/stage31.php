<?php
declare(strict_types=1);
function stage31Ref(string $name='Instituto de Estudos Sintéticos',string $year='2020'):string{return $name.'. ('.$year.'). Título sintético. Editora Exemplo.';}
foreach (['Texto (Instituto de Estudos Sintéticos, 2020).','Instituto de Estudos Sintéticos (2020).','Segundo Instituto de Estudos Sintéticos (2020).'] as $text) {
    test('vínculo institucional literal '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage31Ref()]));same('conforme',$r['references.citation_matching']['status']);
        same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
}
foreach ([
 ['Texto (Instituto de Estudos Sintéticos [IES], 2020).','Texto (IES, 2020).'],
 ['Instituto de Estudos Sintéticos (IES, 2020).','IES (2020).'],
 ['Texto (IES, 2020).','Texto (Instituto de Estudos Sintéticos [IES], 2020).'],
] as $texts) {
    test('sigla declarada vincula mas não certifica primeira apresentação '.md5(implode('',$texts)),function()use($texts){
        $r=apaResults(referenceDocument($texts,[stage31Ref()]));$a=$r['references.apa7']['evidence']['analysis'];
        same(2,$a['matched_citations']);same('conforme',$r['references.citation_matching']['status']);
        same('indeterminado',$r['references.apa7.citation_format']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
}
foreach (['Texto (IES, 2020).','IES (2020).','Texto (Outra Instituição, 2020).','Texto (Silva, 2020).'] as $text) {
    test('não deriva sigla nem prova ausência com instituição fora de vínculo '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage31Ref()]));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
}
test('mesma sigla para duas instituições permanece ambígua',function(){
    $texts=['Texto (Instituto de Estudos Sintéticos [IES], 2020).','Texto (Instituto de Estudos Sociais [IES], 2020).','Texto (IES, 2020).'];
    $r=apaResults(referenceDocument($texts,[stage31Ref(),stage31Ref('Instituto de Estudos Sociais')]));
    same('indeterminado',$r['references.citation_matching']['status']);same(1,$r['references.apa7']['evidence']['analysis']['ambiguous_matches']);
});
test('sigla que também é sobrenome não identifica autor por suposição',function(){
    $r=apaResults(referenceDocument(['Texto (Instituto de Estudos Sintéticos [IES], 2020).','Texto (IES, 2020).'],[stage31Ref(),'IES, A. (2020). Título. Editora.']));
    same('indeterminado',$r['references.citation_matching']['status']);
});
foreach (['Universidade Sintética','Instituto Sintético','Organização Sintética','World Health Organization'] as $name) {
    test('ABNT institucional composta bloqueia '.$name,function()use($name){
        $r=apaResults(referenceDocument([],[$name.'. Estudo sintético. São Paulo: Editora Exemplo, 2020.']));
        same(1,$r['references.apa7']['evidence']['style_audit']['abnt_like_entries']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
    test('instituição seguida de data não vira ABNT '.$name,function()use($name){
        $r=apaResults(referenceDocument(['Texto ('.$name.', 2020).'],[stage31Ref($name)]));
        same(0,$r['references.apa7']['evidence']['style_audit']['abnt_like_entries']);same('conforme',$r['references.citation_matching']['status']);
    });
}
foreach (['Organização Sintética. Estudo sobre São Paulo. Editora, 2020.','Constituição Federal. Estudo. São Paulo: Editora Exemplo, 2020.','Universidade. Estudo. São Paulo: Editora, 2020.','Organização Sintética. (2020). Estudo com v. 8 e p. 10–20. Editora.'] as $ref) {
    test('não reprova por instituição ou cidade isoladas '.md5($ref),function()use($ref){
        $r=apaResults(referenceDocument([],[$ref]));same(0,$r['references.apa7']['evidence']['style_audit']['abnt_like_entries']);
    });
}
test('instituições com mesma chave e ano não viram correspondência única',function(){
    $r=apaResults(referenceDocument(['Texto (Instituto de Estudos Sintéticos, 2020).'],[stage31Ref(),stage31Ref()]));
    same('indeterminado',$r['references.citation_matching']['status']);
});
test('ano da instituição não é corrigido por semelhança de nome',function(){
    $r=apaResults(referenceDocument(['Texto (Instituto de Estudos Sintéticos, 2021).'],[stage31Ref()]));
    same('indeterminado',$r['references.citation_matching']['status']);
});
test('minuta institucional usa orientação fixa sem copiar instituição',function(){
    $doc=referenceDocument([],['Instituto de Estudos Sintéticos. Estudo. São Paulo: Editora Exemplo, 2020.']);
    $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
    $draft=\Estrabao\Reporting\DeskResponse::generate($report);
    same(true,strpos($draft['body'],'posicionar a data após a autoria')!==false);same(false,strpos(json_encode($draft),'Instituto de Estudos Sintéticos')!==false);
});
test('DOCX com ABNT institucional bloqueia liberação',function(){
    withFootnotePanel(docxParts(docxBody(['Referências','Instituto de Estudos Sintéticos. Estudo. São Paulo: Editora Exemplo, 2020.'])),function($r,$service,$request){
        same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});
    });
});
test('cauda bibliográfica institucional não aprova corpo ausente',function(){
    $tail=referenceDocument([],['Instituto de Estudos Sintéticos. Estudo. São Paulo: Editora Exemplo, 2020.']);
    $r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);
    same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.citation_matching']['status']);
});

foreach (['Instituto Sintético. (2020). Livro. Editora. Souza, B. (2021). Outro. Editora.',str_repeat('A',8193),"Instituto Sinte\u{0301}tico. (2020). Título."] as $text) {
    test('referência institucional fora dos limites não é presumida '.md5($text),function()use($text){
        same(null,\Estrabao\Document\InstitutionalAuthors::entry($text));
    });
}

test('não extrai sigla de instituição que apenas contém o nome de outra',function(){
    $r=apaResults(referenceDocument(['Texto (Universidade do Instituto de Estudos Sintéticos [IES], 2020).','Texto (IES, 2020).'],[stage31Ref()]));
    same('indeterminado',$r['references.citation_matching']['status']);same(0,$r['references.apa7']['evidence']['analysis']['matched_citations']);
});
