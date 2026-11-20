<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}
chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true);
if(($c['general']['base_url']??null)!=='http://127.0.0.1:18080'||($c['database']['name']??null)!=='estrabao_synthetic'){exit(1);}
require 'tools/bootstrap.inc.php';new CommandLineTool([]);
$state=json_decode(file_get_contents('/tmp/lab-state.json'),true);$language=$argv[1]??'';$mode=$argv[2]??'';
$marker=$argv[3]??'';if(!in_array($marker,['s.f.','en prensa'],true)){exit(1);}
if(!in_array($language,['pt','en','es'],true)||!in_array($mode,['bad','good','divergent','equivalent','apud','label','inspect'],true)){exit(1);}
$record=$state['submissions'][['pt'=>'review','en'=>'revoke','es'=>'replace'][$language]];$id=(int)$record['id'];$s=Services::get('submission')->get($id);
$allowed=json_decode(file_get_contents('/tmp/estrabao-spanish-date-fresh-ids.json'),true);
if(!$s||$s->getSubmissionProgress()!=4||$s->getData('dateSubmitted')||!is_array($allowed)||!in_array($id,$allowed,true)){exit(1);}
$f=Services::get('submissionFile')->get($record['submission_file_id']);if(!$f||(int)$f->getData('submissionId')!==$id){exit(1);}
if($mode==='inspect'){
 require_once '/opt/estrabao/src/bootstrap.php';$path=Config::getVar('files','files_dir').'/'.$f->getData('path');$d=(new \Estrabao\Document\DocxReader())->read($path);
 $checks=(new \Estrabao\Rules\References($d,$language))->evaluate(new \Estrabao\Contracts\Submission(['schema_version'=>'1.0']));$apa=$checks[0]->jsonSerialize();
 $a=$apa['evidence']['analysis']??[];echo json_encode(['source_available'=>$d['available'],'sha256'=>hash_file('sha256',$path),'locale'=>$s->getLocale(),'decision'=>$apa['evidence']['admission_policy']['decision'],
 'language'=>$a['language']??null,'segmentation_complete'=>$a['segmentation_complete']??false,'matched'=>$a['matched_citations']??0,'unresolved'=>$a['unresolved_citations']??0,'special_date_references'=>$a['special_date_references']??0,'special_date_citations'=>$a['special_date_citations']??0,'unparsed_references'=>$a['unparsed_references']??0,'full_date_references'=>$a['full_date_references']??0,'ambiguous'=>$a['ambiguous_matches']??0,'matching_status'=>$checks[1]->jsonSerialize()['status'],'issues'=>array_column($a['citation_style_samples']??[],'issues')]);exit;
}
$word=['pt'=>'e','en'=>'and','es'=>'y'][$language];$date=$marker;$bodyDate=$marker;
if($mode==='equivalent'){$date=$marker==='s.f.'?'n.d.':'in press';}
if($mode==='divergent'){if($marker==='s.f.'){$date='s.f.-b';$bodyDate='s.f.-a';}else{$bodyDate='2020';}}
$citation='Silva & Costa ('.$bodyDate.').';
if($mode!=='bad'){$citation='Silva '.$word.' Costa ('.$bodyDate.').';}
if($mode==='label'){$citation=['pt'=>'Data','en'=>'Date','es'=>'Fecha'][$language].' ('.$marker.').';}
if($mode==='apud'){$citation='(Original, 1900, apud Silva & Costa, '.$bodyDate.').';}
$labels=$language==='pt'?['Introdução','Metodologia','Resultados e Discussão','Considerações Finais','Referências']:($language==='en'?['Introduction','Methods','Results and Discussion','Conclusions','References']:['Introducción','Métodos','Resultados y Discusión','Conclusiones','Referencias']);
$w='http://schemas.openxmlformats.org/wordprocessingml/2006/main';$body='';
foreach(array_merge([$labels[0],$citation],array_slice($labels,1))as$text){$body.='<w:p><w:r><w:t>'.htmlspecialchars($text,ENT_XML1,'UTF-8').'</w:t></w:r></w:p>';}
$body.='<w:p><w:r><w:t xml:space="preserve">Silva, A., &amp; Costa, B. ('.htmlspecialchars($date,ENT_XML1,'UTF-8').'). </w:t></w:r><w:r><w:rPr><w:i/></w:rPr><w:t>Synthetic work</w:t></w:r><w:r><w:t>. Example Publisher.</w:t></w:r></w:p>';
$temp='/tmp/estrabao-spanish-date-upload.docx';$z=new ZipArchive();if($z->open($temp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){exit(1);}
$z->addFromString('word/document.xml','<w:document xmlns:w="'.$w.'"><w:body>'.$body.'</w:body></w:document>');
$z->addFromString('[Content_Types].xml','<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
$z->addFromString('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');foreach(['word/document.xml','[Content_Types].xml','_rels/.rels']as$part){$z->setMtimeName($part,1577836800);}$z->close();
$locale=['pt'=>'pt_BR','en'=>'en_US','es'=>'es_ES'][$language];$s->setData('locale',$locale);DAORegistry::getDAO('SubmissionDAO')->updateObject($s);
$pub=$s->getCurrentPublication();$pub->setData('title','Synthetic manuscript '.$language,$locale);$pub->setData('locale',$locale);DAORegistry::getDAO('PublicationDAO')->updateObject($pub);
$relative=dirname($f->getData('path')).'/synthetic-'.$language.'-'.$mode.'-'.bin2hex(random_bytes(4)).'.docx';$fid=Services::get('file')->add($temp,$relative);
$f->setData('fileId',$fid);DAORegistry::getDAO('SubmissionFileDAO')->updateObject($f);unlink($temp);echo "Fresh synthetic citation version installed\n";
