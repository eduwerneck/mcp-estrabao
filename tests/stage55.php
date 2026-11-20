<?php
declare(strict_types=1);
require_once __DIR__.'/../plugins/generic/estrabao/EditorialReviewService.inc.php';
function withEditorial(callable $callback,bool $abnt=false):void {
 withDeskPanel(function($apa,$request,$form,$publication,$path,$dir)use($callback){
  $editorial=new EstrabaoEditorialReviewService();$draft=$apa->draft($request,17);$view=$editorial->view($request,17,$draft);
  $request->vars+=['editorialFingerprint'=>$view['fingerprint'],'editorialRevision'=>(string)$view['revision'],'editorialReviewed'=>'1','editorialStates'=>array_fill_keys(EstrabaoEditorialReviewStore::ITEMS,'pending')];
  $callback($editorial,$apa,$request,$form,$publication,$path,$dir,$draft);
 },$abnt);
}
function editorialFresh($editorial,$apa,$request):array {
 $request->vars['fingerprint']=(new EstrabaoAdmissionBridge())->analyze($apa->form($request,17))['fingerprint'];
 return $editorial->view($request,17,$apa->draft($request,17));
}
test('registro editorial inicia todos os itens pendentes sem inferir aprovação',function(){
 withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir,$draft){$v=$s->view($r,17,$draft);same(12,count($v['items']));same(['pending'],array_values(array_unique(array_column($v['items'],'state'))));same(false,$v['current']);same(0,$v['revision']);same(false,is_file($dir.'/editorial-3-17.json'));});
});
test('estados humanos persistem sem liberar APA ou alterar manuscrito',function(){
 withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$before=hash_file('sha256',$path);$r->vars['editorialStates']['metadata']='reviewed';$r->vars['editorialStates']['abstracts']='adjustments';$r->vars['editorialStates']['funding']='not_applicable';$draft=$s->save($r,17);$v=$s->view($r,17,$draft);same(true,$v['current']);same(1,$v['revision']);same('reviewed',$v['events'][0]['states']['metadata']);same('adjustments',$v['events'][0]['states']['abstracts']);same('not_applicable',$v['events'][0]['states']['funding']);same(77,$v['events'][0]['reviewer_id']);same(false,$apa->view($r,17)['release_active']);same($before,hash_file('sha256',$path));same(0,fileperms($dir.'/editorial-3-17.json')&0077);});
});
test('conferência não substitui bloqueio de ABNT comprovada',function(){
 withEditorial(function($s,$apa,$r){$r->vars['editorialStates']=array_fill_keys(EstrabaoEditorialReviewStore::ITEMS,'reviewed');$s->save($r,17);same('not_accepted',$apa->view($r,17)['analysis']['decision']);same(false,$apa->view($r,17)['release_active']);},true);
});
test('pendência editorial não cria bloqueio adicional sobre liberação APA',function(){
 withEditorial(function($s,$apa,$r){$editorialVars=$r->vars;reviewPost($apa,$r);$apa->save($r,17);$r->vars=$editorialVars;$r->vars['editorialStates']['metadata']='adjustments';$s->save($r,17);same(true,$apa->view($r,17)['release_active']);});
});
test('histórico editorial é separado da carta e não persiste conteúdo privado',function(){
 withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$pub->data['authors'][0]->data['givenName']=['pt_BR'=>'PRIVATE_AUTHOR'];$pub->data['authors'][0]->data['email']='PRIVATE_EMAIL';$v=editorialFresh($s,$apa,$r);$r->vars['editorialFingerprint']=$v['fingerprint'];$s->save($r,17);$raw=file_get_contents($dir.'/editorial-3-17.json');foreach(['PRIVATE_AUTHOR','PRIVATE_EMAIL','Estudo da paisagem','orcid.org','reviewAction','source_input_sha256']as$token){same(false,strpos($raw,$token)!==false);}same(false,is_file($dir.'/review-3-17.json'));});
});
test('duas conferências são acrescentadas e reset pendente permanece histórico',function(){
 withEditorial(function($s,$apa,$r){$r->vars['editorialStates']['metadata']='reviewed';$s->save($r,17);$r->vars['editorialRevision']='1';$r->vars['editorialStates']['metadata']='pending';$d=$s->save($r,17);$v=$s->view($r,17,$d);same(2,$v['revision']);same('reviewed',$v['events'][0]['states']['metadata']);same('pending',$v['events'][1]['states']['metadata']);same(true,$v['current']);});
});
test('aba antiga e repetição de POST não sobrescrevem eventos',function(){
 withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$s->save($r,17);$before=file_get_contents($dir.'/editorial-3-17.json');reviewRejects(function()use($s,$r){$s->save($r,17);});same($before,file_get_contents($dir.'/editorial-3-17.json'));});
});
foreach(['get','csrf','token','declaration','fingerprint','revision']as$case){
 test('registro editorial exige POST consentimento CSRF e versão '.$case,function()use($case){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir)use($case){
  if($case==='get'){$r->post=false;}elseif($case==='csrf'){$r->csrf=false;}elseif($case==='token'){unset($r->vars['csrfToken']);}elseif($case==='declaration'){unset($r->vars['editorialReviewed']);}elseif($case==='fingerprint'){$r->vars['editorialFingerprint']=str_repeat('0',64);}else{$r->vars['editorialRevision']='1';}
  reviewRejects(function()use($s,$r){$s->save($r,17);});same(false,is_file($dir.'/editorial-3-17.json'));
 });});
}
foreach(['missing','extra','invalid','nested','na_required']as$case){
 test('estados incompletos ou não permitidos são recusados '.$case,function()use($case){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir)use($case){
  if($case==='missing'){unset($r->vars['editorialStates']['metadata']);}elseif($case==='extra'){$r->vars['editorialStates']['PRIVATE']='reviewed';}elseif($case==='invalid'){$r->vars['editorialStates']['metadata']='approve';}elseif($case==='nested'){$r->vars['editorialStates']['metadata']=['reviewed'];}else{$r->vars['editorialStates']['metadata']='not_applicable';}
  reviewRejects(function()use($s,$r){$s->save($r,17);});same(false,is_file($dir.'/editorial-3-17.json'));
 });});
}
foreach(EstrabaoEditorialReviewStore::CONDITIONAL as $item){test('não aplicável só em categoria condicional '.$item,function()use($item){withEditorial(function($s,$apa,$r)use($item){$r->vars['editorialStates'][$item]='not_applicable';$d=$s->save($r,17);same('not_applicable',$s->view($r,17,$d)['events'][0]['states'][$item]);});});}
foreach(['metadata','type','publication','author_name','affiliation','email','orcid','file']as$case){
 test('mudança de fonte invalida envio antigo sem apagar histórico '.$case,function()use($case){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir)use($case){
  $r->vars['editorialStates']['metadata']='reviewed';$s->save($r,17);$r->vars['editorialRevision']='1';$before=file_get_contents($dir.'/editorial-3-17.json');
  if($case==='metadata'){$pub->data['title']['es_ES']='Cambio';}elseif($case==='type'){$r->vars['manuscriptType']='research_communication';}elseif($case==='publication'){$pub->data['version']=2;}elseif($case==='author_name'){$pub->data['authors'][0]->data['givenName']=['pt_BR'=>'Outro nome'];}elseif($case==='affiliation'){$pub->data['authors'][0]->data['affiliation']=['pt_BR'=>'Outra instituição'];}elseif($case==='email'){$pub->data['authors'][0]->data['email']='other@synthetic.invalid';}elseif($case==='orcid'){$pub->data['authors'][0]->data['orcid']='0000-0000-0000-0000';}else{$z=new ZipArchive();$z->open($path);$z->addFromString('synthetic-change.txt','v2');$z->close();}
  reviewRejects(function()use($s,$r){$s->save($r,17);});same($before,file_get_contents($dir.'/editorial-3-17.json'));$v=editorialFresh($s,$apa,$r);same(false,$v['current']);same(true,$v['has_history']);same(['pending'],array_values(array_unique(array_column($v['items'],'state'))));same(1,$v['revision']);
 });});
}
foreach(['author','foreign_role','foreign_submission','completed']as$case){
 test('controle de acesso editorial permanece obrigatório '.$case,function()use($case){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir,$draft)use($case){
  if($case==='author'){DAORegistry::$roles=[];}elseif($case==='foreign_role'){DAORegistry::$roles=[[8,77]];}elseif($case==='foreign_submission'){$form->submission->data['contextId']=8;}else{$form->submission->data['submissionProgress']=0;}
  reviewRejects(function()use($s,$r,$draft){$s->view($r,17,$draft);});reviewRejects(function()use($s,$r){$s->save($r,17);});same(false,is_file($dir.'/editorial-3-17.json'));
 });});
}
test('desabilitar gate recusa registro sem mudar política',function(){withEditorial(function($s,$apa,$r){Config::$values['estrabao']['admission_gate']=false;reviewRejects(function()use($s,$r){$s->save($r,17);});});});
test('ledger editorial corrompido não concede nem remove liberação APA',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir,$draft){file_put_contents($dir.'/editorial-3-17.json','{"schema_version":0}');chmod($dir.'/editorial-3-17.json',0600);reviewRejects(function()use($s,$r,$draft){$s->view($r,17,$draft);});same(false,$apa->view($r,17)['release_active']);});});
test('armazenamento recusa permissões públicas e link simbólico',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){chmod($dir,0755);reviewRejects(function()use($s,$r){$s->save($r,17);});chmod($dir,0700);$target=$dir.'/target';file_put_contents($target,'PRIVATE');symlink($target,$dir.'/editorial-3-17.json');reviewRejects(function()use($s,$r){$s->save($r,17);});same('PRIVATE',file_get_contents($target));});});
test('lock concorrente impede gravação editorial sem efeito sobre APA',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$h=fopen($dir.'/editorial-3-17.json.lock','c+b');chmod($dir.'/editorial-3-17.json.lock',0600);flock($h,LOCK_EX);try{reviewRejects(function()use($s,$r){$s->save($r,17);});}finally{flock($h,LOCK_UN);fclose($h);}same(false,$apa->view($r,17)['release_active']);});});
test('cem eventos preservam histórico e impedem apenas novas gravações',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){for($i=0;$i<100;$i++){$r->vars['editorialRevision']=(string)$i;$d=$s->save($r,17);}$v=$s->view($r,17,$d);same(100,$v['revision']);same(false,$v['can_save']);$r->vars['editorialRevision']='100';reviewRejects(function()use($s,$r){$s->save($r,17);});same(false,$apa->view($r,17)['release_active']);});});
foreach(['ledger','lock']as$target){test('ledger editorial recusa link simbólico '.$target,function()use($target){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir,$draft)use($target){$file=$dir.'/editorial-3-17.json'.($target==='lock'?'.lock':'');if(is_file($file)){unlink($file);}symlink($path,$file);reviewRejects(function()use($s,$r,$draft){$s->view($r,17,$draft);});});});}
test('callback inválido não altera registro editorial persistido',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$s->save($r,17);$before=file_get_contents($dir.'/editorial-3-17.json');reviewRejects(function(){(new EstrabaoEditorialReviewStore())->transaction(3,17,function(&$l){$l['events'][0]['states']['metadata']='approve';});});same($before,file_get_contents($dir.'/editorial-3-17.json'));});});

test('ledger vazio existente é corrupção e não reinicia histórico',function(){withEditorial(function($s,$apa,$r,$form,$pub,$path,$dir){$ledger=$dir.'/editorial-3-17.json';file_put_contents($ledger,'');chmod($ledger,0600);reviewRejects(function()use($s,$r){$s->save($r,17);});same('',file_get_contents($ledger));same(false,$apa->view($r,17)['release_active']);});});
