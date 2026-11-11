<?php
// Dublê unitário, não simula bootstrap, banco, instalação ou autorização OJS.
function import($path) {
    if ($path !== 'lib.pkp.classes.plugins.GenericPlugin') {
        throw new RuntimeException('Import inesperado.');
    }
}
function __($key) { return $key; }
abstract class GenericPlugin {
    function getName() { return strtolower(get_class($this)); }
    abstract function getDisplayName();
    abstract function getDescription();
}
