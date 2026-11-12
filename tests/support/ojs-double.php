<?php
// Dublê unitário, não simula bootstrap, banco, instalação ou autorização OJS.
function import($path) {
    if (!in_array($path, ['lib.pkp.classes.plugins.GenericPlugin', 'classes.handler.Handler', 'lib.pkp.classes.linkAction.LinkAction', 'lib.pkp.classes.linkAction.request.RedirectAction'], true)) {
        throw new RuntimeException('Import inesperado.');
    }
}
function __($key) { return $key; }
abstract class GenericPlugin {
    public $enabled = false;
    public $registrationResult = true;
    function register($category, $path, $mainContextId = null) { return $this->registrationResult; }
    function getEnabled($contextId = null) { return $this->enabled; }
    function getTemplateResource($template) { return 'synthetic:' . $template; }
    function getActions($request, $actionArgs) { return []; }
    function getName() { return strtolower(get_class($this)); }
    abstract function getDisplayName();
    abstract function getDescription();
}
