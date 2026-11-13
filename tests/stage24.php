<?php
declare(strict_types=1);
use Estrabao\Rules\FundingConsistency;
use Estrabao\Contracts\Submission;
function fundingCheck(array $texts,?array $metadata,array $overrides=[]):array{
 $s=fixture();$s['schema_version']='1.2';$s['supporting_agencies']=$metadata;
 $doc=referenceDocument([],[]);$doc['paragraphs']=[];
 foreach($texts as$i=>$text){$doc['paragraphs'][]=['index'=>$i,'text'=>$text,'outline'=>['known'=>true,'level'=>9],'format'=>['bold'=>false]];}
 $doc=array_replace($doc,$overrides);
 return (new FundingConsistency($doc,'pt'))->evaluate(new Submission($s))[0]->jsonSerialize();
}
foreach([
 [['Fontes de Fomento','Agência Sintética, projeto 123.','Referências'],[], 'não conforme','word_declared_ojs_empty'],
 [['Este estudo foi financiado pela Agência Sintética.'],[], 'não conforme','word_declared_ojs_empty'],
 [['Fontes de Fomento','Não houve financiamento.'],[], 'conforme','no_observed_declaration_conflict'],
 [['Introdução','Texto normal.'],[], 'conforme','no_observed_declaration_conflict'],
 [['Fontes de Fomento','Agência Sintética.'],['pt_BR'=>['Sem financiamento']], 'não conforme','word_declared_ojs_explicit_none'],
 [['Fontes de Fomento','Agência Sintética.'],['pt_BR'=>['Agência Sintética']], 'indeterminado','both_declared_compare_agencies_and_awards'],
 [['Introdução','Texto normal.'],['pt_BR'=>['Universidade Sintética']], 'indeterminado','ojs_support_word_absent_or_none_review'],
 [['Fontes de Fomento','Não houve financiamento.'],['pt_BR'=>['Universidade Sintética']], 'indeterminado','ojs_support_word_absent_or_none_review'],
]as$i=>$case){test('consistência de declarações '.$i,function()use($case){$c=fundingCheck($case[0],$case[1]);same($case[2],$c['status']);same($case[3],$c['evidence']['reason']);same(false,$c['evidence']['funding_applicability_inferred']);});}
foreach([
 ['Fontes de Fomento','Referências'],['Fontes de Fomento','A preencher.'],['Fontes de Fomento','Financiamento com recursos próprios.'],
 ['Fontes de Fomento','Não houve financiamento público, mas houve apoio privado.'],['Agradecimentos','Agradecemos aos colegas pela leitura.'],
 ['O projeto recebeu financiamento privado.'],['Fontes de Fomento','Apoio institucional da Universidade Sintética.'],
]as$i=>$texts){test('declaração financeira não é inventada '.$i,function()use($texts,$i){$c=fundingCheck($texts,[]);same($i===4?'conforme':'indeterminado',$c['status']);});}
test('fontes parciais e metadado indisponível não geram omissão',function(){
 same('indeterminado',fundingCheck(['Fontes de Fomento','Agência Sintética.'],null)['status']);
 same('indeterminado',fundingCheck(['Introdução'],['pt_BR'=>['Agência']],['available'=>false])['status']);
});
test('fomento em referência bibliográfica não vira declaração do estudo',function(){same('conforme',fundingCheck(['Referências','Este estudo foi financiado pela Agência Sintética.'],[])['status']);});
test('locale não normalizado e metadado misto exigem revisão',function(){
 same('indeterminado',fundingCheck(['Fontes de Fomento','Agência.'],['pt_PT'=>['Agência']])['status']);
 same('indeterminado',fundingCheck(['Fontes de Fomento','Agência.'],['pt_BR'=>['Agência','Sem financiamento']])['status']);
});
test('contrato novo preserva 1.0 e 1.1 e distingue mapa/listas',function(){
 foreach(['1.0','1.1']as$v){rejects(function()use($v){new Submission(['schema_version'=>$v,'supporting_agencies'=>[]]);});}
 Submission::fromJson('{"schema_version":"1.2","supporting_agencies":{"pt_BR":[]}}');
 rejects(function(){Submission::fromJson('{"schema_version":"1.2","supporting_agencies":[]}');});
 rejects(function(){Submission::fromJson('{"schema_version":"1.2","supporting_agencies":{"pt_BR":{}}}');});
 rejects(function(){new Submission(['schema_version'=>'1.2','supporting_agencies'=>['pt_BR'=>[23]]]);});
});
test('snapshot inclui fomento no digest sem reescrever metadados',function(){
 $a=new Submission(['schema_version'=>'1.2','supporting_agencies'=>[]]);$b=new Submission(['schema_version'=>'1.2','supporting_agencies'=>['pt_BR'=>['PRIVATE SUPPORT']]]);same(false,$a->digest()===$b->digest());
});
foreach(['request','require','enable','0',null]as$mode){test('adaptador respeita disponibilidade do campo na submissão '.($mode??'null'),function()use($mode){
 withDeskPanel(function($service,$request,$form,$publication)use($mode){$form->context->data['agencies']=$mode;$publication->data['supportingAgencies']=['pt_BR'=>['PRIVATE SUPPORT']];
 $s=(new EstrabaoSubmissionSnapshot())->read($form)['snapshot'];same(in_array($mode,['request','require'],true)?['pt_BR'=>['PRIVATE SUPPORT']]:null,$s->field('supporting_agencies'));});
});}
test('adaptador lê vazio confirmado e rejeita forma inesperada',function(){
 withDeskPanel(function($service,$request,$form,$publication){$form->context->data['agencies']='request';$s=(new EstrabaoSubmissionSnapshot())->read($form)['snapshot'];same([],$s->field('supporting_agencies'));
 $publication->data['supportingAgencies']='bad';same(null,(new EstrabaoSubmissionSnapshot())->read($form)['snapshot']->field('supporting_agencies'));});
});
test('devolutiva usa correção fixa sem expor agência ou projeto',function(){
 $c=fundingCheck(['Fontes de Fomento','PRIVATE AGENCY projeto 123.'],[]);$d=deskFromChecks([$c]);same(1,count($d['corrections']));same(true,strpos($d['body'],'campo correspondente no OJS')!==false);same(false,strpos(json_encode($d),'PRIVATE AGENCY'));same(false,strpos(json_encode($c),'projeto 123'));
});
test('via inversa fica interna por poder representar apoio não financeiro',function(){
 $c=fundingCheck(['Introdução','Texto normal.'],['pt_BR'=>['PRIVATE UNIVERSITY']]);$d=deskFromChecks([$c]);same([],$d['corrections']);same(1,$d['pending_review_count']);same(false,strpos(json_encode($d),'PRIVATE UNIVERSITY'));
});
test('serviço usa campo do DAO e ignora fomento forjado na requisição',function(){
 withDeskPanel(function($service,$request,$form,$publication){
  $form->context->data['agencies']='request';$request->vars['supportingAgencies']=['pt_BR'=>['FORGED PRIVATE SUPPORT']];
  $r=$service->draft($request,17);same(null,$r['funding_review']);same(false,strpos(json_encode($r),'FORGED PRIVATE SUPPORT'));
  $publication->data['supportingAgencies']=['pt_BR'=>['PRIVATE REAL SUPPORT']];$r=$service->draft($request,17);
  same('fundingReviewOjsOnly',$r['funding_review']);same(false,strpos(json_encode($r),'PRIVATE REAL SUPPORT'));
 });
});
test('DOCX e campo disponível vazios no OJS geram correção sem escrever no fluxo',function(){
 withDeskPanel(function($service,$request,$form,$publication,$path){
  $parts=docxParts(docxBody(['Fontes de Fomento','Agência Sintética.','Referências']));$z=new ZipArchive();$z->open($path,ZipArchive::OVERWRITE);foreach($parts as$n=>$xml){$z->addFromString($n,$xml);}$z->close();
  $form->context->data['agencies']='request';$request->vars['fingerprint']=(new EstrabaoAdmissionBridge())->analyze($form)['fingerprint'];
  $r=$service->draft($request,17);same(true,strpos($r['draft']['body'],'campo disponível de agências de apoio está vazio')!==false);same([],$service->view($request,17)['events']);
 });
});
