<?php
declare(strict_types=1);
function stage39Ref(string $description='[Tese de doutorado, Universidade Exemplo]',string $source='Repositório Exemplo'):string {
 return 'Silva, A. (2020). Obra sintética '.$description.'. '.$source.'.';
}
foreach ([
 ['[Tese de doutorado, Universidade Exemplo]','repository_thesis_declared'],
 ['[Dissertação de mestrado, Universidade Exemplo]','repository_thesis_declared'],
 ['[Doctoral dissertation, Example University]','repository_thesis_declared'],
 ["[Master’s thesis, Example University]",'repository_thesis_declared'],
 ['[Tese de doutorado não publicada]','unpublished_thesis_declared'],
 ['[Dissertação de mestrado não publicada]','unpublished_thesis_declared'],
 ['[Unpublished doctoral dissertation]','unpublished_thesis_declared'],
 ["[Unpublished master's thesis]",'unpublished_thesis_declared'],
 ['(Report No. 2024-1)','numbered_report_declared'],
 ['(Relatório nº 12/2024)','numbered_report_declared'],
] as $case) {
 test('modelo longo declarado reconhecido '.md5($case[0]),function()use($case){
  $r=apaResults(italicDocument([stage39Ref($case[0])],[['Obra sintética']]));$a=$r['references.apa7']['evidence']['style_audit'];same($case[1],$a['samples'][0]['type']);same('conforme',auditCodes($a)['declared_work_title_italic']);same('conforme',auditCodes($a)['declared_work_description_plain']);same('conforme',auditCodes($a)['declared_work_source_plain']);same('indeterminado',auditCodes($a)['declared_work_source_review']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
foreach (['[Tese de doutorado, Universidade Exemplo]','[Unpublished doctoral dissertation]','(Report No. 2024-1)'] as $description) {
 foreach (['title','description','source'] as $field) {
  test('erro de itálico em modelo explícito '.md5($description.$field),function()use($description,$field){
   $fragments=$field==='title'?[]:array_merge(['Obra sintética'],[$field==='description'?$description:'Repositório Exemplo']);
   $r=apaResults(italicDocument([stage39Ref($description)],[$fragments]));$a=$r['references.apa7']['evidence']['style_audit'];same('não conforme',auditCodes($a)['declared_work_'.($field==='title'?'title_italic':($field==='description'?'description_plain':'source_plain'))]);same('not_accepted',$r['references.apa7']['evidence']['admission_policy']['decision']);
  });
 }
}
foreach (['[Tese de doutorado]','[Dissertação de mestrado, U. Exemplo]','[Tese de doutorado, Universidade Exemplo, Campus Central]','[Thesis]','(Report No. )','(Report No. 1) (Outra indicação)'] as $description) {
 test('descrição fora do subconjunto fica indeterminada '.md5($description),function()use($description){
  $r=apaResults(italicDocument([stage39Ref($description)]));same('indeterminado',$r['references.apa7.recognized_formats']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('relatório sem número não é rejeitado por ausência do número',function(){
 $r=apaResults(italicDocument(['Silva, A. (2020). Relatório sintético. Editora Exemplo.'],[['Relatório sintético']]));same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);same(false,isset(auditCodes($r['references.apa7']['evidence']['style_audit'])['declared_work_description_plain']));
});
test('formatação desconhecida não comprova falha em tese',function(){
 $d=italicDocument([stage39Ref()]);foreach($d['paragraphs'] as &$p){$p['reference_formatting']=['available'=>false];}unset($p);
 $r=apaResults($d);same('indeterminado',$r['references.apa7.recognized_formats']['status']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
test('metadado com acentos preserva limites de itálico',function(){
 $a=apaAudit(italicDocument([stage39Ref()],[['Obra sintética','Universidade Exemplo']]));same('conforme',auditCodes($a)['declared_work_title_italic']);same('não conforme',auditCodes($a)['declared_work_description_plain']);same('conforme',auditCodes($a)['declared_work_source_plain']);
});
foreach (['https://repository.synthetic.invalid/item/1','https://doi.org/10.1234/synthetic'] as $url) {
 test('identificador não muda tipo declarado '.md5($url),function()use($url){
  $r=apaResults(italicDocument([stage39Ref().' '.$url],[['Obra sintética']]));same('repository_thesis_declared',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
 });
}
test('descrição não exige URL para provar existência ou não publicação',function(){
 $a=apaAudit(italicDocument([stage39Ref('[Tese de doutorado não publicada]','Universidade Exemplo')],[['Obra sintética']]));same('unpublished_thesis_declared',$a['samples'][0]['type']);same('indeterminado',$a['recognized_formats_status']);
});
test('citação de tese continua ligada no formato pessoal',function(){
 $r=apaResults(italicDocument([stage39Ref()],[['Obra sintética']]));same('conforme',$r['references.citation_matching']['status']);
});
test('minuta orienta o itálico sem copiar instituição ou título',function(){
 $d=italicDocument([stage39Ref()]);$report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),\Estrabao\Rules\DefaultRules::all($d,'article','pt'));$draft=\Estrabao\Reporting\DeskResponse::generate($report);same(true,strpos($draft['body'],'aplicar itálico ao título no modelo declarado')!==false);same(false,strpos(json_encode($draft),'Universidade Exemplo')!==false);same(false,strpos(json_encode($draft),'Obra sintética')!==false);
});
test('tese malformada com tipo ambíguo não vira livro para impor itálico',function(){
 $r=apaResults(italicDocument(['Silva, A. (2020). Obra sintética [Tese de doutorado]. Universidade Exemplo.']));same('unresolved',$r['references.apa7']['evidence']['style_audit']['samples'][0]['type']);same('manual_review_required',$r['references.apa7']['evidence']['admission_policy']['decision']);
});
