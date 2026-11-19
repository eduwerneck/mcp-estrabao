<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1')exit(1);
chdir('/var/www/html');$labConfig=parse_ini_file('config.inc.php',true);if(($labConfig['general']['base_url']??null)!=='http://127.0.0.1:18080'||($labConfig['database']['name']??null)!=='estrabao_synthetic'){exit(1);}require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$inspect=($argv[1]??'')==='--inspect-corrected';$correct=$inspect||($argv[1]??'')==='--correct-abnt';
$s=json_decode(file_get_contents('/tmp/lab-state.json'),true)['submissions'][$correct?'abnt':'replace'];
if($correct&&!$inspect){$submission=Services::get('submission')->get($s['id']);if(!$submission||$submission->getData('submissionProgress')!=4||$submission->getData('dateSubmitted')){exit(1);}}
$f=Services::get('submissionFile')->get($s['submission_file_id']);$source=Config::getVar('files','files_dir').'/'.$f->getData('path');
if($inspect){
 require_once '/opt/estrabao/src/bootstrap.php';$document=(new \Estrabao\Document\DocxReader())->read($source);$analysis=\Estrabao\Document\ReferenceAnalysis::analyze($document);
 echo json_encode(['file_id'=>$f->getData('fileId'),'sha256'=>hash_file('sha256',$source),'available'=>$document['available'],'analysis'=>$analysis,'style_audit'=>\Estrabao\Document\ReferenceStyleAudit::analyze($document,'pt')]);exit;
}
$temp='/tmp/replacement-synthetic.docx';copy($source,$temp);$z=new ZipArchive();$z->open($temp);$z->addFromString('synthetic-version.txt','new version');$z->close();
if($correct){
 $w='http://schemas.openxmlformats.org/wordprocessingml/2006/main';
 $texts=['Introdução','Costa (2018).','Silva (2019) afirma “'.implode(' ',array_fill(0,39,'trecho')).'” (p. 10).','Silva (2019, 2020a, 2020b).','Texto (Costa, Alves, et al., 2021).','Souza (s.d.).','Metodologia','Texto sintético.','Resultados e Discussão','Texto sintético.','Considerações Finais','Texto sintético.','Referências',
 'Costa, B. (2018). Obra sintética individual. Editora Exemplo.',
 'Costa, B., Alves, C., Rocha, D., Nunes, E., & Paiva, F. (2021). Obra sintética. Editora Exemplo.',
 'Costa, B., Lima, C., Ramos, D., Vieira, E., & Reis, F. (2021). Outra obra sintética. Editora Exemplo.',
 'Silva, A. (2019). Obra sintética. Editora Exemplo.','Silva, A. (2020a). Obra sintética. Editora Exemplo.','Silva, A. (2020b). Outra obra sintética. Editora Exemplo.','Souza, B. (n.d.). Obra sintética. Editora Exemplo.'];
 $xml='<w:document xmlns:w="'.$w.'"><w:body>';foreach($texts as$text){$xml.='<w:p><w:r><w:t>'.htmlspecialchars($text,ENT_XML1,'UTF-8').'</w:t></w:r></w:p>';}$xml.='</w:body></w:document>';
 $z=new ZipArchive();if($z->open($temp)!==true){exit(1);}$z->addFromString('word/document.xml',$xml);$z->close();
}
$relative=dirname($f->getData('path')).'/manuscrito-v2.docx';$id=Services::get('file')->add($temp,$relative);$f->setData('fileId',$id);DAORegistry::getDAO('SubmissionFileDAO')->updateObject($f);echo "Synthetic version replaced\n";
