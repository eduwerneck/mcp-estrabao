<?php
declare(strict_types=1);
function stage30Reference(string $last='Lima', string $year='2020'): string {
    return 'Silva, A., Souza, B., & '.$last.', C. ('.$year.'). Título sintético. Editora Exemplo.';
}
foreach ([
    'Texto (Silva, Souza, & Lima, 2020).',
    'Texto (Silva, Souza e Lima, 2020).',
    'Texto (Silva, Souza, Lima, 2020).',
    'Silva, Souza e Lima (2020).',
    'Segundo Silva, Souza e Lima (2020).',
    'Silva, Souza, & Lima (2020).',
    'Texto (Silva, Souza, et al., 2020).',
    'Silva, Souza, et al. (2020).',
] as $text) {
    test('três autores sem ambiguidade exigem et al '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference()]));
        $a=$r['references.apa7']['evidence']['analysis'];
        same(1,$a['recognized_citations']); same(1,$a['matched_citations']);
        same('conforme',$r['references.citation_matching']['status']);
        same('não conforme',$r['references.apa7.citation_format']['status']);
        same(true,in_array('three_or_more_authors_require_et_al',$a['citation_style_samples'][0]['issues'],true));
        same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
}
foreach (['Texto (Silva et al., 2020).','Silva et al. (2020).','Segundo Silva et al. (2020).'] as $text) {
    test('et al correto continua sem liberação automática '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference()]));
        same('conforme',$r['references.apa7.citation_format']['status']);
        same('conforme',$r['references.citation_matching']['status']);
        same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
    });
}
foreach (['Texto (Silva et al, 2020).','Silva et al (2020).','Texto (Silva, Souza, et al, 2020).'] as $text) {
    test('ponto de et al é verificável quando a obra está ligada '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference()]));
        same('não conforme',$r['references.apa7.citation_format']['status']);
        same(true,in_array('et_al_period',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
    });
}
foreach (['Texto (Silva, Souza, & Lima, 2020).','Silva, Souza e Lima (2020).','Texto (Silva, Souza, et al., 2020).','Texto (Silva et al., 2020).'] as $text) {
    test('coorte ambígua confere adequação dos nomes adicionais '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference(),stage30Reference('Costa')]));
        same(strpos($text,'et al.')!==false?'não conforme':'conforme',$r['references.apa7.citation_format']['status']);
        same(strpos($text,'et al.')!==false?'not_accepted':'manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
        same(false,in_array('three_or_more_authors_require_et_al',array_merge(...array_column($r['references.apa7']['evidence']['analysis']['citation_style_samples'],'issues')),true));
    });
}
foreach (['2020a','2021'] as $year) {
    test('anos ou sufixos distintos não criam exceção indevida '.$year,function()use($year){
        $r=apaResults(referenceDocument(['Texto (Silva, Souza, & Lima, 2020).'],[stage30Reference(),stage30Reference('Costa',$year)]));
        same('não conforme',$r['references.apa7.citation_format']['status']);
    });
}
foreach (['Referência não reconhecida.','Silva, A., Jr. (2020). Título. Editora.'] as $unknown) {
    test('referência não analisável impede descartar desambiguação '.md5($unknown),function()use($unknown){
        $r=apaResults(referenceDocument(['Texto (Silva, Souza, & Lima, 2020).'],[stage30Reference(),$unknown]));
        same('indeterminado',$r['references.apa7.citation_format']['status']);
        same(false,in_array('three_or_more_authors_require_et_al',$r['references.apa7']['evidence']['analysis']['citation_style_samples'][0]['issues'],true));
    });
}
foreach (['Texto (Silva, Costa, & Lima, 2020).','Texto (Silva, Souza, Costa, et al., 2020).'] as $text) {
    test('lista diferente não vira incompatibilidade de abreviação '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference()]));
        same('indeterminado',$r['references.apa7.citation_format']['status']);
        same('indeterminado',$r['references.citation_matching']['status']);
    });
}
foreach (['Texto (X, Souza, & Lima, 2020).','Texto (Silva, Souza apud Lima, 2020).','Texto (de Silva, Souza e Lima, 2020).','Texto (Silva et. al., 2020).'] as $text) {
    test('fora da gramática não gera falta inventada '.md5($text),function()use($text){
        $r=apaResults(referenceDocument([$text],[stage30Reference()]));
        same('indeterminado',$r['references.citation_matching']['status']);
        same('indeterminado',$r['references.apa7.citation_format']['status']);
    });
}
test('minuta dá correção de citação sem copiar texto privado',function(){
    $doc=referenceDocument(['Texto privado (Silva, Souza, & Lima, 2020).'],[stage30Reference()]);
    $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
    $draft=\Estrabao\Reporting\DeskResponse::generate($report);
    same(true,strpos($draft['body'],'primeiro sobrenome seguido de et al.')!==false);
    same(false,strpos(json_encode($draft),'Texto privado')!==false);same(false,strpos(json_encode($draft),'Silva')!==false);
});
foreach (['Texto (Silva, Souza, & Lima, 2020).','Texto (Silva et al, 2020).'] as $text) {
    test('DOCX incompatível por citação bloqueia revisão '.md5($text),function()use($text){
        withFootnotePanel(docxParts(docxBody(array_column(referenceDocument([$text],[stage30Reference()])['paragraphs'],'text'))),function($r,$service,$request){
            same('not_accepted',$service->view($request,17)['analysis']['decision']);
            reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});
        });
    });
}
test('cauda parcial não inventa análise de citação do corpo',function(){
    $tail=referenceDocument([], [stage30Reference()]);$r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);
    same('indeterminado',$r['references.apa7.citation_format']['status']);
    same('indeterminado',$r['references.citation_matching']['status']);
});
foreach ([4,20] as $count) {
    test('lista de citação com '.$count.' autores reconhecidos deve abreviar',function()use($count){
        $names=[];$authors=[];
        for($i=0;$i<$count;$i++){$names[]='Autor'.chr(65+$i);$authors[]=$names[$i].', A.';}
        $last=array_pop($authors);$reference=implode(', ',$authors).', & '.$last.' (2020). Título. Editora.';
        $last=array_pop($names);$citation='Texto ('.implode(', ',$names).', & '.$last.', 2020).';
        $r=apaResults(referenceDocument([$citation],[$reference]));
        same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);
    });
}
test('citação extensa fica fora da gramática sem referência ausente inventada',function(){
    $text='Texto ('.str_repeat('Silva, ',350).'Lima, 2020).';
    $r=apaResults(referenceDocument([$text],[stage30Reference()]));
    same('indeterminado',$r['references.citation_matching']['status']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('localizador preservado no reconhecimento de três autores',function(){
    $r=apaResults(referenceDocument(['Texto (Silva, Souza, & Lima, 2020, pp. 10–12).'],[stage30Reference()]));
    same('conforme',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7.citation_format']['status']);
});
