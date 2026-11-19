<?php
declare(strict_types=1);
function stage41Ref(string $label='Data set',string $version='Version 1.2',string $source='Repositório Sintético',string $title='Dados sintéticos'):string {
 return 'Silva, A. (2020). '.$title.($version!==''?' ('.$version.')':'').' ['.$label.']'.($source!==''?'. '.$source:'').'.';
}
foreach (['Data set','Conjunto de dados','Computer software','Software'] as $label) {
 foreach ([['Version 1.2','Repositório Sintético'],['Versão 2.0-beta',''],['','']] as $options) {
  test('modelo digital explícito com campos opcionais '.md5($label.json_encode($options)),function()use($label,$options){
   $r=apaResults(italicDocument([stage41Ref($label,$options[0],$options[1])],[['Dados sintéticos']]));$a=$r['references.apa7']['evidence']['style_audit'];same(in_array($label,['Data set','Conjunto de dados'],true)?'dataset_declared':'software_declared',$a['samples'][0]['type']);same('conforme',auditCodes($a)['digital_work_title_italic']);same('conforme',auditCodes($a)['digital_work_description_plain']);same($options[0]!=='',isset(auditCodes($a)['digital_work_version_plain']));same($options[1]!=='',isset(auditCodes($a)['digital_work_source_plain']));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
  });
 }
}
foreach (['Data set','Computer software'] as $label) {
 foreach (['title','version','description','source'] as $field) {
  test('falha de itálico em modelo digital '.md5($label.$field),function()use($label,$field){
   $extra=['version'=>'(Version 1.2)','description'=>'['.$label.']','source'=>'Repositório Sintético'];$fragments=$field==='title'?[]:['Dados sintéticos',$extra[$field]];
   $r=apaResults(italicDocument([stage41Ref($label)],[$fragments]));$a=$r['references.apa7']['evidence']['style_audit'];same('não conforme',auditCodes($a)['digital_work_'.$field.($field==='title'?'_italic':'_plain')]);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
  });
 }
}
foreach (['QGIS','R','SPSS','DADOS SINTÉTICOS'] as $title) {
 test('grafia oficial não é reescrita nem rejeitada por caixa '.md5($title),function()use($title){
  $r=apaResults(italicDocument([stage41Ref('Software','Version 1.2','',$title)],[[$title]]));$a=$r['references.apa7']['evidence']['style_audit'];same('software_declared',$a['samples'][0]['type']);same(false,isset(auditCodes($a)['work_title_full_uppercase']));same('indeterminado',auditCodes($a)['digital_work_title_case_review']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach ([['Software','Version '],['Software','Version 1.2 stable'],['Data set','Version (1.2)'],['Data set adicional','Version 1.2'],['Mobile app','Version 1.2']] as $case) {
 test('descrição ou versão fora da gramática não impõe tipo '.md5(json_encode($case)),function()use($case){
  $r=apaResults(italicDocument([stage41Ref($case[0],$case[1])]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('unresolved',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);
 });
}
foreach (['https://repository.synthetic.invalid/data.zip','https://downloads.synthetic.invalid/program.exe','https://doi.org/10.1234/synthetic'] as $url) {
 test('URL ou DOI informado não é consultado '.md5($url),function()use($url){
  $r=apaResults(italicDocument([stage41Ref().' '.$url],[['Dados sintéticos']]));same('dataset_declared',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same(false,$r['references.apa7']['evidence']['external_lookup_performed']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(false,strpos(json_encode($r),$url)!==false);
 });
}
test('nome de produto e URL sem descrição não comprovam software',function(){
 $r=apaResults(italicDocument(['Silva, A. (2020). Programa sintético. Portal Sintético. https://downloads.synthetic.invalid/program.exe'],[['Programa sintético']]));same(false,in_array($r['references.apa7']['evidence']['style_audit']['samples'][0]['type'],['dataset_declared','software_declared'],true));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('fonte complexa fica indeterminada em vez de receber exigência presumida',function(){
 $r=apaResults(italicDocument([stage41Ref('Software','Version 1.2','Editora Exemplo, Inc.')]));same('unresolved',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('formatação digital indisponível não comprova falha',function(){
 $d=italicDocument([stage41Ref()]);foreach($d['paragraphs'] as &$p){$p['reference_formatting']=['available'=>false];}unset($p);$r=apaResults($d);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same('indeterminado',auditCodes($r['references.apa7']['evidence']['style_audit'])['digital_work_title_italic']);
});
test('minuta digital não copia produto versão ou repositório',function(){
 $d=italicDocument([stage41Ref('Software')],[['Dados sintéticos','(Version 1.2)']]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'retirar o itálico da versão')!==false);foreach(['Dados sintéticos','Version 1.2','Repositório Sintético']as$value){same(false,strpos($draft['body'],$value)!==false);}
});
test('descrição digital não aceita ausência de título como prova de tipo',function(){
 $r=apaResults(italicDocument(['Silva, A. (2020). [Data set]. Repositório Sintético.']));same('unresolved',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('conjunto de dados mantém ligação pessoal autor ano',function(){
 $r=apaResults(italicDocument([stage41Ref()],[['Dados sintéticos']]));same('conforme',$r['references.citation_matching']['status']);same('conforme',$r['references.apa7.citation_format']['status']);
});
