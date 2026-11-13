<?php
declare(strict_types=1);
require_once __DIR__.'/../plugins/generic/estrabao/ArticleSubmittedDate.inc.php';
function articleDateRecords():array{
 return [new AdmissionTestRecord(['id'=>8,'contextId'=>3,'status'=>3,'submissionProgress'=>0,'dateSubmitted'=>'2026-01-02 00:10:00']),
  new AdmissionTestRecord(['id'=>22,'submissionId'=>8,'status'=>3,'datePublished'=>'2026-03-05']),
  new AdmissionTestRecord(['id'=>21,'submissionId'=>8,'status'=>3,'datePublished'=>'2026-02-04'])];
}
test('data pública vem da submissão e não da publicação ou horário do navegador',function(){
 [$a,$p,$f]=articleDateRecords();$before=$a->data;same(['iso_date'=>'2026-01-02'],EstrabaoArticleSubmittedDate::model($a,$p,$f,3));same($before,$a->data);
 $p->data['datePublished']='2026-04-06';same(['iso_date'=>'2026-01-02'],EstrabaoArticleSubmittedDate::model($a,$p,$f,3));
});
foreach([null,'','0000-00-00','2026-02-30','2026-01-02 24:00:00','2026-01-02 00:60:00','2026-01-02 00:00:60','2026-01-02T00:00:00Z','<script>2026-01-02</script>']as$value){
 test('data ausente ou inválida não vira informação pública '.json_encode($value),function()use($value){[$a,$p,$f]=articleDateRecords();$a->data['dateSubmitted']=$value;same(null,EstrabaoArticleSubmittedDate::model($a,$p,$f,3));});
}
foreach(['context','article_status','progress','publication_status','first_status','publication_owner','first_owner','missing_published','after_first','reversed_versions']as$kind){
 test('data respeita vínculo status e cronologia '.$kind,function()use($kind){
  [$a,$p,$f]=articleDateRecords();
  if($kind==='context')$a->data['contextId']=4;
  if($kind==='article_status')$a->data['status']=1;
  if($kind==='progress')$a->data['submissionProgress']=4;
  if($kind==='publication_status')$p->data['status']=1;
  if($kind==='first_status')$f->data['status']=1;
  if($kind==='publication_owner')$p->data['submissionId']=9;
  if($kind==='first_owner')$f->data['submissionId']=9;
  if($kind==='missing_published')$p->data['datePublished']=null;
  if($kind==='after_first')$a->data['dateSubmitted']='2026-02-05';
  if($kind==='reversed_versions')$f->data['datePublished']='2026-04-06';
  same(null,EstrabaoArticleSubmittedDate::model($a,$p,$f,3));
 });
}
function articleDateHtml():string{return '<html><article class="obj_article_details"><div class="main_entry">Abstract</div><div class="entry_details"><div class="item published"><section class="sub_item">Published 04-02-2026</section></div></div></article></html>';}
test('insere seção no mesmo box antes de Publicado sem reescrever HTML',function(){
 $html=articleDateHtml();$block='<section class="sub_item estrabao-submitted-date">Submitted 02-01-2026</section>';
 $out=EstrabaoArticleSubmittedDate::insert($html,$block);same($html,str_replace($block,'',$out));same(true,strpos($out,'Submitted')<strpos($out,'Published'));same($out,EstrabaoArticleSubmittedDate::insert($out,$block));
});
foreach(['no_published','different_theme','duplicate','wrong_order','outside_article']as$kind){
 test('markup não suportado ou ambíguo permanece intacto '.$kind,function()use($kind){
  $html=articleDateHtml();
  if($kind==='no_published')$html=str_replace('class="item published"','class="other"',$html);
  if($kind==='different_theme')$html=str_replace('class="entry_details"','class="custom-sidebar"',$html);
  if($kind==='duplicate')$html.='<div class="item published">Duplicate</div>';
  if($kind==='wrong_order')$html=str_replace('<div class="entry_details">','',$html).'<div class="entry_details">';
  if($kind==='outside_article')$html=str_replace('<div class="item published">','</article><div class="item published">',$html);
  same($html,EstrabaoArticleSubmittedDate::insert($html,'<section>date</section>'));
 });
}
class ArticleDateManager {
 public $vars=[];public $filters=[];
 function getTemplateVars($key){return $this->vars[$key]??null;}
 function assign($key,$value){$this->vars[$key]=$value;}
 function fetch($template){return '<section class="estrabao-submitted-date"><time datetime="'.htmlspecialchars($this->vars['estrabaoSubmittedDate']['iso_date'],ENT_QUOTES,'UTF-8').'">Date</time></section>';}
 function registerFilter($type,$callback){$this->filters[]=$callback;}
}
foreach(['valid','disabled','no_context','preview','other_template']as$kind){
 test('hook de data restrito a artigo público da revista '.$kind,function()use($kind){
  $p=new EstrabaoPlugin();$p->enabled=$kind!=='disabled';Application::$request=new AdmissionTestRequest();Application::$request->context=$kind==='no_context'?null:new AdmissionTestRecord(['id'=>3]);
  [$a,$pub,$f]=articleDateRecords();if($kind==='preview')$pub->data['status']=1;
  $m=new ArticleDateManager();$m->vars=['article'=>$a,'publication'=>$pub,'firstPublication'=>$f];
  same(false,$p->articleSubmittedDate('TemplateManager::display',[$m,$kind==='other_template'?'frontend/pages/issue.tpl':'frontend/pages/article.tpl']));
  same($kind==='valid'?1:0,count($m->filters));
  if($kind==='valid'){same(true,strpos($m->filters[0](articleDateHtml()),'datetime="2026-01-02"')!==false);}
 });
}
