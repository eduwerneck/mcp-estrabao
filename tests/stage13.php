<?php
declare(strict_types=1);

// Only the methods used by the adapter; no OJS installation, database or mailer.
class Config {
    public static $values = [];
    static function getVar($section, $key) { return self::$values[$section][$key] ?? null; }
}
class HookRegistry {
    public static $hooks = [];
    static function register($name, $callback) { self::$hooks[$name] = $callback; }
}
class Application {
    public static $request;
    static function get() { return new self(); }
    function getRequest() { return self::$request; }
}
class Services {
    public static $files = [];
    public static $query;
    public static $downloads = [];
    static function get($name) { if (!in_array($name, ['submissionFile', 'file'], true)) { throw new RuntimeException('service'); } return new self(); }
    function download($id, $filename, $inline = false) { self::$downloads[] = [$id, $filename, $inline]; }
    function getMany($args) { self::$query = $args; return new ArrayIterator(self::$files); }
}
class AdmissionTestRecord {
    public $data;
    function __construct(array $data) { $this->data = $data; }
    function getData($key) { return $this->data[$key] ?? null; }
    function getId() { return $this->getData('id'); }
    function getContextId() { return $this->getData('contextId'); }
    function getLocale() { return $this->getData('locale'); }
    function getSubmissionProgress() { return $this->getData('submissionProgress'); }
}
class AdmissionTestForm {
    public $submission;
    public $context;
    public $submissionId = 17;
    public $step = 4;
    public $errors = [];
    function __construct() {
        $this->context = new AdmissionTestRecord(['id' => 3]);
        $this->submission = new AdmissionTestRecord(['id' => 17, 'contextId' => 3, 'locale' => 'pt_BR']);
    }
    function addError($field, $message) { $this->errors[$field] = $message; }
}
class AdmissionTestRequest {
    public $context;
    public $user = true;
    public $vars = [];
    public $post = true;
    public $csrf = true;
    function getRouter() { return new AdmissionTestRouter(); }
    function isPost() { return $this->post; }
    function checkCSRF() { return $this->csrf; }
    function getUserVar($key) { return $this->vars[$key] ?? null; }
    function getContext() { return $this->context; }
    function getUser() { return $this->user; }
}
if (!defined('SUBMISSION_FILE_SUBMISSION')) { define('SUBMISSION_FILE_SUBMISSION', 2); }
require_once __DIR__ . '/../plugins/generic/estrabao/AdmissionBridge.inc.php';
function admissionSetup($path): AdmissionTestForm {
    $form = new AdmissionTestForm();
    Application::$request = new AdmissionTestRequest(); Application::$request->context = $form->context;
    Config::$values = ['estrabao' => ['engine_bootstrap' => __DIR__ . '/../src/bootstrap.php', 'admission_gate' => true], 'files' => ['files_dir' => dirname($path)]];
    Services::$files = [new AdmissionTestRecord(['submissionId' => 17, 'fileStage' => SUBMISSION_FILE_SUBMISSION, 'path' => basename($path)])];
    return $form;
}
function withAdmissionDoc(callable $callback): void {
    withDocx(docxParts(docxBody(['Referências', 'SILVA, João. Livro. São Paulo: Editora, 2020.'])), $callback);
}
test('hook só é registrado com plugin e configuração explicitamente habilitados', function () {
    $p = new EstrabaoPlugin(); HookRegistry::$hooks = []; Config::$values = ['estrabao' => ['admission_gate' => true]];
    same(true, $p->register('generic', 'synthetic', 3)); same([], HookRegistry::$hooks);
    $p->enabled = true; Config::$values = []; $p->register('generic', 'synthetic', 3); same([], HookRegistry::$hooks);
    Config::$values = ['estrabao' => ['admission_gate' => true]]; $p->register('generic', 'synthetic', 3);
    same(true, isset(HookRegistry::$hooks['submissionsubmitstep4form::validate']));
    $p->registrationResult = false; same(false, $p->register('generic', 'synthetic', 3));
});
test('adaptador resolve arquivo atual no servidor e detecta ABNT', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); same('not_accepted', (new EstrabaoAdmissionBridge())->inspect($form));
        same([17], Services::$query['submissionIds']); same([SUBMISSION_FILE_SUBMISSION], Services::$query['fileStages']);
        same(false, Services::$query['includeDependentFiles']);
    });
});
test('hook retorna validação falsa antes de executar conclusão e aviso de recebimento', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); $value = null; $executed = false;
        $p = new EstrabaoPlugin(); $p->enabled = true; same(false, $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]));
        if ($value) { $executed = true; }
        same(false, $value); same(false, $executed);
        same('plugins.generic.estrabao.apaNotAccepted', $form->errors['estrabaoAdmission']);
    });
});
test('contexto, identidade da submissão e autenticação são conferidos', function () {
    withAdmissionDoc(function ($path) {
        foreach (['journal', 'submission', 'user', 'step'] as $case) {
            $form = admissionSetup($path);
            if ($case === 'journal') { $form->context = new AdmissionTestRecord(['id' => 8]); }
            if ($case === 'submission') { $form->submissionId = 99; }
            if ($case === 'user') { Application::$request->user = null; }
            if ($case === 'step') { $form->step = 2; }
            same('manual_review_required', (new EstrabaoAdmissionBridge())->inspect($form));
        }
    });
});
test('sem arquivo, vários anexos ou registro de outra submissão não há seleção arbitrária', function () {
    withAdmissionDoc(function ($path) {
        foreach (['none', 'many', 'foreign', 'stage'] as $case) {
            $form = admissionSetup($path);
            if ($case === 'none') { Services::$files = []; }
            if ($case === 'many') { Services::$files[] = Services::$files[0]; }
            if ($case === 'foreign') { Services::$files[0]->data['submissionId'] = 99; }
            if ($case === 'stage') { Services::$files[0]->data['fileStage'] = 99; }
            same('manual_review_required', (new EstrabaoAdmissionBridge())->inspect($form));
        }
    });
});
test('caminhos externos e travessia não são lidos', function () {
    withAdmissionDoc(function ($path) {
        foreach ([$path, '../' . basename($path), 'https://invalid.example/private', 'file://' . $path, "bad\0path", 'C:\\private.docx'] as $bad) {
            $form = admissionSetup($path); Services::$files[0]->data['path'] = $bad;
            same('manual_review_required', (new EstrabaoAdmissionBridge())->inspect($form));
        }
    });
});
test('falha técnica ou motor ausente mantém pendência sem expor dados', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); Config::$values['estrabao']['engine_bootstrap'] = '/unavailable/private/bootstrap.php';
        $value = true; $p = new EstrabaoPlugin(); $p->enabled = true; $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]);
        same(false, $value); same('plugins.generic.estrabao.apaReviewRequired', $form->errors['estrabaoAdmission']);
        same(false, strpos(json_encode($form->errors), 'private') !== false);
        $form = admissionSetup($path); Application::$request = null; $value = true;
        $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]); same(false, $value);
    });
});
test('nova versão do manuscrito é analisada novamente sem reutilizar decisão anterior', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); $bridge = new EstrabaoAdmissionBridge(); same('not_accepted', $bridge->inspect($form));
        file_put_contents($path, 'invalid synthetic replacement');
        same('manual_review_required', $bridge->inspect($form));
    });
});

test('callback não interfere no contexto em que plugin está desabilitado', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); $value = null; $p = new EstrabaoPlugin();
        same(false, $p->validateAdmission('submissionsubmitstep4form::validate', [$form, &$value]));
        same(null, $value); same([], $form->errors);
    });
});
test('link para fora do diretório privado é recusado', function () {
    withAdmissionDoc(function ($path) {
        $form = admissionSetup($path); $dir = sys_get_temp_dir() . '/estrabao-synthetic-root-' . bin2hex(random_bytes(8));
        mkdir($dir, 0700);
        try {
            symlink($path, $dir . '/manuscript.docx');
            Config::$values['files']['files_dir'] = $dir; Services::$files[0]->data['path'] = 'manuscript.docx';
            same('manual_review_required', (new EstrabaoAdmissionBridge())->inspect($form));
        } finally { unlink($dir . '/manuscript.docx'); rmdir($dir); }
    });
});
