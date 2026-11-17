<?php
// Read-only profile of this exact synthetic instance; no production endpoint.
if (getenv('ESTRABAO_SYNTHETIC_LAB')!=='1') { exit(1); }
chdir('/var/www/html');$config=parse_ini_file('config.inc.php',true);
if(($config['general']['base_url']??null)!=='http://127.0.0.1:18080'||($config['database']['name']??null)!=='estrabao_synthetic'){exit(1);}
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$context=Application::getContextDAO()->getByPath('estrabao-lab');
if(!$context){exit(1);}
$settings=DAORegistry::getDAO('PluginSettingsDAO');
$font=$settings->getSetting($context->getId(),'defaultthemeplugin','typography');
$color=$settings->getSetting($context->getId(),'defaultthemeplugin','baseColour');
function hasStyle($value){if(is_array($value)){foreach($value as$v){if(hasStyle($v)){return true;}}return false;}return $value!==null&&$value!==false&&$value!=='';}
echo json_encode(['theme_setting'=>$context->getData('themePluginPath'),
 'typography'=>$font===null||$font===false?'notoSans':$font,
 'header_color'=>$color===null||$color===false?'#1E6292':$color,
 'additional_css_present'=>hasStyle($context->getData('styleSheet')),
 'lab_theme_version'=>(string)simplexml_load_file('plugins/themes/default/version.xml')->release,
 'lab_article_template_sha256'=>hash_file('sha256','templates/frontend/objects/article_details.tpl')],JSON_PRETTY_PRINT);
