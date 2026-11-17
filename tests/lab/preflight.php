<?php
// Read-only assertion of the exact synthetic lab, never a production probe.
if (getenv('ESTRABAO_SYNTHETIC_LAB') !== '1') { exit(1); }
chdir('/var/www/html'); $all=parse_ini_file('config.inc.php',true);
if (($all['general']['base_url']??null)!=='http://127.0.0.1:18080'
    || ($all['database']['name']??null)!=='estrabao_synthetic') { exit(1); }
$c=$all['database'];$db=new PDO('mysql:host='.$c['host'].';dbname='.$c['name'],$c['username'],$c['password']);
$rows=$db->query("SELECT product,major,minor,revision,build FROM versions WHERE current=1 AND product IN ('ojs2','estrabao')")->fetchAll(PDO::FETCH_ASSOC);
$versions=[];foreach($rows as$row){$versions[$row['product']]=implode('.',[$row['major'],$row['minor'],$row['revision'],$row['build']]);}
require '/opt/estrabao/src/bootstrap.php';
$plugin=(string)simplexml_load_file('plugins/generic/estrabao/version.xml')->release;
$root=realpath($all['files']['files_dir']??'');$review=realpath($all['estrabao']['review_dir']??'');
$checks=[
 'ojs_3_3_0_20'=>($versions['ojs2']??null)==='3.3.0.20',
 'plugin_installed_matches_files'=>($versions['estrabao']??null)===$plugin,
 'smtp_disabled'=>in_array($all['email']['smtp']??null,[null,false,'','0',0],true),
 'sendmail_neutralized'=>ini_get('sendmail_path')==='/bin/true',
 'admission_gate_enabled'=>($all['estrabao']['admission_gate']??null)==='1',
 'engine_bootstrap_matches_private_mount'=>realpath($all['estrabao']['engine_bootstrap']??'')===realpath('/opt/estrabao/src/bootstrap.php'),
 'private_files_outside_webroot'=>$root&&$root!==realpath('/var/www/html')&&strpos($root,realpath('/var/www/html').'/')!==0,
 'private_review_directory'=>$root&&$review&&strpos($review,$root.'/')===0&&(fileperms($review)&0077)===0,
];
echo json_encode(['checks'=>$checks,'runtime'=>['php'=>PHP_VERSION,'ojs'=>$versions['ojs2']??null,
 'installed_plugin'=>$versions['estrabao']??null,'plugin_files'=>$plugin,'engine'=>\Estrabao\Reporting\Report::ENGINE_VERSION]],JSON_PRETTY_PRINT);
exit(in_array(false,$checks,true)?1:0);
