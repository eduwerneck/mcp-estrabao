<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true);if(($c['database']['name']??'')!=='estrabao_synthetic'||($c['database']['host']??'')!=='estrabao-lab-62-restore-db'){exit(1);}require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$id=(int)($argv[1]??0);$s=DAORegistry::getDAO('SubmissionDAO')->getById($id);if(!$s||$id<=0){exit(1);}
require '/var/www/html/plugins/generic/estrabao/AutomaticTriage.inc.php';
$state=EstrabaoAutomaticTriage::state($id);$d=DAORegistry::getDAO('EditDecisionDAO')->getEditorDecisions($id);
$db=Illuminate\Database\Capsule\Manager::connection();
$emails=$db->table('email_log')->where('assoc_id',$id)->where('subject','like','Submissão rejeitada%')->get();
echo json_encode(['decisions'=>count($d),'editor_matches'=>count($d)===1&&(int)$d[0]['editorId']===(int)Config::getVar('estrabao','automation_editor_id'),'mail_logged'=>count($emails)===1,'mail_state'=>$state['mail']??null,'editor_tasks'=>(int)$db->table('notifications')->where('assoc_id',$id)->where('user_id',(int)Config::getVar('estrabao','automation_editor_id'))->count()]);
