<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1')exit(1);
chdir('/var/www/html');$labConfig=parse_ini_file('config.inc.php',true);if(($labConfig['general']['base_url']??null)!=='http://127.0.0.1:18080'||($labConfig['database']['name']??null)!=='estrabao_synthetic'){exit(1);}require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$s=json_decode(file_get_contents('/tmp/lab-state.json'),true)['submissions']['replace'];
$f=Services::get('submissionFile')->get($s['submission_file_id']);$source=Config::getVar('files','files_dir').'/'.$f->getData('path');
$temp='/tmp/replacement-synthetic.docx';copy($source,$temp);$z=new ZipArchive();$z->open($temp);$z->addFromString('synthetic-version.txt','new version');$z->close();
$relative=dirname($f->getData('path')).'/manuscrito-v2.docx';$id=Services::get('file')->add($temp,$relative);$f->setData('fileId',$id);DAORegistry::getDAO('SubmissionFileDAO')->updateObject($f);echo "Synthetic version replaced\n";
