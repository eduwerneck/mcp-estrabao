<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}chdir('/var/www/html');
$c=parse_ini_file('config.inc.php',true);if(($c['general']['base_url']??'')!=='http://127.0.0.1:18080'||($c['database']['name']??'')!=='estrabao_synthetic'){exit(1);}
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$db=new PDO('mysql:host='.$c['database']['host'].';dbname='.$c['database']['name'],$c['database']['username'],$c['database']['password']);
$versions=$db->query("SELECT product,major,minor,revision,build,current FROM versions WHERE product IN ('estrabao','ojs2') ORDER BY product,major,minor,revision,build")->fetchAll(PDO::FETCH_ASSOC);
$ledgers=[];foreach(glob('/var/www/files/estrabao-review/*.json')as$p){$ledgers[basename($p)]=hash_file('sha256',$p);}
$mode=$argv[1]??'inspect';$state=json_decode(file_get_contents('/tmp/lab-state.json'),true);
if($mode==='enable'||$mode==='disable'){DAORegistry::getDAO('PluginSettingsDAO')->updateSetting($state['journal_id'],'estrabaoplugin','enabled',$mode==='enable','bool');}
echo json_encode(['versions'=>$versions,'ledgers'=>$ledgers,'enabled'=>DAORegistry::getDAO('PluginSettingsDAO')->getSetting($state['journal_id'],'estrabaoplugin','enabled'),'submissions'=>$db->query('SELECT submission_id,submission_progress,date_submitted FROM submissions ORDER BY submission_id')->fetchAll(PDO::FETCH_ASSOC),'extensions'=>['dom'=>extension_loaded('dom'),'zip'=>extension_loaded('zip'),'pdo_mysql'=>extension_loaded('pdo_mysql')],'php'=>PHP_VERSION]);
