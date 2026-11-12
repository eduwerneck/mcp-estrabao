<?php
// Dublê unitário, não simula bootstrap, banco, instalação ou autorização OJS.
function import($path) {
    if ($path !== 'lib.pkp.classes.plugins.GenericPlugin') {
        throw new RuntimeException('Import inesperado.');
    }
}
function __($key) { return $key; }
abstract class GenericPlugin {
    public $enabled = false;
    public $registrationResult = true;
    function register($category, $path, $mainContextId = null) { return $this->registrationResult; }
    function getEnabled($contextId = null) { return $this->enabled; }
    function getName() { return strtolower(get_class($this)); }
    abstract function getDisplayName();
    abstract function getDescription();
}
