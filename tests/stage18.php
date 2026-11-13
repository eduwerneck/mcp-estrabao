<?php
declare(strict_types=1);
use Estrabao\Document\{DocxReader,DocxCompatibility};

function compoundDocxParts(bool $picture=true,bool $table=true):array {
    $p=formattingParts();$body=($picture?visualPicture():'').($table?simpleVisualTable():'');
    $p['word/document.xml']=str_replace('<w:body>','<w:body>'.$body,$p['word/document.xml']);
    if($picture){
        $p['word/_rels/document.xml.rels']=str_replace('</Relationships>','<Relationship Id="image1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/picture.png"/></Relationships>',$p['word/_rels/document.xml.rels']);
        $p['[Content_Types].xml']=str_replace('</Types>','<Default Extension="png" ContentType="image/png"/></Types>',$p['[Content_Types].xml']);
        $p['word/media/picture.png']=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=');
    }
    return $p;
}
function scopedChecks(array $doc):array {
    $s=new \Estrabao\Contracts\Submission(fixture());return array_column(\Estrabao\Reporting\Report::generate($s,\Estrabao\Rules\DefaultRules::all($doc,'article','pt'))['checks'],null,'rule_id');
}
function addWordAdvisories(array $p,string $value='none'):array {
    $lig='<w14:ligatures xmlns:w14="'.DocxCompatibility::W14.'" w14:val="'.$value.'"/>';
    $p['word/document.xml']=str_replace('<w:r>','<w:r><w:rPr>'.$lig.'</w:rPr><w:lastRenderedPageBreak/>',$p['word/document.xml']);
    if(isset($p['word/styles.xml'])){$p['word/styles.xml']=str_replace('<w:rPr>','<w:rPr>'.$lig,$p['word/styles.xml']);}
    return $p;
}
test('marcadores de paginação e ligaturas do Word não impedem leitura completa simples',function(){
    withDocx(addWordAdvisories(formattingParts()),function($path){
        $doc=(new DocxReader())->read($path);same(true,$doc['available']);same('Introdução',$doc['paragraphs'][0]['text']);
        same(true,$doc['compatibility']['cached_page_breaks']>0);same(true,$doc['compatibility']['ligature_properties']>0);
        same(hash_file('sha256',$path),$doc['sha256']);same('conforme',scopedChecks($doc)['manuscript.page.a4']['status']);
    });
});
test('paginação em cache entre runs não insere espaço ou quebra no título',function(){
    withDocx(docxParts('<w:p><w:r><w:t>Introdu</w:t><w:lastRenderedPageBreak/><w:t>ção</w:t></w:r></w:p>'),function($path){
        $doc=(new DocxReader())->read($path);same(true,$doc['available']);same('Introdução',$doc['paragraphs'][0]['text']);
    });
});
test('normalização não altera DOM original nem bytes do pacote',function(){
    $d=new DOMDocument();$d->loadXML('<w:document xmlns:w="'.DocxReader::W.'"><w:body><w:p><w:r><w:lastRenderedPageBreak/><w:t>Privado</w:t></w:r></w:p></w:body></w:document>');
    $before=$d->saveXML();$result=DocxCompatibility::normalize(['word/document.xml'=>$d]);
    same($before,$d->saveXML());same(false,strpos($result['parts']['word/document.xml']->saveXML(),'lastRenderedPageBreak')!==false);
});
foreach(['none','standard','standardContextual','all']as$value){
    test('ligatura conhecida é apenas apresentação '.$value,function()use($value){
        withDocx(addWordAdvisories(formattingParts(),$value),function($path){same(true,(new DocxReader())->read($path)['available']);});
    });
}
foreach(['future','none" w14:other="x','none"><w:t>PRIVATE</w:t></w14:ligatures><w14:ligatures w14:val="none']as$value){
    test('ligatura desconhecida ou com conteúdo não é apagada '.$value,function()use($value){
        withDocx(addWordAdvisories(formattingParts(),$value),function($path){$doc=(new DocxReader())->read($path);same(false,$doc['available']);same(false,isset($doc['main_text']));});
    });
}
test('marcador de paginação com texto ou atributo não é removido',function(){
    foreach(['<w:lastRenderedPageBreak><w:t>PRIVATE</w:t></w:lastRenderedPageBreak>','<w:lastRenderedPageBreak w:val="1"/>']as$node){
        withDocx(docxParts('<w:p><w:r>'.$node.'<w:t>Texto</w:t></w:r></w:p>'),function($path){$doc=(new DocxReader())->read($path);same(false,$doc['available']);same(false,isset($doc['main_text']));});
    }
});
foreach([[true,false],[false,true],[true,true]]as$options){
    test('perfil parcial registra figuras/tabelas sem declarar texto completo '.json_encode($options),function()use($options){
        withDocx(compoundDocxParts(...$options),function($path)use($options){
            $doc=(new DocxReader())->read($path);same(false,$doc['available']);same([],$doc['paragraphs']);same(true,$doc['main_text']['available']);
            $coverage=$doc['main_text']['coverage'];same($options[0]?1:0,$coverage['drawings_excluded']);same($options[1]?1:0,$coverage['tables_excluded']);
            same(false,$coverage['image_text_read']);same(false,$doc['main_text']['coverage_complete']);
            $checks=scopedChecks($doc);same('conforme',$checks['manuscript.page.a4']['status']);same('conforme',$checks['manuscript.page.margins']['status']);
            same('indeterminado',$checks['manuscript.body.font']['status']);same('indeterminado',$checks['manuscript.word_limit']['status']);
        });
    });
}
test('seções presentes no texto Word são confirmadas sem afirmar cobertura completa',function(){
    withDocx(compoundDocxParts(),function($path){
        $c=scopedChecks((new DocxReader())->read($path))['manuscript.docx.structure'];same('conforme',$c['status']);same('3.0.0',$c['rule_version']);same(false,$c['evidence']['full_document_text_available']);
    });
});
test('seção ausente do perfil parcial fica inconclusiva, não ausente por inferência',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace('Metodologia','Outra seção',$p['word/document.xml']);
    withDocx($p,function($path){$c=scopedChecks((new DocxReader())->read($path))['manuscript.docx.structure'];same('indeterminado',$c['status']);
        foreach($c['evidence']['sections']as$s){if($s['required_label']==='Metodologia'){same('indeterminado',$s['status']);}}
    });
});
test('texto de tabela não é promovido a seção ou título do corpo',function(){
    $p=compoundDocxParts(false,true);$p['word/document.xml']=str_replace('Metodologia','Outro título',$p['word/document.xml']);
    $p['word/document.xml']=str_replace('Dado A','Metodologia',$p['word/document.xml']);
    withDocx($p,function($path){$doc=(new DocxReader())->read($path);same('indeterminado',scopedChecks($doc)['manuscript.docx.structure']['status']);
        same(false,in_array('Metodologia',array_column($doc['main_text']['paragraphs'],'text'),true));
    });
});
test('divergência de fonte/tamanho comprovada permanece não conforme com figura',function(){
    $p=compoundDocxParts(true,false);$p['word/styles.xml']=str_replace(['Times New Roman','w:sz w:val="24"'],['Arial','w:sz w:val="20"'],$p['word/styles.xml']);
    withDocx($p,function($path){$c=scopedChecks((new DocxReader())->read($path));same('não conforme',$c['manuscript.body.font']['status']);same('não conforme',$c['manuscript.body.font_size']['status']);same(false,$c['manuscript.body.font']['evidence']['coverage_complete']);});
});
test('tamanho de página divergente pode ser comprovado com tabela textual',function(){
    $p=compoundDocxParts(false,true);$p['word/document.xml']=str_replace('w:w="11906" w:h="16838"','w:w="10000" w:h="15000"',$p['word/document.xml']);
    withDocx($p,function($path){same('não conforme',scopedChecks((new DocxReader())->read($path))['manuscript.page.a4']['status']);});
});
test('caixa alta em título Word reconhecido pode ser sinalizada no perfil parcial',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace('Introdução','INTRODUÇÃO',$p['word/document.xml']);
    withDocx($p,function($path){$c=scopedChecks((new DocxReader())->read($path))['manuscript.headings.case'];same('não conforme',$c['status']);same('3.0.0',$c['rule_version']);same(false,$c['evidence']['full_document_text_available']);});
});
foreach(['external','missing','wrong-type','wrong-mime','duplicate','svg','bad-embed']as$case){
    test('imagem não resolvida ou não suportada não libera perfil '.$case,function()use($case){
        $p=compoundDocxParts();$rel='word/_rels/document.xml.rels';
        if($case==='external'){$p[$rel]=str_replace('Target="media/picture.png"','TargetMode="External" Target="https://invalid.example/private.png"',$p[$rel]);}
        if($case==='missing'){unset($p['word/media/picture.png']);}
        if($case==='wrong-type'){$p[$rel]=str_replace('relationships/image','relationships/hyperlink',$p[$rel]);}
        if($case==='wrong-mime'){$p['[Content_Types].xml']=str_replace('image/png','image/svg+xml',$p['[Content_Types].xml']);}
        if($case==='duplicate'){$p[$rel]=str_replace('</Relationships>','<Relationship Id="image1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/picture.png"/></Relationships>',$p[$rel]);}
        if($case==='svg'){$p['word/media/picture.svg']=$p['word/media/picture.png'];unset($p['word/media/picture.png']);$p[$rel]=str_replace('picture.png','picture.svg',$p[$rel]);$p['[Content_Types].xml']=str_replace(['Extension="png"','image/png'],['Extension="svg"','image/svg+xml'],$p['[Content_Types].xml']);}
        if($case==='bad-embed'){$p['word/document.xml']=str_replace('r:embed="image1"','r:link="image1"',$p['word/document.xml']);}
        withDocx($p,function($path){$doc=(new DocxReader())->read($path);same(false,isset($doc['main_text']));same('indeterminado',scopedChecks($doc)['manuscript.page.a4']['status']);});
    });
}

