<?php
if (getenv('ESTRABAO_SYNTHETIC_LAB') !== '1') { exit(1); }
ob_start();chdir('/var/www/html');
$labConfig=parse_ini_file('config.inc.php',true);
if(($labConfig['general']['base_url']??null)!=='http://127.0.0.1:18080'||($labConfig['database']['name']??null)!=='estrabao_synthetic'){exit(1);}
 require 'tools/bootstrap.inc.php'; new CommandLineTool([]);
$c=json_decode(file_get_contents('/tmp/lab-credentials.json'),true);
$admin=DAORegistry::getDAO('UserDAO')->getByUsername($c['admin_username']);Registry::set('user',$admin);
$request=Application::get()->getRequest();$request->setDispatcher(Application::get()->getDispatcher());
$contextDao=Application::getContextDAO();
$journal=$contextDao->getByPath('estrabao-lab');
if(!$journal){
 $journal=$contextDao->newDataObject();
 foreach(['urlPath'=>'estrabao-lab','enabled'=>true,'primaryLocale'=>'pt_BR','supportedLocales'=>['pt_BR','en_US','es_ES'],'supportedSubmissionLocales'=>['pt_BR'],'supportedFormLocales'=>['pt_BR'],'contactName'=>'Equipe sintética','contactEmail'=>'editor@synthetic.invalid','supportName'=>'Suporte sintético','supportEmail'=>'support@synthetic.invalid']as$k=>$v){$journal->setData($k,$v);}
 $journal->setData('name','Estrabão — laboratório sintético','pt_BR');
 $journal=Services::get('context')->add($journal,$request);
}
echo 'Journal '.$journal->getId()."\n";
$userDao=DAORegistry::getDAO('UserDAO');$author=$userDao->getByUsername($c['author_username']);
if(!$author){$author=$userDao->newDataObject();$author->setUsername($c['author_username']);$author->setPassword(Validation::encryptCredentials($c['author_username'],$c['author_password']));$author->setEmail('author@synthetic.invalid');$author->setGivenName('Autora','pt_BR');$author->setFamilyName('Sintética','pt_BR');$author->setData('locales',['pt_BR']);$author->setData('dateRegistered',Core::getCurrentDate());$userDao->insertObject($author);}
$groupDao=DAORegistry::getDAO('UserGroupDAO');$group=$groupDao->getDefaultByRoleId($journal->getId(),ROLE_ID_AUTHOR);$groupDao->assignUserToGroup($author->getId(),$group->getId());
$pluginDao=DAORegistry::getDAO('PluginSettingsDAO');$pluginDao->updateSetting($journal->getId(),'estrabaoplugin','enabled',true,'bool');
file_put_contents('/tmp/lab-state.json',json_encode(['journal_id'=>$journal->getId(),'admin_id'=>$admin->getId(),'author_id'=>$author->getId(),'author_group_id'=>$group->getId()]));
echo 'Users and plugin ready'."\n";
$state=json_decode(file_get_contents('/tmp/lab-state.json'),true);
$sections=DAORegistry::getDAO('SectionDAO')->getByContextId($journal->getId())->toArray();$section=reset($sections);
$state['section_id']=$section->getId();
$state['submissions']=[];
$genreId=null;foreach(DAORegistry::getDAO('GenreDAO')->getByContextId($journal->getId())->toArray()as$genre){if(!$genre->getData('dependent')&&!$genre->getData('supplementary')){$genreId=$genre->getId();break;}}
Registry::set('user',$author);
foreach(['abnt','review','revoke','replace','csrf']as$kind){
 $s=DAORegistry::getDAO('SubmissionDAO')->newDataObject();
 foreach(['contextId'=>$journal->getId(),'locale'=>'pt_BR','submissionProgress'=>4,'stageId'=>WORKFLOW_STAGE_ID_SUBMISSION,'status'=>STATUS_QUEUED]as$k=>$v){$s->setData($k,$v);}
 $s=Services::get('submission')->add($s,$request);
 $pub=DAORegistry::getDAO('PublicationDAO')->newDataObject();
 foreach(['submissionId'=>$s->getId(),'sectionId'=>$section->getId(),'status'=>STATUS_QUEUED,'version'=>1,'locale'=>'pt_BR']as$k=>$v){$pub->setData($k,$v);}
 $pub->setData('title','Manuscrito sintético '.$kind,'pt_BR');
 $pub=Services::get('publication')->add($pub,$request);
 $s=Services::get('submission')->edit($s,['currentPublicationId'=>$pub->getId()],$request);
 $a=DAORegistry::getDAO('AuthorDAO')->newDataObject();$a->setData('publicationId',$pub->getId());$a->setData('userGroupId',$group->getId());$a->setData('email','author@synthetic.invalid');$a->setData('givenName','Autora','pt_BR');$a->setData('familyName','Sintética','pt_BR');$a->setData('seq',0);DAORegistry::getDAO('AuthorDAO')->insertObject($a);
 $pub=Services::get('publication')->edit($pub,['primaryContactId'=>$a->getId()],$request);
 DAORegistry::getDAO('StageAssignmentDAO')->build($s->getId(),$group->getId(),$author->getId(),false,true);
 $w='http://schemas.openxmlformats.org/wordprocessingml/2006/main';
 $ref=$kind==='abnt'?'SILVA, João. Livro. São Paulo: Editora, 2020.':'Silva, A. (2020). Estudo da paisagem. Editora Exemplo.';
 $xml='<w:document xmlns:w="'.$w.'"><w:body><w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>'.htmlspecialchars($ref,ENT_XML1,'UTF-8').'</w:t></w:r></w:p></w:body></w:document>';
 $local='/tmp/lab-synthetic.docx';$zip=new ZipArchive();$zip->open($local,ZipArchive::CREATE|ZipArchive::OVERWRITE);$zip->addFromString('word/document.xml',$xml);$zip->addFromString('[Content_Types].xml','<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');$zip->addFromString('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');$zip->close();
 $relative='journals/'.$journal->getId().'/articles/'.$s->getId().'/submission/manuscrito.docx';Services::get('file')->fs->createDir(dirname($relative));$fid=Services::get('file')->add($local,$relative);
 $sf=DAORegistry::getDAO('SubmissionFileDAO')->newDataObject();
 foreach(['submissionId'=>$s->getId(),'fileId'=>$fid,'fileStage'=>SUBMISSION_FILE_SUBMISSION,'genreId'=>$genreId,'uploaderUserId'=>$author->getId(),'viewable'=>true]as$k=>$v){$sf->setData($k,$v);}$sf->setData('name','Manuscrito sintético.docx','pt_BR');
 $sf=Services::get('submissionFile')->add($sf,$request);
 $state['submissions'][$kind]=['id'=>$s->getId(),'file_id'=>$fid,'submission_file_id'=>$sf->getId()];
 echo 'Synthetic submission '.$kind.' '.$s->getId()."\n";
}
file_put_contents('/tmp/lab-state.json',json_encode($state));

ob_end_flush();
