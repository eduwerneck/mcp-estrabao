<?php
declare(strict_types=1);
require_once __DIR__ . '/../plugins/generic/estrabao/AdmissionReviewService.inc.php';
if (!defined('ROLE_ID_MANAGER')) { define('ROLE_ID_MANAGER', 16); }
class DAORegistry {
    public static $roles = [];
    public static $submissions = [];
    private $name;
    function __construct($name) { $this->name = $name; }
    static function getDAO($name) { return new self($name); }
    function userHasRole($contextId, $userId, $roleId) { return $roleId === ROLE_ID_MANAGER && in_array([(int) $contextId, (int) $userId], self::$roles, true); }
    function getById($id) { return self::$submissions[$id] ?? null; }
}
function reviewRejects(callable $callback): void {
    $failed = false;
    try { $callback(); } catch (Throwable $error) { $failed = true; }
    same(true, $failed);
}
function withReview(callable $callback, bool $abnt = false): void {
    $reference = $abnt ? 'SILVA, João. Livro. São Paulo: Editora, 2020.' : 'Silva, A. (2020). Estudo da paisagem. Editora Exemplo.';
    withDocx(docxParts(docxBody(['Referências', $reference])), function ($path) use ($callback) {
        $form = admissionSetup($path);
        $form->submission->data['submissionProgress'] = 4;
        Services::$files[0]->data['id'] = 11; Services::$files[0]->data['fileId'] = 111;
        $request = Application::$request; $request->user = new AdmissionTestRecord(['id' => 77]);
        DAORegistry::$roles = [[3,77]]; DAORegistry::$submissions = [17 => $form->submission];
        $dir = sys_get_temp_dir() . '/estrabao-review-test-' . bin2hex(random_bytes(8)); mkdir($dir, 0700);
        Config::$values['estrabao']['review_dir'] = $dir;
        try { $callback(new EstrabaoAdmissionReviewService(), $request, $form, $path, $dir); }
        finally {
            foreach (scandir($dir) as $name) { if ($name !== '.' && $name !== '..') { unlink($dir . '/' . $name); } }
            rmdir($dir);
        }
    });
}
function reviewPost($service, $request, string $action = 'approve'): array {
    $view = $service->view($request, 17);
    $request->vars = ['submissionId' => '17', 'csrfToken' => 'synthetic-token', 'reviewAction' => $action,
        'apaReviewed' => '1', 'fingerprint' => $view['analysis']['fingerprint'], 'revision' => (string) $view['revision']];
    return $view;
}
test('liberação editorial inconclusiva vinculada à versão atual permite validações nativas', function () {
    withReview(function ($service, $request, $form) {
        $view = reviewPost($service, $request); same(true, $view['analysis']['reviewable']); same(false, $view['release_active']);
        same(true, $service->save($request, 17));
        $view = $service->view($request, 17); same(true, $view['release_active']); same(1, $view['revision']);
        $p = new EstrabaoPlugin(); $p->enabled = true; $value = null;
        same(false, $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]));
        same(null, $value); same([], $form->errors); // Native POST/CSRF/errors retain control.
    });
});
test('liberação não contorna uma validação nativa já inválida', function () {
    withReview(function ($service, $request, $form) {
        reviewPost($service, $request); $service->save($request,17);
        $form->addError('csrf', 'synthetic-invalid'); $value = false; $p = new EstrabaoPlugin(); $p->enabled = true;
        same(false, $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]));
        same(false, $value); same('synthetic-invalid', $form->errors['csrf']);
    });
});
test('ABNT comprovada não pode ser liberada por gerente editorial', function () {
    withReview(function ($service, $request, $form) {
        $view = reviewPost($service,$request); same('not_accepted', $view['analysis']['decision']);
        same(false,$view['analysis']['reviewable']); reviewRejects(function () use ($service,$request) { $service->save($request,17); });
        same(false,$service->allows($form,$view['analysis']));
    },true);
});
test('autor e gerente de outra revista não podem consultar nem gravar decisão', function () {
    withReview(function ($service,$request) {
        foreach ([[],[[8,77]]] as $roles) {
            DAORegistry::$roles=$roles;
            reviewRejects(function () use ($service,$request) { $service->view($request,17); });
            reviewRejects(function () use ($service,$request) { $service->save($request,17); });
        }
    });
});
test('submissão de outro contexto, inexistente ou fora da confirmação é recusada', function () {
    withReview(function ($service,$request,$form) {
        reviewRejects(function () use ($service,$request) { $service->view($request,99); });
        $form->submission->data['contextId']=8;
        reviewRejects(function () use ($service,$request) { $service->view($request,17); });
        $form->submission->data['contextId']=3; $form->submission->data['submissionProgress']=0;
        reviewRejects(function () use ($service,$request) { $service->view($request,17); });
        foreach (['../17','0',['17']] as $id) { reviewRejects(function () use ($service,$request,$id) { $service->view($request,$id); }); }
    });
});
test('POST com CSRF válido e declaração editorial são obrigatórios', function () {
    withReview(function ($service,$request) {
        foreach (['get','csrf','missing-token','declaration','action'] as $case) {
            reviewPost($service,$request); $request->post=true; $request->csrf=true;
            if($case==='get'){$request->post=false;}
            if($case==='csrf'){$request->csrf=false;}
            if($case==='missing-token'){unset($request->vars['csrfToken']);}
            if($case==='declaration'){unset($request->vars['apaReviewed']);}
            if($case==='action'){$request->vars['reviewAction']='override';}
            reviewRejects(function () use ($service,$request) { $service->save($request,17); });
        }
    });
});
test('revogação registra evento e retém conclusão novamente', function () {
    withReview(function ($service,$request,$form) {
        reviewPost($service,$request); $service->save($request,17);
        reviewPost($service,$request,'revoke'); $service->save($request,17);
        $view=$service->view($request,17); same(2,$view['revision']); same(false,$view['release_active']);
        same(['approve','revoke'],array_column($view['events'],'action'));
        same([77,77],array_column($view['events'],'reviewer_id'));
    });
});
test('decisão de aba desatualizada não sobrescreve revisão mais recente', function () {
    withReview(function ($service,$request) {
        reviewPost($service,$request); $stale=$request->vars; $service->save($request,17);
        $request->vars=$stale; reviewRejects(function () use ($service,$request) { $service->save($request,17); });
        same(1,$service->view($request,17)['revision']);
    });
});
test('arquivo alterado invalida liberação e rejeita a confirmação da aba antiga', function () {
    withReview(function ($service,$request,$form,$path) {
        reviewPost($service,$request); $old=$request->vars; $service->save($request,17);
        $zip=new ZipArchive();$zip->open($path);$zip->addFromString('synthetic-version.txt','new version');$zip->close();
        same(false,$service->view($request,17)['release_active']); $request->vars=$old;
        reviewRejects(function () use ($service,$request) { $service->save($request,17); });
    });
});
test('IDs de arquivo e idioma também invalidam liberação mesmo com bytes iguais', function () {
    withReview(function ($service,$request,$form) {
        reviewPost($service,$request);$service->save($request,17);
        Services::$files[0]->data['fileId']=112;same(false,$service->view($request,17)['release_active']);
        Services::$files[0]->data['fileId']=111; Services::$files[0]->data['id']=12;
        same(false,$service->view($request,17)['release_active']);
        Services::$files[0]->data['id']=11;$form->submission->data['locale']='en_US';
        same(false,$service->view($request,17)['release_active']);
    });
});
test('perda da função editorial invalida liberação existente', function () {
    withReview(function ($service,$request,$form) {
        reviewPost($service,$request);$service->save($request,17);DAORegistry::$roles=[];
        same(false,$service->allows($form,(new EstrabaoAdmissionBridge())->analyze($form)));
    });
});
test('documento corrompido não se torna liberável por revisão', function () {
    withReview(function ($service,$request,$form,$path) {
        file_put_contents($path,'invalid synthetic document');$view=$service->view($request,17);
        same(false,$view['analysis']['reviewable']??false);same(false,$view['release_active']);
    });
});
test('ledger privado não guarda texto do manuscrito nem caminho', function () {
    withReview(function ($service,$request,$form,$path,$dir) {
        reviewPost($service,$request);$service->save($request,17);$ledger=$dir.'/review-3-17.json';
        $raw=file_get_contents($ledger);same(false,strpos($raw,'Silva')!==false);same(false,strpos($raw,'paisagem')!==false);
        same(false,strpos($raw,$path)!==false);same(0600,fileperms($ledger)&0777);
    });
});
test('ledger corrompido ou de outra submissão falha fechado', function () {
    withReview(function ($service,$request,$form,$path,$dir) {
        reviewPost($service,$request);$service->save($request,17);$ledger=$dir.'/review-3-17.json';
        foreach (['invalid',json_encode(['schema_version'=>1,'context_id'=>8,'submission_id'=>17,'events'=>[]])] as $bad) {
            file_put_contents($ledger,$bad);
            reviewRejects(function () use ($service,$request) { $service->view($request,17); });
            $p=new EstrabaoPlugin();$p->enabled=true;$value=null;
            same(false,$p->validateAdmission('submissionsubmitstep4form::validate',[$form,&$value]));same(false,$value);
        }
    });
});
test('download exige gerente e fingerprint da versão atual, sem caminho do cliente', function () {
    withReview(function ($service,$request) {
        reviewPost($service,$request);Services::$downloads=[];$service->download($request,17);
        same([[111,'manuscrito.docx',false]],Services::$downloads);
        $request->vars['fingerprint']=str_repeat('0',64);
        reviewRejects(function () use ($service,$request) {$service->download($request,17);});
        DAORegistry::$roles=[];
        reviewRejects(function () use ($service,$request) {$service->download($request,17);});
    });
});
test('diretório privado ausente ou permissivo não permite liberação', function () {
    withReview(function ($service,$request,$form,$path,$dir) {
        chmod($dir,0755);clearstatcache();
        reviewRejects(function () use ($service,$request) {$service->view($request,17);});
        chmod($dir,0700);Config::$values['estrabao']['review_dir']=null;
        reviewRejects(function () use ($service,$request) {$service->view($request,17);});
    });
});
test('trava ocupada não permite sobrescrita concorrente', function () {
    withReview(function ($service,$request,$form,$path,$dir) {
        reviewPost($service,$request);$h=fopen($dir.'/review-3-17.json.lock','c+b');flock($h,LOCK_EX);
        try {reviewRejects(function () use ($service,$request) {$service->save($request,17);});}
        finally{flock($h,LOCK_UN);fclose($h);}
    });
});

