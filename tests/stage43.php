<?php
declare(strict_types=1);
test('orientação de referências usa assets backend sem copiar metadados',function(){
 $p=new TitleGuidancePlugin();$p->enabled=true;Config::$values=[];Application::$request=new TitleGuidanceRequest();Application::$request->context=new AdmissionTestRecord(['id'=>3]);$m=new TitleGuidanceManager();$p->titleFormAssets('TemplateManager::display',[$m]);
 same('/ojs/plugins/generic/estrabao/reference-guidance.js',$m->scripts['estrabao-reference-guidance'][0]);same(['backend'],$m->scripts['estrabao-reference-guidance'][1]['contexts']);same(true,strpos($m->scripts['estrabao-reference-guidance-config'][0],'referenceJoinedWarning')!==false);same(false,strpos($m->scripts['estrabao-reference-guidance-config'][0],'citationsRaw')!==false);
});
foreach (['disabled','no_context'] as $case) {
 test('aviso de referências respeita escopo '.$case,function()use($case){
  $p=new TitleGuidancePlugin();$p->enabled=$case!=='disabled';Application::$request=new TitleGuidanceRequest();Application::$request->context=$case==='no_context'?null:new AdmissionTestRecord(['id'=>3]);$m=new TitleGuidanceManager();$p->titleFormAssets('TemplateManager::display',[$m]);same([],$m->scripts);
 });
}
