<?php
declare(strict_types=1);
require_once __DIR__.'/../plugins/generic/estrabao/SubmissionSnapshot.inc.php';
function withDeskPanel(callable $callback,bool $abnt=false):void {
    withReview(function($service,$request,$form,$path,$dir)use($callback){
        $data=fixture();$pub=['id'=>21,'submissionId'=>17,'version'=>1];
        foreach(['title','abstract','keywords']as$key){$pub[$key]=$data[$key];}
        $pub['subtitle']=[];$pub['authors']=array_map(function($row){return new AdmissionTestRecord($row);},$data['authors']);
        $publication=new AdmissionTestRecord($pub);$form->submission->data['currentPublication']=$publication;
        $form->submission->data['currentPublicationId']=21;
        $analysis=(new EstrabaoAdmissionBridge())->analyze($form);
        $request->vars=['submissionId'=>'17','csrfToken'=>'synthetic-token','fingerprint'=>$analysis['fingerprint'],'manuscriptType'=>'article'];
        $callback($service,$request,$form,$publication,$path,$dir);
    },$abnt);
}
test('painel gera devolutiva com snapshot 1.1 da publicação autorizada',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $result=$service->draft($request,17);same(false,$result['draft']['send_authorized']);same(true,$result['draft']['editor_review_required']);
        $source=(new EstrabaoSubmissionSnapshot())->read($form);
        same($source['snapshot']->digest(),$result['draft']['source_input_sha256']);same('article',$result['manuscript_type']);
        same(64,strlen($result['fingerprint']));same(true,count($result['pending_groups'])>0);
    });
});
test('gerar carta não registra aprovação, revogação nem alteração editorial',function(){
    withDeskPanel(function($service,$request,$form){
        $before=$service->view($request,17);$service->draft($request,17);$after=$service->view($request,17);
        same($before['events'],$after['events']);same(false,$after['release_active']);same(4,$form->submission->getSubmissionProgress());
    });
});
test('gerente pode gerar carta de ABNT bloqueada sem poder liberá-la',function(){
    withDeskPanel(function($service,$request,$form){
        $r=$service->draft($request,17);same(true,strpos($r['draft']['body'],'padrões ABNT reconhecidos')!==false);
        same('not_accepted',$service->view($request,17)['analysis']['decision']);same(false,$service->view($request,17)['release_active']);
    },true);
});
test('correções usam metadados atuais e ignoram dados, relatório ou carta enviados pelo cliente',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        unset($publication->data['title']['es_ES']);$request->vars['title']=['es_ES'=>'forged'];$request->vars['body']='PRIVATE_FORGED';
        $request->vars['report']=['status'=>'conforme'];$r=$service->draft($request,17);
        same(true,strpos($r['draft']['body'],'campo de título em espanhol')!==false);same(false,strpos(json_encode($r),'PRIVATE_FORGED')!==false);
    });
});
test('metadado malformado fica indisponível em vez de gerar ausência inventada',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $publication->data['abstract']='not-a-map';$source=(new EstrabaoSubmissionSnapshot())->read($form);
        same(null,$source['snapshot']->field('abstract'));$r=$service->draft($request,17);
        same(false,strpos($r['draft']['body'],'campo de resumo')!==false);
    });
});
test('leitura DAO vazia confirma campo ausente; autores não disponíveis ficam inconclusivos',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $publication->data['abstract']=null;$publication->data['authors']=null;
        $r=$service->draft($request,17);same(true,strpos($r['draft']['body'],'campo de resumo em espanhol')!==false);
        same(false,strpos($r['draft']['body'],'ORCID não preenchido')!==false);
    });
});
test('não se misturam publicações de outra submissão nem IDs divergentes',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $publication->data['submissionId']=99;reviewRejects(function()use($service,$request){$service->draft($request,17);});
        $publication->data['submissionId']=17;$form->submission->data['currentPublicationId']=22;
        reviewRejects(function()use($service,$request){$service->draft($request,17);});
    });
});
test('POST CSRF, tipo editorial e fingerprint válidos são obrigatórios',function(){
    withDeskPanel(function($service,$request){
        $original=$request->vars;
        foreach(['get','csrf','missing-csrf','type','fingerprint','stale']as$case){
            $request->vars=$original;$request->post=true;$request->csrf=true;
            if($case==='get'){$request->post=false;}if($case==='csrf'){$request->csrf=false;}
            if($case==='missing-csrf'){unset($request->vars['csrfToken']);}
            if($case==='type'){$request->vars['manuscriptType']='guess';}if($case==='fingerprint'){$request->vars['fingerprint']=[];}
            if($case==='stale'){$request->vars['fingerprint']=str_repeat('0',64);}
            reviewRejects(function()use($service,$request){$service->draft($request,17);});
        }
    });
});
test('autor ou gerente de outro contexto não pode gerar devolutiva',function(){
    withDeskPanel(function($service,$request){
        foreach([[],[[8,77]]]as$roles){DAORegistry::$roles=$roles;reviewRejects(function()use($service,$request){$service->draft($request,17);});}
    });
});
test('metadados novos mudam a proveniência da carta mesmo com DOCX igual',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $a=$service->draft($request,17);$publication->data['title']['es_ES']='Título sintético actualizado';$b=$service->draft($request,17);
        same(false,$a['fingerprint']===$b['fingerprint']);same(false,$a['draft']['source_input_sha256']===$b['draft']['source_input_sha256']);
        same($a['draft']['source_document_sha256'],$b['draft']['source_document_sha256']);
    });
});
class DeskChangingPublication extends AdmissionTestRecord {
    public $reads=0;
    function getCurrentPublication(){
        $this->reads++;if($this->reads>1){$this->data['currentPublication']->data['title']['es_ES']='Cambio durante la generación';}
        return parent::getCurrentPublication();
    }
}
test('alteração de metadados durante geração impede devolver carta desatualizada',function(){
    withDeskPanel(function($service,$request,$form){
        $changed=new DeskChangingPublication($form->submission->data);DAORegistry::$submissions[17]=$changed;
        reviewRejects(function()use($service,$request){$service->draft($request,17);});
    });
});
test('arquivo trocado após abrir a tela não reaproveita o fingerprint da carta',function(){
    withDeskPanel(function($service,$request,$form,$publication,$path){
        file_put_contents($path,'synthetic replacement');reviewRejects(function()use($service,$request){$service->draft($request,17);});
    });
});
test('assunto e corpo não recebem títulos, ORCIDs ou caminhos privados do OJS',function(){
    withDeskPanel(function($service,$request,$form,$publication,$path){
        $publication->data['title']['pt_BR']='PRIVATE MANUSCRIPT TITLE ABOUT LANDSCAPE AND TERRITORY';
        $r=$service->draft($request,17);$encoded=json_encode($r);same(false,strpos($encoded,'PRIVATE MANUSCRIPT')!==false);
        same(false,strpos($encoded,'0000-0000')!==false);same(false,strpos($encoded,$path)!==false);same(false,isset($r['document']));
    });
});
test('handler apresenta minuta separada da revisão de liberação sem salvar texto',function(){
    withDeskPanel(function($service,$request){
        $handler=new EstrabaoAdmissionReviewHandler();$handler->deskDraft([],$request);
        same('plugins.generic.estrabao.deskGenerated',TemplateManager::$assigned['reviewMessage']);
        same(true,isset(TemplateManager::$assigned['deskDraftModel']['draft']['body']));
        same(0,TemplateManager::$assigned['reviewModel']['revision']);
        $request->csrf=false;$handler->deskDraft([],$request);
        same('plugins.generic.estrabao.deskUnavailable',TemplateManager::$assigned['reviewMessage']);same(null,TemplateManager::$assigned['deskDraftModel']);
    });
});

