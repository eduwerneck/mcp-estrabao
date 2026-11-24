<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true);if(($c['database']['name']??'')!=='estrabao_synthetic'||($c['database']['host']??'')!=='estrabao-lab-62-restore-db'){exit(1);}require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$id=(int)($argv[1]??0);$s=DAORegistry::getDAO('SubmissionDAO')->getById($id);if(!$s||$id<=0){exit(1);}
require '/var/www/html/plugins/generic/estrabao/AutomaticTriage.inc.php';
$state=EstrabaoAutomaticTriage::state($id);$d=DAORegistry::getDAO('EditDecisionDAO')->getEditorDecisions($id);
$db=Illuminate\Database\Capsule\Manager::connection();$email=$db->table('email_log')->where('assoc_id',$id)->where('subject','like','[TESTE HOMOLOGAÇÃO]%')->first();
echo json_encode(['status'=>(int)$s->getStatus(),'progress'=>(int)$s->getSubmissionProgress(),'decisions'=>count($d),'marker'=>$state!==null,'controlled_recipient'=>$email&&trim($email->recipients)==='scope-only@synthetic.invalid','test_subject'=>$email!==null]);
