<?php
declare(strict_types=1);

foreach ([': ', ' – ', ' — '] as $separator) {
    test('título combinado não esconde subtítulo em caixa alta ' . $separator, function () use ($separator) {
        $data=fixture(); $data['schema_version']='1.1';
        $data['title']['pt_BR']='Paisagem e educação'; $data['subtitle']=['pt_BR'=>'ESTUDO DA PAISAGEM'];
        $text=$data['title']['pt_BR'].$separator.$data['subtitle']['pt_BR'];
        $check=docHeadingCase(docxParts(docxBody(array_merge([$text],labels()))),$data);
        same('não conforme',$check['status']); same('3.0.0',$check['rule_version']);
        same(true,$check['evidence']['samples'][0]['heading_confirmed']);
        same(2,$check['evidence']['samples'][0]['matched_metadata_parts']);
        same(false,strpos(json_encode($check),'ESTUDO DA PAISAGEM'));
    });
}
test('idioma do título localizado prevalece sobre idioma principal do manuscrito',function(){
    $data=fixture();$data['title']['en_US']='THE STUDY OF LANDSCAPES';
    $c=docHeadingCase(docxParts(docxBody(array_merge([$data['title']['en_US']],labels()))),$data,'pt');
    same('não conforme',$c['status']);same('full_uppercase_phrase',$c['evidence']['samples'][0]['reason']);
});
test('título e subtítulo com siglas preservam ambiguidade sem converter texto',function(){
    $data=fixture();$data['schema_version']='1.1';$data['title']['pt_BR']='Paisagem e educação';$data['subtitle']=['pt_BR'=>'DNA RNA'];
    $c=docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação: DNA RNA'],labels()))),$data);
    same('indeterminado',$c['status']);same('full_uppercase_acronym_review',$c['evidence']['samples'][0]['reason']);
});
test('título misto combinado passa no escopo de caixa sem exigir subtítulo',function(){
    $data=fixture();$data['schema_version']='1.1';$data['title']['pt_BR']='Paisagem e educação';$data['subtitle']=['pt_BR'=>'DNA e RNA'];
    same('conforme',docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação: DNA e RNA'],labels()))),$data)['status']);
    $data['subtitle']=[];same('conforme',docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação'],labels()))),$data)['status']);
});
test('não combina título e subtítulo de idiomas distintos',function(){
    $data=fixture();$data['schema_version']='1.1';$data['title']['pt_BR']='Paisagem e educação';$data['subtitle']=['es_ES'=>'ESTUDIO DEL PAISAJE'];
    $c=docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação: ESTUDIO DEL PAISAJE'],labels()))),$data);
    same(0,$c['evidence']['samples'][0]['matched_metadata_parts']??0);
    foreach($c['evidence']['samples'] as $sample){if($sample['paragraph_index']===0){same(false,$sample['heading_confirmed']);}}
});
test('metadados correspondentes depois de seção não promovem prosa a título',function(){
    $data=fixture();$data['schema_version']='1.1';$data['subtitle']=['pt_BR'=>'ESTUDO DA PAISAGEM'];
    same('conforme',docHeadingCase(docxParts(docxBody(array_merge(labels(),[$data['title']['pt_BR'].': ESTUDO DA PAISAGEM']))),$data)['status']);
});
test('devolutiva aceita versão nova da regra de caixa e orienta correção',function(){
    $data=fixture();$data['schema_version']='1.1';$data['title']['pt_BR']='Paisagem e educação';$data['subtitle']=['pt_BR'=>'ESTUDO DA PAISAGEM'];
    withDocx(docxParts(docxBody(array_merge(['Paisagem e educação: ESTUDO DA PAISAGEM'],labels()))),function($path)use($data){
        $submission=new \Estrabao\Contracts\Submission($data);
        $document=(new \Estrabao\Document\DocxReader())->read($path);
        $report=\Estrabao\Reporting\Report::generate($submission,\Estrabao\Rules\DefaultRules::all($document,'article','pt'));
        // The public generator must recognize the new rule version.
        $draft=(new \Estrabao\Reporting\DeskResponse())->generate($report);
        same(true,strpos(json_encode($draft,JSON_UNESCAPED_UNICODE),'caixa alta integral')!==false);
    });
});
class TitleGuidanceManager {
    public $scripts=[];
    function addJavaScript($name,$script,$args){$this->scripts[$name]=[$script,$args];}
}
class TitleGuidanceRequest extends AdmissionTestRequest {
    function getBaseUrl(){return '/ojs';}
}
class TitleGuidancePlugin extends EstrabaoPlugin {
    function getPluginPath(){return 'plugins/generic/estrabao';}
}
test('orientação de formulário funciona sem ativar bloqueio APA e preserva valores',function(){
    $p=new TitleGuidancePlugin();$p->enabled=true;Config::$values=[];
    Application::$request=new TitleGuidanceRequest();Application::$request->context=new AdmissionTestRecord(['id'=>3]);
    $config=['id'=>'titleAbstract','fields'=>[['name'=>'title','value'=>['pt_BR'=>'DNA RNA'],'description'=>'Existing'],['name'=>'subtitle','value'=>[]],['name'=>'abstract','value'=>'Private abstract']]];
    $original=$config;$p->titleFormConfig('Form::config::after',[&$config,null]);
    same($original['fields'][0]['value'],$config['fields'][0]['value']);same([],$config['fields'][1]['value']);
    same($original['fields'][2],$config['fields'][2]);same(true,strpos($config['fields'][0]['description'],'Existing')===0);
    same(true,strpos($config['fields'][1]['description'],'titleGuidance')!==false);
});
foreach (['disabled','no_context','other_form'] as $case) {
    test('orientação respeita escopo '.$case,function()use($case){
        $p=new TitleGuidancePlugin();$p->enabled=$case!=='disabled';
        Application::$request=new TitleGuidanceRequest();Application::$request->context=$case==='no_context'?null:new AdmissionTestRecord(['id'=>3]);
        $config=['id'=>$case==='other_form'?'other':'titleAbstract','fields'=>[['name'=>'title','value'=>'Synthetic']]];$original=$config;
        $p->titleFormConfig('Form::config::after',[&$config,null]);same($original,$config);
        if($case!=='other_form'){$m=new TitleGuidanceManager();$p->titleFormAssets('TemplateManager::display',[$m]);same([],$m->scripts);}
    });
}
test('assets de orientação não incluem dados do artigo e usam caminho do plugin',function(){
    $p=new TitleGuidancePlugin();$p->enabled=true;Application::$request=new TitleGuidanceRequest();Application::$request->context=new AdmissionTestRecord(['id'=>3]);
    $m=new TitleGuidanceManager();$p->titleFormAssets('TemplateManager::display',[$m]);
    same('/ojs/plugins/generic/estrabao/title-guidance.js',$m->scripts['estrabao-title-guidance'][0]);
    same(['backend'],$m->scripts['estrabao-title-guidance'][1]['contexts']);
    same(true,strpos($m->scripts['estrabao-title-guidance-config'][0],'titleGuidance')!==false);
});

test('identidade usa metadados mas caixa usa o texto efetivo do Word',function(){
    $data=fixture();$data['schema_version']='1.1';$data['title']['pt_BR']='Paisagem e educação';$data['subtitle']=['pt_BR'=>'Estudo da paisagem'];
    same('não conforme',docHeadingCase(docxParts(docxBody(array_merge(['PAISAGEM E EDUCAÇÃO'],labels()))),$data)['status']);
    same('não conforme',docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação: ESTUDO DA PAISAGEM'],labels()))),$data)['status']);
    $data['subtitle']['pt_BR']='ESTUDO DA PAISAGEM';
    same('conforme',docHeadingCase(docxParts(docxBody(array_merge(['Paisagem e educação: Estudo da paisagem'],labels()))),$data)['status']);
});
