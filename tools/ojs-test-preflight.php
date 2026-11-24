<?php
/** Read-only CLI check before a single production test. Run from the OJS root. */
if (PHP_SAPI !== 'cli') { exit(1); }
if (!is_file('tools/bootstrap.inc.php')) { exit("Execute a partir da raiz do OJS.\n"); }
require 'tools/bootstrap.inc.php';
new CommandLineTool([]);
require_once 'plugins/generic/estrabao/TestScope.inc.php';
$failed = 0;
function preflight($name, $ok) {
    global $failed;
    if (!$ok) { $failed++; }
    echo ($ok ? 'OK ' : 'FALHA ') . $name . "\n";
}
echo 'PHP: ' . PHP_VERSION . "\n";
preflight('PHP 8.2 do servidor de destino', PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 2);
foreach (['zip','dom','mbstring','intl','fileinfo','openssl','pdo_mysql','mysqli','gettext'] as $extension) {
    preflight('extensão ' . $extension, extension_loaded($extension));
}
$root = realpath(getcwd());
$bootstrap = Config::getVar('estrabao', 'engine_bootstrap');
$engine = is_string($bootstrap) ? realpath($bootstrap) : false;
preflight('motor legível e fora da raiz web', $engine && is_readable($engine) && strpos($engine, $root . DIRECTORY_SEPARATOR) !== 0);
$files = realpath(Config::getVar('files', 'files_dir'));
preflight('arquivos editoriais fora da raiz web', $files && strpos($files, $root . DIRECTORY_SEPARATOR) !== 0);
$review = Config::getVar('estrabao', 'review_dir');
$private = is_string($review) ? realpath($review) : false;
preflight('diretório de revisão privado e gravável', $private && $files && !is_link($review)
    && strpos($private, $files . DIRECTORY_SEPARATOR) === 0 && is_writable($private) && (fileperms($private) & 0077) === 0);
preflight('escopo de teste configurado', EstrabaoTestScope::active() && EstrabaoTestScope::id() > 0);
preflight('destinatário do teste', strtolower(EstrabaoTestScope::recipient() ?? '') === 'eduwerneck@gmail.com');
$j = Application::getContextDAO()->getByPath('estrabao');
$id = EstrabaoTestScope::id();
$s = $id ? DAORegistry::getDAO('SubmissionDAO')->getById($id) : null;
preflight('rascunho da revista Estrabão', $j && $s && (int)$s->getContextId() === (int)$j->getId()
    && (int)$s->getSubmissionProgress() >= 1 && (int)$s->getSubmissionProgress() <= 4 && (int)$s->getStatus() === STATUS_QUEUED);
$editorId = (int)Config::getVar('estrabao', 'automation_editor_id');
$editor = $editorId ? DAORegistry::getDAO('UserDAO')->getById($editorId) : null;
preflight('responsável editorial ativo', $j && $editor && !$editor->getDisabled()
    && DAORegistry::getDAO('RoleDAO')->userHasRole($j->getId(), $editorId, ROLE_ID_MANAGER));
$authorsOk = false;
if ($s) {
    $authors = $s->getAuthors(); $authorsOk = count($authors) > 0;
    foreach ($authors as $author) {
        $authorsOk = $authorsOk && in_array(strtolower($author->getEmail()), ['eduwerneck@gmail.com','revista@estrabao.press'], true);
    }
}
preflight('autores somente em caixas controladas', $authorsOk);
echo 'Escopo: ' . $id . "; destinatário: eduwerneck@gmail.com\n";
echo 'Este comando não ativa a automação, não altera dados e não envia mensagens.' . "\n";
exit($failed ? 1 : 0);
