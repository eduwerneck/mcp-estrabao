<?php
// Isolated synthetic lab only. Temporarily expose citations to the author; never edit references.
if (getenv('ESTRABAO_SYNTHETIC_LAB')!=='1') { exit(1); }
chdir('/var/www/html');$all=parse_ini_file('config.inc.php',true);
if (($all['general']['base_url']??null)!=='http://127.0.0.1:18080'||($all['database']['name']??null)!=='estrabao_synthetic') { exit(1); }
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$journal=Application::getContextDAO()->getByPath('estrabao-lab');if(!$journal){exit(1);}$id=(int)$journal->getId();
$c=$all['database'];$db=new PDO('mysql:host='.$c['host'].';dbname='.$c['name'],$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$backup='/tmp/estrabao-stage43-citations-setting.json';$mode=$argv[1]??'';
$settings=$db->query("SELECT * FROM journal_settings WHERE journal_id=$id AND setting_name='citations' ORDER BY locale")->fetchAll(PDO::FETCH_ASSOC);
if($mode==='state'){
 echo json_encode(['setting'=>$settings,'metadata'=>$db->query("SELECT * FROM publication_settings WHERE setting_name='citationsRaw' ORDER BY publication_id,locale")->fetchAll(PDO::FETCH_ASSOC),'citations'=>$db->query('SELECT * FROM citations ORDER BY citation_id')->fetchAll(PDO::FETCH_ASSOC)]);exit;
}
if(!in_array($mode,['enable','restore'],true)){exit(1);}
if($mode==='restore'&&!is_file($backup)){exit;}
if($mode==='enable'){
 if(is_file($backup)){exit(1);}file_put_contents($backup,json_encode(['journal_id'=>$id,'rows'=>$settings]));chmod($backup,0600);
 $rows=[['journal_id'=>$id,'locale'=>'','setting_name'=>'citations','setting_value'=>'request']];
 $columns=array_column($db->query('SHOW COLUMNS FROM journal_settings')->fetchAll(PDO::FETCH_ASSOC),'Field');
 if(in_array('setting_type',$columns,true)){$rows[0]['setting_type']='string';}
}else{
 $data=json_decode(file_get_contents($backup),true);if(($data['journal_id']??null)!==$id){exit(1);}$rows=$data['rows'];
}
$db->beginTransaction();$db->exec("DELETE FROM journal_settings WHERE journal_id=$id AND setting_name='citations'");
foreach($rows as$row){$cols=array_keys($row);$db->prepare('INSERT INTO journal_settings (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}
$db->commit();if($mode==='restore'){unlink($backup);}