function withRasterEffectLayer(array $p):array {
    $extension='<a:extLst><a:ext uri="synthetic-effect"><a14:imgProps xmlns:a14="http://schemas.microsoft.com/office/drawing/2010/main"><a14:imgLayer r:embed="effect1"><a14:imgEffect><a14:saturation sat="0"/></a14:imgEffect></a14:imgLayer></a14:imgProps></a:ext></a:extLst>';
    $p['word/document.xml']=str_replace('<a:blip r:embed="image1"/>','<a:blip r:embed="image1">'.$extension.'</a:blip>',$p['word/document.xml']);
    $p['word/_rels/document.xml.rels']=str_replace('</Relationships>','<Relationship Id="effect1" Type="http://schemas.microsoft.com/office/2007/relationships/hdphoto" Target="media/effect.wdp"/></Relationships>',$p['word/_rels/document.xml.rels']);
    $p['[Content_Types].xml']=str_replace('</Types>','<Default Extension="wdp" ContentType="image/vnd.ms-photo"/></Types>',$p['[Content_Types].xml']);
    $p['word/media/effect.wdp']='SYNTHETIC_OPAQUE_RASTER_NOT_DECODED';return $p;
}
test('camada raster local de efeito Word não oculta o texto principal',function(){
    withDocx(withRasterEffectLayer(compoundDocxParts()),function($path){$d=(new DocxReader())->read($path);same(true,$d['main_text']['available']);same(1,$d['main_text']['coverage']['drawings_excluded']);same(false,$d['main_text']['coverage']['image_text_read']);});
});
test('camada de efeito externa ou com tipo incorreto mantém análise parcial indisponível',function(){
    foreach(['external','type']as$case){
        $p=withRasterEffectLayer(compoundDocxParts());$r='word/_rels/document.xml.rels';
        if($case==='external'){$p[$r]=str_replace('Target="media/effect.wdp"','TargetMode="External" Target="https://invalid.example/effect.wdp"',$p[$r]);}
        else{$p[$r]=str_replace('relationships/hdphoto','relationships/hyperlink',$p[$r]);}
        withDocx($p,function($path){same(false,isset((new DocxReader())->read($path)['main_text']));});
    }
});
test('imagem ancorada simples com relacionamento local admite perfil Word verificado',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace(['wp:inline','</wp:inline>'],['wp:anchor','</wp:anchor>'],$p['word/document.xml']);
    withDocx($p,function($path){same(true,(new DocxReader())->read($path)['main_text']['available']);});
});
foreach(['textbox','field','revision','control','math','unknown-graphic']as$case){
    test('contenção conservadora preservada para '.$case,function()use($case){
        $p=compoundDocxParts();
        $nodes=['textbox'=>'<w:txbxContent><w:p><w:r><w:t>PRIVATE</w:t></w:r></w:p></w:txbxContent>',
            'field'=>'<w:fldSimple w:instr="PRIVATE"/>','revision'=>'<w:ins><w:r><w:t>PRIVATE</w:t></w:r></w:ins>',
            'control'=>'<w:sdt><w:sdtContent><w:r><w:t>PRIVATE</w:t></w:r></w:sdtContent></w:sdt>',
            'math'=>'<m:oMath xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"/>',
            'unknown-graphic'=>'<a:future xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">PRIVATE</a:future>'];
        if($case==='unknown-graphic'){$p['word/document.xml']=str_replace('</a:graphicData>',$nodes[$case].'</a:graphicData>',$p['word/document.xml']);}
        else{$p['word/document.xml']=str_replace('<w:body>','<w:body><w:p><w:r>'.$nodes[$case].'</w:r></w:p>',$p['word/document.xml']);}
        withDocx($p,function($path){$d=(new DocxReader())->read($path);same(false,isset($d['main_text']));same('indeterminado',scopedChecks($d)['manuscript.page.a4']['status']);});
    });
}
test('tabela mesclada ou aninhada não é declarada integralmente suportada',function(){
    foreach(['merge','nested']as$case){$p=compoundDocxParts(false,true);
        if($case==='merge'){$p['word/document.xml']=str_replace('<w:tc>','<w:tc><w:tcPr><w:gridSpan w:val="2"/></w:tcPr>',$p['word/document.xml']);}
        else{$p['word/document.xml']=str_replace('<w:tc>','<w:tc>'.simpleVisualTable(),$p['word/document.xml']);}
        withDocx($p,function($path){same(false,isset((new DocxReader())->read($path)['main_text']));});
    }
});
test('bibliografia completa fica analisável após figuras, tabela e metadados Word',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace(standardSection(),docxBody(['SILVA, João. Livro. Cidade: Editora, 2020.']).standardSection(),$p['word/document.xml']);
    $p=addWordAdvisories($p,'standardContextual');
    withDocx($p,function($path){$d=(new DocxReader())->read($path);$c=scopedChecks($d);same('verified_reference_tail',$c['references.apa7']['evidence']['source_profile']);
        same('não conforme',$c['references.apa7']['status']);same('indeterminado',$c['references.citation_matching']['status']);
        same('conforme',$c['manuscript.footnotes.count']['status']);same(false,$c['references.apa7']['evidence']['full_document_text_available']);
    });
});
test('texto em propriedades não é promovido a corpo ou falso marcador Referências',function(){
    foreach([false,true]as$compound){$p=$compound?compoundDocxParts():formattingParts();
        $p['word/document.xml']=str_replace('Referências','Outra seção',$p['word/document.xml']);
        $p['word/document.xml']=str_replace('<w:body>','<w:body><w:p><w:pPr><w:r><w:t>Referências</w:t></w:r></w:pPr></w:p>',$p['word/document.xml']);
        withDocx($p,function($path){$d=(new DocxReader())->read($path);same(false,$d['available']);same(false,isset($d['main_text']));same(false,isset($d['reference_tail']));});
    }
});
test('limite de parágrafos e desenhos impede inspeção adicional sem falso resultado editorial',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace('<w:body>','<w:body>'.str_repeat('<w:p/>',4097),$p['word/document.xml']);
    withDocx($p,function($path){same(false,isset((new DocxReader())->read($path)['main_text']));});
    $p=compoundDocxParts();$p['word/document.xml']=str_replace('<w:body>','<w:body>'.str_repeat(visualPicture(),257),$p['word/document.xml']);
    withDocx($p,function($path){same(false,isset((new DocxReader())->read($path)['main_text']));});
});
test('DTD continua recusado antes de aplicar normalização ou perfil parcial',function(){
    $p=addWordAdvisories(compoundDocxParts());$p['word/document.xml']='<!DOCTYPE x [<!ENTITY y SYSTEM "file:///private">]>'.$p['word/document.xml'];
    withDocx($p,function($path){$d=(new DocxReader())->read($path);same(false,$d['available']);same(false,isset($d['main_text']));same(false,isset($d['reference_tail']));});
});
test('relatório e devolutiva não expõem texto/tabela/imagem ou caminhos privados',function(){
    $p=compoundDocxParts();$p['word/document.xml']=str_replace('Dado A','PRIVATE_TABLE_DATA',$p['word/document.xml']);
    $p['word/document.xml']=str_replace('<wp:inline>','<wp:inline><wp:docPr id="1" name="PRIVATE_IMAGE_NAME"/>',$p['word/document.xml']);
    withDocx($p,function($path){$d=(new DocxReader())->read($path);$checks=scopedChecks($d);$reply=deskFromChecks(array_values($checks));$encoded=json_encode([$checks,$reply]);
        same(false,strpos($encoded,'PRIVATE_TABLE_DATA')!==false);same(false,strpos($encoded,'PRIVATE_IMAGE_NAME')!==false);same(false,strpos($encoded,$path)!==false);
    });
});
test('motor novo invalida liberação ligada ao fingerprint do leitor anterior',function(){
    withReview(function($service,$request,$form,$path,$dir){$analysis=(new EstrabaoAdmissionBridge())->analyze($form);$old=$analysis['binding'];$old['engine_version']='0.12.0';
        $ledger=['schema_version'=>1,'context_id'=>3,'submission_id'=>17,'events'=>[['sequence'=>1,'action'=>'approve','reviewer_id'=>77,'recorded_at'=>gmdate('Y-m-d\TH:i:s\Z'),'fingerprint'=>hash('sha256',json_encode($old))]]];
        file_put_contents($dir.'/review-3-17.json',json_encode($ledger));chmod($dir.'/review-3-17.json',0600);
        same(false,$service->allows($form,$analysis));
    });
});

test('painel consome perfil parcial e versões novas sem expor conteúdo excluído',function(){
    withDeskPanel(function($service,$request){
        $p=compoundDocxParts();$p['word/styles.xml']=str_replace('w:sz w:val="24"','w:sz w:val="20"',$p['word/styles.xml']);
        withDocx($p,function($path)use($service,$request){
            Services::$files[0]->data['path']=basename($path);
            $form=$service->form($request,17);$request->vars['fingerprint']=(new EstrabaoAdmissionBridge())->analyze($form)['fingerprint'];
            $r=$service->draft($request,17);same('0.15.0',$r['draft']['source_engine_version']);
            same(true,strpos($r['draft']['body'],'12 pontos')!==false);same(hash_file('sha256',$path),$r['draft']['source_document_sha256']);
            same(false,strpos(json_encode($r),'Dado A')!==false);
        });
    });
});