test('histórico limitado reserva espaço para revogação sem apagar decisões anteriores', function () {
    withReview(function ($service,$request,$form,$path,$dir) {
        reviewPost($service,$request);$service->save($request,17);$file=$dir.'/review-3-17.json';
        $ledger=json_decode(file_get_contents($file),true);$event=$ledger['events'][0];
        for($i=2;$i<=99;$i++){$next=$event;$next['sequence']=$i;$ledger['events'][]=$next;}
        file_put_contents($file,json_encode($ledger));
        reviewPost($service,$request);reviewRejects(function () use ($service,$request) {$service->save($request,17);});
        reviewPost($service,$request,'revoke');$service->save($request,17);$view=$service->view($request,17);
        same(100,$view['revision']);same(false,$view['release_active']);same('revoke',$view['events'][99]['action']);
    });
});
test('submissões e revistas não compartilham a mesma liberação', function () {
    withReview(function ($service,$request,$form) {
        reviewPost($service,$request);$service->save($request,17);
        $other=clone $form;$other->submissionId=18;
        same(false,$service->allows($other,(new EstrabaoAdmissionBridge())->analyze($form)));
        $other=clone $form;$other->context=new AdmissionTestRecord(['id'=>8]);
        same(false,$service->allows($other,(new EstrabaoAdmissionBridge())->analyze($form)));
    });
});

