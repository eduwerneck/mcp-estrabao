<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}
chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true);
if(($c['general']['base_url']??null)!=='http://127.0.0.1:18080'||($c['database']['name']??null)!=='estrabao_synthetic'){exit(1);}
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$state=json_decode(file_get_contents('/tmp/lab-state.json'),true);$kind=$argv[2]??'review';if(!in_array($kind,['review','revoke','replace','csrf'],true)){exit(1);}
$record=$state['submissions'][$kind];$id=(int)$record['id'];$s=Services::get('submission')->get($id);
if(!$s||$s->getSubmissionProgress()!=4||$s->getData('dateSubmitted')){exit(1);}
// Fresh drafts in this runner are explicitly recorded; never modify original lab submissions.
$allowed=json_decode(file_get_contents('/tmp/estrabao-doc-fresh-ids.json'),true);
if(!is_array($allowed)||!in_array($id,$allowed,true)){exit(1);}
$f=Services::get('submissionFile')->get($record['submission_file_id']);if(!$f||(int)$f->getData('submissionId')!==$id){exit(1);}
$mode=$argv[1]??'';$files=Config::getVar('files','files_dir');$root=Config::getVar('estrabao','review_dir');
if($mode==='inspect'){
 $path=$files.'/'.$f->getData('path');$ledger=$root.'/review-'.$s->getContextId().'-'.$id.'.json';$events=is_file($ledger)?json_decode(file_get_contents($ledger),true)['events']:[];
 echo json_encode(['sha256'=>hash_file('sha256',$path),'ledger_sha256'=>is_file($ledger)?hash_file('sha256',$ledger):null,'modes'=>array_column($events,'review_mode'),'events'=>count($events),'private_mode'=>!is_file($ledger)||(fileperms($ledger)&0077)===0]);exit;
}
if(!in_array($mode,['install','changed','encrypted'],true)){exit(1);}
$bytes=file_get_contents('/tmp/estrabao-synthetic.doc');
if($mode==='changed'){$bytes[strlen($bytes)-1]=chr(ord($bytes[strlen($bytes)-1])^1);}
if($mode==='encrypted'){
 $pos=strpos($bytes,"W\0o\0r\0d\0D\0o\0c\0u\0m\0e\0n\0t\0");if($pos===false){exit(1);}
 $start=(unpack('V',substr($bytes,$pos+116,4))[1]+1)*512;$flags=unpack('v',substr($bytes,$start+10,2))[1];$bytes=substr_replace($bytes,pack('v',$flags|0x100),$start+10,2);
}
$temp='/tmp/estrabao-doc-upload.doc';file_put_contents($temp,$bytes);chmod($temp,0600);
$relative=dirname($f->getData('path')).'/synthetic-'.$mode.'-'.bin2hex(random_bytes(4)).'.doc';
$fid=Services::get('file')->add($temp,$relative);$f->setData('fileId',$fid);DAORegistry::getDAO('SubmissionFileDAO')->updateObject($f);unlink($temp);
echo "Synthetic DOC version installed\n";
