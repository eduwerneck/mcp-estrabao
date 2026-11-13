<?php
declare(strict_types=1);
use Estrabao\Document\DocxReader;
use Estrabao\Rules\Footnotes;
foreach([0,1,3,4] as $count){
 test('conferência de linhas separada da contagem '.$count,function()use($count){
  footnoteDocx(footnoteParts($count),function($doc)use($count){
   $c=footnoteChecks($doc);same($count<=3?'conforme':'não conforme',$c[0]['status']);
   same('2.0.0',$c[1]['rule_version']);same($count===0?'conforme':'indeterminado',$c[1]['status']);
   $r=$c[1]['evidence']['review_plan'];same($count!==0,$r['required']);same($count,$r['note_count']);
   same(false,$r['manual_notes_inspected']);same($count,count($r['samples']));
   same(false,$c[1]['evidence']['renderer_available']);
  });
 });
}
foreach(['reference','part','relationship','content_type']as$kind){
 test('indicador de notas de fim requer revisão separada '.$kind,function()use($kind){
  $p=docxParts(docxBody(labels()));
  if($kind==='reference'){$p['word/document.xml']=str_replace('</w:body>','<w:p><w:r><w:endnoteReference w:id="7"/></w:r></w:p></w:body>',$p['word/document.xml']);}
  if($kind==='part'){$p['word/endnotes.xml']='<w:endnotes xmlns:w="'.DocxReader::W.'"><w:endnote w:id="7"><w:p/></w:endnote></w:endnotes>';}
  if($kind==='relationship'){$p['word/_rels/document.xml.rels']='<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="en" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/endnotes" TargetMode="External" Target="https://invalid.example/private"/></Relationships>';}
  if($kind==='content_type'){$p['[Content_Types].xml']=str_replace('</Types>','<Override PartName="/word/odd.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml"/></Types>',$p['[Content_Types].xml']);}
  footnoteDocx($p,function($doc){$c=footnoteChecks($doc);same('conforme',$c[0]['status']);same(0,$doc['footnotes']['note_count']);same('indeterminado',$c[1]['status']);same(true,$c[1]['evidence']['review_plan']['endnote_indicators']);});
 });
}
test('inventário inconclusivo não fornece lista parcial para conferir como completa',function(){
 $p=footnoteParts(4);$p['word/document.xml']=str_replace('w:id="1"','w:id="999"',$p['word/document.xml']);
 footnoteDocx($p,function($doc){$r=footnoteChecks($doc)[1]['evidence']['review_plan'];same(false,$r['inventory_complete']);same(null,$r['note_count']);same([],$r['samples']);same(true,$r['required']);});
});
test('plano de conferência limita amostras e não expõe texto IDs ou caminhos',function(){
 footnoteDocx(footnoteParts(40),function($doc,$path){$c=footnoteChecks($doc);$r=$c[1]['evidence']['review_plan'];same(32,count($r['samples']));same(8,$r['samples_omitted']);same(false,strpos(json_encode($c),'Nota sintética privada'));same(false,strpos(json_encode($c),$path));});
});
test('fonte ausente requer resolver fonte e nunca aprova linhas ou zero notas',function(){
 $c=(new Footnotes())->evaluate(new \Estrabao\Contracts\Submission(fixture()))[1]->jsonSerialize();same('indeterminado',$c['status']);same(true,$c['evidence']['review_plan']['required']);same(null,$c['evidence']['note_count']);
});
function withFootnotePanel(array $parts,callable $callback):void{
 withDeskPanel(function($service,$request,$form,$publication,$path)use($parts,$callback){
  $z=new ZipArchive();$z->open($path,ZipArchive::OVERWRITE);foreach($parts as$n=>$xml){$z->addFromString($n,$xml);}$z->close();
  $request->vars['fingerprint']=(new EstrabaoAdmissionBridge())->analyze($form)['fingerprint'];
  $callback($service->draft($request,17),$service,$request);
 });
}
test('painel separa notas da carta e não registra liberação pela conferência',function(){
 withFootnotePanel(footnoteParts(3),function($r,$service,$request){
  same(3,$r['footnote_review']['note_count']);same(1,$r['pending_groups']['deskPendingFootnotes']);
  same(false,strpos($r['draft']['body'],'Conferir chamada Word'));same(false,strpos($r['draft']['body'],'Notas de rodapé:'));
  same([],$service->view($request,17)['events']);same(false,$service->view($request,17)['release_active']);
 });
});
test('excesso comprovado continua na carta mas linhas permanecem internas',function(){
 withFootnotePanel(footnoteParts(4),function($r){same(true,strpos($r['draft']['body'],'confirmadas 4 notas')!==false);same(4,$r['footnote_review']['note_count']);same(false,strpos($r['draft']['body'],'duas linhas')!==false);});
});
test('zero rodapés sem outro indicador não cria ficha de revisão pendente',function(){
 withFootnotePanel(footnoteParts(0),function($r){same(null,$r['footnote_review']);same(false,isset($r['pending_groups']['deskPendingFootnotes']));});
});