class Handler {
    function authorize($request, &$args, $roleAssignments) { return true; }
    function setupTemplate($request) {}
}
class AdmissionTestRouter {
    function url($request,$context,$page,$op,$args=null,$params=[]) { return '/synthetic/' . $page . '/' . $op . ($params ? '?' . http_build_query($params) : ''); }
}
class TemplateManager {
    public static $assigned=[];
    public static $template;
    static function getManager($request) { return new self(); }
    function assign($key,$value) { self::$assigned[$key]=$value; }
    function display($template) { self::$template=$template; }
}
class LinkAction {
    public $id;
    function __construct($id,$request,$title) {$this->id=$id;}
}
class RedirectAction {
    function __construct($url) {}
}
require_once __DIR__ . '/../plugins/generic/estrabao/AdmissionReviewHandler.inc.php';
test('rota de revisão é explícita e não expõe operações auxiliares', function () {
    withReview(function ($service,$request) {
        $plugin=new EstrabaoPlugin();$plugin->enabled=true;
        same(false,$plugin->loadReviewHandler('LoadHandler',['otherPage','review',null]));
        same(false,$plugin->loadReviewHandler('LoadHandler',['estrabaoAdmission','authorize',null]));
        same(true,$plugin->loadReviewHandler('LoadHandler',['estrabaoAdmission','review',null]));
        same('EstrabaoAdmissionReviewHandler',HANDLER_CLASS);
        same($plugin,EstrabaoAdmissionReviewHandler::$plugin);
        $plugin->enabled=false;
        same(false,$plugin->loadReviewHandler('LoadHandler',['estrabaoAdmission','saveReview',null]));
    });
});
test('handler rejeita autor e entrega modelo de revisão somente ao gerente', function () {
    withReview(function ($service,$request) {
        $handler=new EstrabaoAdmissionReviewHandler();$args=[];DAORegistry::$roles=[];
        same(false,$handler->authorize($request,$args,[]));DAORegistry::$roles=[[3,77]];
        same(true,$handler->authorize($request,$args,[]));$request->vars=['submissionId'=>'17'];
        $handler->review([],$request);same(17,TemplateManager::$assigned['reviewModel']['submission_id']);
        same('synthetic:admissionReview.tpl',TemplateManager::$template);
        same(true,strpos(TemplateManager::$assigned['downloadReviewUrl'],'fingerprint=')!==false);
    });
});
test('handler grava decisão válida e não anuncia sucesso em POST inválido', function () {
    withReview(function ($service,$request) {
        $handler=new EstrabaoAdmissionReviewHandler();reviewPost($service,$request);$handler->saveReview([],$request);
        same('plugins.generic.estrabao.reviewSaved',TemplateManager::$assigned['reviewMessage']);
        same(true,TemplateManager::$assigned['reviewModel']['release_active']);
        $request->csrf=false;$handler->saveReview([],$request);
        same('plugins.generic.estrabao.reviewNotSaved',TemplateManager::$assigned['reviewMessage']);
        same(1,TemplateManager::$assigned['reviewModel']['revision']);
    });
});
test('plugins mostram entrada para revisão somente quando integração está habilitada', function () {
    withReview(function ($service,$request) {
        $plugin=new EstrabaoPlugin();same([],$plugin->getActions($request,[]));$plugin->enabled=true;
        $actions=$plugin->getActions($request,[]);same(1,count($actions));same('admissionReview',$actions[0]->id);
        Config::$values['estrabao']['admission_gate']=false;same([],$plugin->getActions($request,[]));
        reviewRejects(function () use ($service,$request) {$service->view($request,17);});
    });
});

test('revogação continua possível com arquivo ilegível e não revive ao restaurar bytes', function () {
    withReview(function ($service,$request,$form,$path) {
        reviewPost($service,$request);$service->save($request,17);$original=file_get_contents($path);
        file_put_contents($path,'invalid synthetic replacement');$view=$service->view($request,17);
        same(false,$view['release_active']);
        $request->vars=['csrfToken'=>'synthetic-token','reviewAction'=>'revoke','revision'=>(string)$view['revision'],
            'fingerprint'=>$view['revocation_fingerprint']];
        $service->save($request,17);file_put_contents($path,$original);
        same(false,$service->view($request,17)['release_active']);
    });
});
