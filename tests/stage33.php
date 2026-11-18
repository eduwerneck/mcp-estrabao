<?php
declare(strict_types=1);
function stage33Chapter(string $editors='B. Editor, C. Outro, & D. Terceiro',string $label='Eds.',string $pages='10–20',string $edition=''):string {
 return 'Silva, A. (2020). Capítulo sintético. In '.$editors.' ('.$label.'), Livro sintético ('.$edition.'pp. '.$pages.'). Editora Exemplo.';
}
foreach (['B. Editor, C. Outro, & D. Terceiro','B. van der Meer, C. García Márquez, & D. de Souza'] as $editors) {
 test('capítulo com três editores reconhecidos '.md5($editors),function()use($editors){
  $a=apaAudit(italicDocument([stage33Chapter($editors)],[['Livro sintético']]));
  same('chapter',$a['samples'][0]['type']);same('conforme',$a['recognized_formats_status']);same('conforme',auditCodes($a)['chapter_editor_separators']);
 });
}
foreach (['B. Editor; C. Outro; D. Terceiro','B. Editor, C. Outro e D. Terceiro','B. Editor, C. Outro & D. Terceiro'] as $editors) {
 test('separador de editoria no modelo de capítulo '.md5($editors),function()use($editors){
  $a=apaAudit(italicDocument([stage33Chapter($editors)],[['Livro sintético']]));same('não conforme',auditCodes($a)['chapter_editor_separators']);
 });
}
foreach ([['B. Editor','Ed.',true],['B. Editor','Eds.',false],['B. Editor & C. Outro','Eds.',true],['B. Editor, & C. Outro','Eds.',false]] as $case) {
 test('rótulo e separador dependem da contagem real reconhecida '.md5(json_encode($case)),function()use($case){
  $a=apaAudit(italicDocument([stage33Chapter($case[0],$case[1])],[['Livro sintético']]));same($case[2]?'conforme':'não conforme',$a['recognized_formats_status']);
 });
}
foreach (['B. Editor','Eds.','pp. 10–20','2nd ed.'] as $fragment) {
 test('metadado de capítulo não recebe itálico '.$fragment,function()use($fragment){
  $a=apaAudit(italicDocument([stage33Chapter('B. Editor, C. Outro, & D. Terceiro','Eds.','10–20','2nd ed., ')],[['Livro sintético',$fragment]]));
  $codes=['B. Editor'=>'chapter_editors_plain','Eds.'=>'chapter_editor_label_plain','pp. 10–20'=>'chapter_pages_plain','2nd ed.'=>'chapter_edition_plain'];
  same('não conforme',auditCodes($a)[$codes[$fragment]]);
 });
}
foreach (['0th','2nd','3rd','11th','12th','13th','21st','22nd','103rd'] as $edition) {
 test('edição de obra autônoma no modelo reconhecido '.$edition,function()use($edition){
  $r=apaResults(italicDocument(['Silva, A. (2020). Livro sintético ('.$edition.' ed.). Editora Exemplo.'],[['Livro sintético']],[]));
  $a=$r['references.apa7']['evidence']['style_audit'];same('standalone_edition_candidate',$a['samples'][0]['type']);same('conforme',$a['recognized_formats_status']);
  same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['2st','3th','11st'] as $edition) {
 test('ordinal inglês reconhecido incorreto '.$edition,function()use($edition){
  $a=apaAudit(italicDocument(['Silva, A. (2020). Livro sintético ('.$edition.' ed.). Editora Exemplo.'],[['Livro sintético']]));same('não conforme',auditCodes($a)['edition_ordinal']);
 });
}
test('itálico na edição da obra autônoma gera falha concreta',function(){
 $a=apaAudit(italicDocument(['Silva, A. (2020). Livro sintético (2nd ed.). Editora Exemplo.'],[['Livro sintético','2nd ed.']]));same('não conforme',auditCodes($a)['standalone_edition_plain']);
});
foreach (['2nd ed., ','2st ed., '] as $edition) {
 test('edição junto às páginas do capítulo '.$edition,function()use($edition){
  $a=apaAudit(italicDocument([stage33Chapter('B. Editor','Ed.','10–20',$edition)],[['Livro sintético']]));same($edition==='2nd ed., '?'conforme':'não conforme',auditCodes($a)['edition_ordinal']);
 });
}
foreach (['10–20'=>true,'20–10'=>false,'10-20'=>true,'10–10'=>true,'999999999–1'=>false] as $pages=>$valid) {
 foreach (['article','chapter'] as $kind) {
  test('ordem das páginas '.$kind.$pages,function()use($pages,$valid,$kind){
   $ref=$kind==='chapter'?stage33Chapter('B. Editor','Ed.',$pages):'Silva, A. (2020). Artigo sintético. Revista Sintética, 8(2), '.$pages.'.';
   $a=apaAudit(italicDocument([$ref],[$kind==='chapter'?['Livro sintético']:['Revista Sintética','8']]));same($valid?'conforme':'não conforme',auditCodes($a)[$kind==='chapter'?'chapter_page_range_order':'article_page_range_order']);
  });
 }
}
foreach (['Silva, A. (2020). Título (2nd ed.). Fonte Exemplo.','Silva, A. (2020). Livro sintético (2a ed.). Editora Exemplo.','Silva, A. (2020). Capítulo. In B. Editor (Org.), Livro (pp. 10–20). Editora.'] as $ref) {
 test('modelo não confirmado não vira livro com infração '.md5($ref),function()use($ref){
  $a=apaAudit(referenceDocument([],[$ref]));same('indeterminado',$a['recognized_formats_status']);same(false,isset(auditCodes($a)['standalone_edition_plain']));
 });
}
foreach (['Article e12345','e12345','10'] as $locator) {
 test('identificador e página única não recebem ordem inventada '.$locator,function()use($locator){
  $a=apaAudit(italicDocument(['Silva, A. (2020). Artigo. Revista Sintética, 8(2), '.$locator.'.'],[['Revista Sintética','8']]));same(false,isset(auditCodes($a)['article_page_range_order']));
 });
}
test('tipo e formatação ausentes continuam inconclusivos',function(){
 $a=apaAudit(referenceDocument([],[stage33Chapter()]));same('indeterminado',auditCodes($a)['chapter_editors_plain']);same('indeterminado',$a['recognized_formats_status']);
});
test('cauda bibliográfica pode bloquear páginas sem aprovar corpo',function(){
 $tail=referenceDocument([],[stage33Chapter('B. Editor','Ed.','20–10')]);$r=apaResults(['available'=>false,'sha256'=>str_repeat('a',64),'reference_tail'=>$tail]);
 same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.citation_matching']['status']);
});
test('minuta mapeia novos erros sem copiar nomes ou título',function(){
 $doc=referenceDocument([],[stage33Chapter('B. Editor; C. Outro; D. Terceiro','Ed.','20–10','2st ed., ')]);
 $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($doc,'article','pt'));
 $draft=\Estrabao\Reporting\DeskResponse::generate($report);$body=$draft['body'];
 same(true,strpos($body,'sufixo ordinal')!==false);same(true,strpos($body,'separadores dos editores')!==false);same(true,strpos($body,'página final não pode anteceder')!==false);same(false,strpos($body,'Livro sintético')!==false);same(false,strpos($body,'B. Editor')!==false);
});
test('DOCX com páginas invertidas bloqueia liberação',function(){
 withFootnotePanel(docxParts(docxBody(['Referências',stage33Chapter('B. Editor','Ed.','20–10')])),function($r,$service,$request){
  same('not_accepted',$service->view($request,17)['analysis']['decision']);reviewPost($service,$request);reviewRejects(function()use($service,$request){$service->save($request,17);});
 });
});
foreach ([20=>true,21=>false] as $count=>$recognized) {
 test('limite de editores do modelo de capítulo '.$count,function()use($count,$recognized){
  $names=[];for($i=0;$i<$count;$i++){$names[]='A. Editor'.chr(65+$i);}$last=array_pop($names);$editors=implode(', ',$names).', & '.$last;
  $a=apaAudit(italicDocument([stage33Chapter($editors)],[['Livro sintético']]));
  same($recognized?'chapter':'unresolved',$a['samples'][0]['type']);same($recognized?'conforme':'indeterminado',$a['recognized_formats_status']);
 });
}
foreach ([str_repeat('A',2049),'A. Editor; texto inesperado; B. Outro',"A. Edite\u{0301}r"] as $text) {
 test('editoria fora dos limites não é adivinhada '.md5($text),function()use($text){same(null,\Estrabao\Document\ReferenceModels::editors($text));});
}