test('locale alternativo não normalizado fica pendente em vez de acusar idioma ausente',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $publication->data['title']=['pt_PT'=>'Título português sintético'];
        $source=(new EstrabaoSubmissionSnapshot())->read($form);same(null,$source['snapshot']->field('title'));
        $r=$service->draft($request,17);same(false,strpos($r['draft']['body'],'campo de título em português')!==false);
    });
});
test('autoria malformada ou acima do limite técnico não cria limite editorial de autores',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $publication->data['authors']=array_fill(0,2049,new AdmissionTestRecord(['orcid'=>null]));
        same(null,(new EstrabaoSubmissionSnapshot())->read($form)['snapshot']->field('authors'));
        $r=$service->draft($request,17);same(false,strpos($r['draft']['body'],'2048')!==false);
        $publication->data['authors']=[new AdmissionTestRecord(['orcid'=>['bad']])];
        same(null,(new EstrabaoSubmissionSnapshot())->read($form)['snapshot']->field('authors'));
    });
});
test('análise padrão não expõe texto e inclusão opt-in serve somente ao gerador interno',function(){
    withDeskPanel(function($service,$request,$form){
        $bridge=new EstrabaoAdmissionBridge();same(false,isset($bridge->analyze($form)['document']));
        same(true,isset($bridge->analyze($form,true)['document']));
        same(false,isset($service->view($request,17)['analysis']['document']));
    });
});

class DeskDocumentChangingPublication extends AdmissionTestRecord {
    public $path;
    public $changed=false;
    function getData($key){
        if($key==='title'&&!$this->changed){$this->changed=true;file_put_contents($this->path,'synthetic change during generation');}
        return parent::getData($key);
    }
}
test('DOCX alterado durante geração impede a entrega da minuta',function(){
    withDeskPanel(function($service,$request,$form,$publication,$path){
        $p=new DeskDocumentChangingPublication($publication->data);$p->path=$path;
        $form->submission->data['currentPublication']=$p;
        reviewRejects(function()use($service,$request){$service->draft($request,17);});
    });
});
test('publicação ausente e snapshot acima do limite técnico falham sem inventar pendências editoriais',function(){
    withDeskPanel(function($service,$request,$form,$publication){
        $form->submission->data['currentPublication']=null;reviewRejects(function()use($service,$request){$service->draft($request,17);});
        $form->submission->data['currentPublication']=$publication;$publication->data['abstract']['pt_BR']=str_repeat('x',1048577);
        reviewRejects(function()use($service,$request){$service->draft($request,17);});
    });
});
