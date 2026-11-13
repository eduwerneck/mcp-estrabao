<?php
declare(strict_types=1);
use Estrabao\Document\ApaPersonalAuthors;
foreach([
 'Silva, A.; Souza, B.'=>false,
 'Silva, A., Souza, B.'=>false,
 'Silva, A. & Souza, B.'=>false,
 'Silva, A. e Souza, B.'=>false,
 'Silva, A. and Souza, B.'=>false,
 'Silva, A. y Souza, B.'=>false,
 'Silva, A., & Souza, B.'=>true,
 'Silva, A.'=>true,
 'Silva, A., Souza, B., & Lima, C.'=>true,
]as$authors=>$valid){
 test('separadores pessoais da referência '.$authors,function()use($authors,$valid){
  $ref=$authors.' (2020). Estudo da paisagem. Revista Sintética, 8(2), 10–20.';
  $doc=italicDocument([$ref],[['Revista Sintética','8']]);$a=apaAudit($doc);
  same($valid?'conforme':'não conforme',auditCodes($a)['personal_author_separators']);
  same($valid?0:1,$a['mixed_format_entries']);same(0,$a['abnt_like_entries']);
  same($valid?'conforme':'não conforme',$a['recognized_formats_status']);
 });
}
foreach([
 'Revista Sintética, v. 8, n. 2, p. 10–20.',
 'Revista Sintética, v. 8, p. 10–20.',
 'Revista Sintética, v. 8, n. 2, pp. 10–20, 2020.',
]as$source){
 test('periódico com data inicial e rótulos legados '.$source,function()use($source){
  $ref='Silva, A. (2020). Estudo da paisagem. '.$source;
  $r=apaResults(referenceDocument(['Texto (Silva, 2020).'],[$ref]));$a=$r['references.apa7']['evidence']['style_audit'];
  same('não conforme',auditCodes($a)['journal_legacy_labels']);same(1,$a['mixed_format_entries']);same(0,$a['abnt_like_entries']);
  same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach([
 'Silva, A. (2020). Um título sobre v. 8, n. 2, p. 10–20. Editora Exemplo.',
 'Silva, A. (2020). Uso de n. e p. em documentos. Editora Exemplo.',
 'Silva, A. (2020). Trabalho sobre volume 8. Revista Sintética, 8(2), 10–20.',
 'Supremo Tribunal. (2020). Lei n. 8 sobre registros. Diário Oficial, v. 8, n. 2, p. 10–20.',
]as$ref){
 test('não inventa rótulos de periódico a partir de título ou autoria institucional '.md5($ref),function()use($ref){
  same(false,isset(auditCodes(apaAudit(referenceDocument([],[$ref])))['journal_legacy_labels']));
 });
}
foreach([20=>true,21=>false]as$count=>$valid){
 test('limite de lista pessoal não abreviada '.$count,function()use($count,$valid){
  $names=[];for($i=0;$i<$count;$i++){$names[]='Autor'.chr(65+$i).', A.';}
  $last=array_pop($names);$authors=implode(', ',$names).', & '.$last;
  $ref=$authors.' (2020). Estudo da paisagem. Revista Sintética, 8(2), 10–20.';
  $a=apaAudit(italicDocument([$ref],[['Revista Sintética','8']]));
  same($valid?'conforme':'não conforme',auditCodes($a)['personal_author_list_length']);
 });
}
foreach(['World Health Organization.','de Silva, A.; Souza, B.','Silva, A., Jr.; Souza, B.','Silva, A., … Souza, B.','Silva, A.; Texto inesperado; Souza, B.']as$prefix){
 test('autoria fora da gramática não é adivinhada '.md5($prefix),function()use($prefix){same(null,ApaPersonalAuthors::inspect($prefix));});
}
test('formato misto com DOI antigo gera correções fixas sem reproduzir referência',function(){
 $ref='Silva, A.; Souza, B. (2020). Estudo da paisagem. Revista Sintética, v. 8, n. 2, p. 10–20. DOI: 10.1234/synthetic';
 $s=new \Estrabao\Contracts\Submission(fixture());$doc=referenceDocument([],[$ref]);
 $report=\Estrabao\Reporting\Report::generate($s,\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
 $draft=\Estrabao\Reporting\DeskResponse::generate($report);$body=$draft['body'];
 same(true,strpos($body,'sem ponto e vírgula')!==false);same(true,strpos($body,'rótulos v., n. e p./pp.')!==false);same(true,strpos($body,'https://doi.org/')!==false);
 same(false,strpos(json_encode($draft),'Silva'));same(false,strpos(json_encode($draft),'10.1234/synthetic'));
});
test('novas entradas não promovem correspondência desconhecida a ausência de referência',function(){
 $r=apaResults(referenceDocument(['Texto (Silva & Souza, 2020).'],['Silva, A.; Souza, B. (2020). Estudo da paisagem. Revista Sintética, 8(2), 10–20.']));
 same('indeterminado',$r['references.citation_matching']['status']);same('não conforme',$r['references.apa7']['status']);
});
test('cauda parcial pode bloquear formato misto sem aprovar corpo não extraído',function(){
 $doc=referenceDocument([],['Silva, A.; Souza, B. (2020). Estudo da paisagem. Revista Sintética, v. 8, p. 10–20.']);
 $doc=['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$doc];$r=apaResults($doc);
 same('verified_reference_tail',$r['references.apa7']['evidence']['source_profile']);same('não conforme',$r['references.apa7']['status']);same('indeterminado',$r['references.citation_matching']['status']);
});
test('formato misto em DOCX bloqueia wizard e não aceita liberação humana',function(){
 withFootnotePanel(docxParts(docxBody(['Referências','Silva, A.; Souza, B. (2020). Estudo da paisagem. Revista Sintética, v. 8, p. 10–20.'])),function($r,$service,$request){
  same('not_accepted',$service->view($request,17)['analysis']['decision']);same(false,$service->view($request,17)['release_active']);
  reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});
 });
});
