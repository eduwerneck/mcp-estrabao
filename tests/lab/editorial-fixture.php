<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}
chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true);
if(($c['general']['base_url']??null)!=='http://127.0.0.1:18080' || ($c['database']['name']??null)!=='estrabao_synthetic'){exit(1);}
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$state=json_decode(file_get_contents('/tmp/lab-state.json'),true);$id=(int)$state['submissions']['abnt']['id'];
$submission=Services::get('submission')->get($id);if(!$submission || $submission->getSubmissionProgress()!=4){exit(1);}
$pub=$submission->getCurrentPublication();$root=realpath(Config::getVar('estrabao','review_dir'));$files=realpath(Config::getVar('files','files_dir'));
if(!$root || !$files || strpos($root,$files.'/')!==0 || (fileperms($root)&0077)!==0){exit(1);}
$path=$root.'/editorial-'.(int)$submission->getContextId().'-'.$id.'.json';$apa=$root.'/review-'.(int)$submission->getContextId().'-'.$id.'.json';
if(is_link($path)){exit(1);}
$backup='/tmp/estrabao-editorial-backup.json';$mode=$argv[1]??'';
if($mode==='backup'){
 if(is_file($backup)){exit(1);}
 $b=['submission_id'=>$id,'title'=>$pub->getData('title'),'ledger'=>is_file($path)?base64_encode(file_get_contents($path)):null];
 file_put_contents($backup,json_encode($b,JSON_THROW_ON_ERROR));chmod($backup,0600);echo "Synthetic review backup created\n";exit;
}
if(in_array($mode,['change','reset-metadata','restore','corrupt'],true)){
 if(!is_file($backup)){exit(1);}$b=json_decode(file_get_contents($backup),true);if($b['submission_id']!==$id){exit(1);}
 if($mode==='change'){$title=$b['title'];$title['es_ES']='Metadato sintético modificado';$pub->setData('title',$title);DAORegistry::getDAO('PublicationDAO')->updateObject($pub);}
 if($mode==='reset-metadata' || $mode==='restore'){
  if(!array_key_exists('es_ES',$b['title'])){DAORegistry::getDAO('PublicationDAO')->update('DELETE FROM publication_settings WHERE publication_id = ? AND setting_name = ? AND locale = ?',[(int)$pub->getId(),'title','es_ES'],false);}
  $pub->setData('title',$b['title']);DAORegistry::getDAO('PublicationDAO')->updateObject($pub);
 }
 if($mode==='corrupt'){file_put_contents($path,'{"schema_version":0}');chmod($path,0600);}
 if($mode==='restore'){
  if($b['ledger']===null){if(is_file($path)){unlink($path);}}else{file_put_contents($path,base64_decode($b['ledger'],true));chmod($path,0600);}
  unlink($backup);
 }
 echo "Synthetic review fixture changed\n";exit;
}
if($mode!=='inspect'){exit(1);}
$raw=is_file($path)?file_get_contents($path):null;$l=$raw===null?null:json_decode($raw,true);
echo json_encode(['metadata_sha256'=>hash('sha256',json_encode($pub->getData('title'))),'ledger_sha256'=>$raw===null?null:hash('sha256',$raw),
 'events'=>$l['events']??[],'apa_sha256'=>is_file($apa)?hash_file('sha256',$apa):null,'private_mode'=>$raw===null || (fileperms($path)&0077)===0]);
