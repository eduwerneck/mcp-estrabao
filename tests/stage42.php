<?php
declare(strict_types=1);
function stage42Ref(string $translators='A. Tradutor',string $role='Trans.',string $edition='2nd ed.',string $original='1900',string $publication='2020'):string {
 return 'Silva, A. ('.$publication.'). Obra traduzida ('.$translators.', '.$role.($edition!==''?'; '.$edition:'').'). Editora Exemplo.'.($original!==''?' (Original work published '.$original.')':'');
}
foreach (['A. Tradutor','A. van der Meer','A. Tradutor & B. Outro','A. Tradutor, B. Outro, & C. Terceiro'] as $names) {
 test('tradução declarada com nomes reconhecidos '.md5($names),function()use($names){
  $r=apaResults(italicDocument([stage42Ref($names)],[['Obra traduzida']]));$a=$r['references.apa7']['evidence']['style_audit'];same('translated_work_layout_candidate',$a['samples'][0]['type']);same('conforme',auditCodes($a)['translated_work_translator_plain']);same('conforme',auditCodes($a)['translated_work_translator_separators']);same('conforme',auditCodes($a)['translated_work_edition_plain']);same('conforme',auditCodes($a)['original_publication_plain']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Trad.','Trads.'] as $role) {
 test('função portuguesa não impõe separadores do modelo inglês '.$role,function()use($role){
  $r=apaResults(italicDocument([stage42Ref('A. Tradutor e B. Outro',$role)],[['Obra traduzida']]));same('translated_work_layout_candidate',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same(false,isset(auditCodes($r['references.apa7']['evidence']['style_audit'])['translated_work_translator_separators']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['A. Tradutor; B. Outro','A. Tradutor e B. Outro','A. Tradutor, B. Outro & C. Terceiro'] as $names) {
 test('separadores do modelo inglês de tradução '.md5($names),function()use($names){
  $r=apaResults(italicDocument([stage42Ref($names)],[['Obra traduzida']]));same('não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['translated_work_translator_separators']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['A. Tradutor','Trans.','2nd ed.','(Original work published 1900)'] as $fragment) {
 test('metadados de tradução ou republicação sem itálico '.md5($fragment),function()use($fragment){
  $codes=['A. Tradutor'=>'translated_work_translator_plain','Trans.'=>'translated_work_translator_plain','2nd ed.'=>'translated_work_edition_plain','(Original work published 1900)'=>'original_publication_plain'];
  $r=apaResults(italicDocument([stage42Ref()],[['Obra traduzida',$fragment]]));same('não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])[$codes[$fragment]]);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['1900'=>true,'2020'=>true,'2030'=>false,'0000'=>false] as $year=>$valid) {
 test('cronologia original e consultada '.$year,function()use($year,$valid){
  $r=apaResults(italicDocument([stage42Ref('A. Tradutor','Trans.','2nd ed.',(string)$year)],[['Obra traduzida']]));same($valid?'conforme':'não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['original_publication_year_order']);same($valid?'manual_review_required':'not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Obra original publicada em','Trabalho original publicado em','Original work published'] as $label) {
 test('indicação original sem presumir tipo '.md5($label),function()use($label){
  $ref='Silva, A. (2020). Obra sintética. Editora Exemplo. ('.$label.' 1900)';$r=apaResults(italicDocument([$ref],[['Obra sintética']]));same('conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['original_publication_plain']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['Texto (Silva, 1900).','Texto (Silva, 1900/2020).','Silva (1900/2020).'] as $text) {
 test('ano original ou barra não vira ausência automática '.md5($text),function()use($text){
  $r=apaResults(italicDocument([stage42Ref()],[['Obra traduzida']],[$text]));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['(Original work published desconhecido)','(Original work published 1900) (Original work published 1910)','Original work published 1900'] as $annotation) {
 test('indicação original ambígua protege revisão '.md5($annotation),function()use($annotation){
  $r=apaResults(italicDocument(['Silva, A. (2020). Obra sintética. Editora Exemplo. '.$annotation],[['Obra sintética']],['Texto (Silva, 1900).']));same('indeterminado',$r['references.citation_matching']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['https://repository.synthetic.invalid/item (Original work published 1900)','(Original work published 1900) https://repository.synthetic.invalid/item'] as $tail) {
 test('ano original com URL em posição reconhecida '.md5($tail),function()use($tail){
  $ref='Silva, A. (2020). Obra sintética. Editora Exemplo. '.$tail;$r=apaResults(italicDocument([$ref],[['Obra sintética']],['Texto (Silva, 1900).']));same('indeterminado',$r['references.citation_matching']['status']);same('conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['original_publication_year_order']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('tradução não presume livro para impor título ou fonte em itálico',function(){
 $r=apaResults(italicDocument([stage42Ref()],[]));same('translated_work_layout_candidate',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('edição ou ano original ausentes não são inventados',function(){
 $r=apaResults(italicDocument([stage42Ref('A. Tradutor','Trans.','','')],[['Obra traduzida']]));$c=auditCodes($r['references.apa7']['evidence']['style_audit']);same(false,isset($c['translated_work_edition_plain']));same(false,isset($c['original_publication_year_order']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('ordinal inglês inválido continua bloqueado na tradução',function(){
 $r=apaResults(italicDocument([stage42Ref('A. Tradutor','Trans.','2th ed.')],[['Obra traduzida']]));same('não conforme',auditCodes($r['references.apa7']['evidence']['style_audit'])['edition_ordinal']);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('ano original não fortalece acusação de sufixo de outra edição',function(){
 $refs=[stage42Ref(),'Silva, A. (1900a). Outra obra. Editora Exemplo.'];$r=apaResults(italicDocument($refs,[['Obra traduzida'],['Outra obra']],['Texto (Silva, 1900).']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('ano original não fortalece desambiguação et al da edição',function(){
 $refs=[stage36Ref(['Silva','Souza','Lima'],'1900'),stage36Ref(['Silva','Costa','Ramos'],'2020').' (Original work published 1900)'];$r=apaResults(italicDocument($refs,[],['Texto (Silva et al., 1900).']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
test('minuta aponta cronologia sem copiar datas ou tradutor',function(){
 $d=italicDocument([stage42Ref('A. Tradutor','Trans.','2nd ed.','2030')],[['Obra traduzida']]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'conferir os anos declarados')!==false);foreach(['2030','A. Tradutor','Obra traduzida']as$value){same(false,strpos($draft['body'],$value)!==false);}
});
test('ano original com edição sem data não gera cronologia inventada',function(){
 $r=apaResults(italicDocument([stage42Ref('A. Tradutor','Trans.','2nd ed.','1900','n.d.')],[['Obra traduzida']],['Texto (Silva, n.d.).']));same(false,isset(auditCodes($r['references.apa7']['evidence']['style_audit'])['original_publication_year_order']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});

test('ano original ambíguo impede ordenar grupo por edição presumida',function(){
 $refs=['Silva, A. (1900a). Obra primeira. Editora Exemplo.','Silva, A. (1900). Obra segunda. Editora Exemplo.',stage42Ref()];
 $r=apaResults(italicDocument($refs,[['Obra primeira'],['Obra segunda'],['Obra traduzida']],['Texto (Silva, 1900a, 1900).']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',$r['references.apa7.citation_format']['status']);
});
