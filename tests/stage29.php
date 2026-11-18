<?php
declare(strict_types=1);
use Estrabao\Document\ApaPersonalAuthors;
foreach (['de Silva','da Costa','dos Santos','García Márquez','Castelo Branco','van der Meer','De Souza','D’Ávila','Smith-Jones'] as $surname) {
    foreach ([', & '=>true, '; '=>false] as $separator=>$valid) {
        test('autoria composta com separador verificável '.$surname.$separator, function () use ($surname,$separator,$valid) {
            $authors=$surname.', A.'.$separator.'Souza, B.';
            $parsed=ApaPersonalAuthors::inspect($authors);
            same([$surname,'Souza'],$parsed['authors']); same($valid,$parsed['separators_valid']);
            $ref=$authors.' (2020). Estudo da paisagem. Revista Sintética, 8(2), 10–20.';
            $r=apaResults(italicDocument([$ref],[['Revista Sintética','8']],[]));
            same($valid?'conforme':'não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['personal_author_separators']);
            same($valid?'manual_review_required':'not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
            same(false,$r['references.apa7']['evidence']['admission_policy']['automatic_progression_allowed']);
        });
    }
}
function stage29Authors(int $count): array {
    $names=[]; for($i=0;$i<$count;$i++) {$names[]='Autor'.chr(65+intdiv($i,26)).chr(65+$i%26).', A.';}
    return $names;
}
foreach ([2,19,20,21] as $count) {
    foreach (['…','...','. . .'] as $ellipsis) {
        foreach ([false,true] as $ampersand) {
            test('estrutura de autoria abreviada '.$count.$ellipsis.($ampersand?' &':''),function()use($count,$ellipsis,$ampersand){
                $names=stage29Authors($count); $last=array_pop($names);
                $prefix=implode(', ',$names).', '.$ellipsis.' '.($ampersand?'& ':'').$last;
                $p=ApaPersonalAuthors::inspect($prefix);same(true,$p['abbreviated']);
                $valid=$count===20&&!$ampersand; same($valid,$p['abbreviation_structure_valid']);
                $r=apaResults(referenceDocument([],[$prefix.' (2020). Título sintético. Editora Exemplo.']));
                $a=$r['references.apa7']['evidence']['style_audit'];
                same($valid?'conforme':'não conforme',auditCodes($a)['personal_author_abbreviation_structure']);
                same('indeterminado',auditCodes($a)['personal_author_total_unverified']);
                same($valid?'manual_review_required':'not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
                same(false,isset(auditCodes($a)['personal_author_separators']));
            });
        }
    }
}
foreach (['World Health Organization.','Universidade de São Paulo.','de Silva, A., Jr.; Souza, B.','Silva, A.; texto desconhecido; Souza, B.','Silva, A., et al.','Silva, A., … … Souza, B.',str_repeat('A',2049),"Silva\u{0301}, A.","Silva, A.\xFF"] as $prefix) {
    test('não infere autoria ou lista ambígua '.md5($prefix),function()use($prefix){same(null,ApaPersonalAuthors::inspect($prefix));});
}
foreach ([20=>true,21=>false,64=>false] as $count=>$valid) {
    test('limite não abreviado preservado com nomes compostos '.$count,function()use($count,$valid){
        $names=stage29Authors($count);$names[0]='de Silva, A.';$last=array_pop($names);
        $p=ApaPersonalAuthors::inspect(implode(', ',$names).', & '.$last);
        same($valid,$p['unabridged_list_length_valid']);same(false,$p['abbreviated']);
    });
}
test('limite de 64 nomes não é ultrapassado',function(){
    $names=stage29Authors(65);$last=array_pop($names);same(null,ApaPersonalAuthors::inspect(implode(', ',$names).', & '.$last));
});
test('ampliação da autoria não inventa correspondência autor-ano',function(){
    $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],['de Silva, A.; Souza, B. (2020). Título sintético. Editora Exemplo.']));
    same('indeterminado',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7']['status']);
});
test('minuta informa lista abreviada sem copiar nomes nem adivinhar total',function(){
    $doc=referenceDocument([],['de Silva, A., … Souza, B. (2020). Título privado sintético. Editora Exemplo.']);
    $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
    $draft=\Estrabao\Reporting\DeskResponse::generate($report);
    same(true,strpos($draft['body'],'19 primeiros autores, reticências e o último, sem &')!==false);
    same(false,strpos(json_encode($draft),'Silva')!==false);same(false,strpos(json_encode($draft),'Título privado')!==false);
});
foreach (['de Silva, A.; Souza, B.','de Silva, A., … Souza, B.'] as $authors) {
    test('DOCX com autoria incompatível bloqueia wizard e liberação '.md5($authors),function()use($authors){
        withFootnotePanel(docxParts(docxBody(['Referências',$authors.' (2020). Título sintético. Editora Exemplo.'])),function($r,$service,$request){
            same('not_accepted',$service->view($request,17)['analysis']['decision']);same(false,$service->view($request,17)['release_active']);
            reviewPost($service,$request); reviewRejects(function()use($service,$request){$service->save($request,17);});
        });
    });
}
test('cauda bibliográfica com autoria composta bloqueia sem aprovar corpo ausente',function(){
    $tail=referenceDocument([],['van der Meer, A.; Souza, B. (2020). Título sintético. Editora Exemplo.']);
    $r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);
    same('verified_reference_tail',$r['references.apa7']['evidence']['source_profile']);same('não conforme',$r['references.apa7']['status']);same('indeterminado',$r['references.citation_matching']['status']);
});

foreach (['de Silva','García Márquez','van der Meer'] as $surname) {
    foreach (['João','A.'] as $given) {
        test('ABNT com sobrenome composto e ano após editora '.$surname.$given,function()use($surname,$given){
            $r=apaResults(referenceDocument([],[$surname.', '.$given.'. Estudo sintético. São Paulo: Editora Exemplo, 2020.']));
            same(1,$r['references.apa7']['evidence']['style_audit']['abnt_like_entries']);
            same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
        });
    }
}
