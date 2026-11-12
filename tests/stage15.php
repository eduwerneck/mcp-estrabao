<?php
declare(strict_types=1);

test('catálogo mestre en_US permite carregar traduções do plugin no OJS real', function () {
    $base=__DIR__.'/../plugins/generic/estrabao/locale/';$expected=null;
    foreach(['en_US','pt_BR','es_ES']as$locale){
        $text=file_get_contents($base.$locale.'/locale.po');preg_match_all('/^msgid "(plugins\.generic\.estrabao\.[^"]+)"$/m',$text,$matches);
        $keys=$matches[1];sort($keys);same(true,count($keys)>20);
        if($expected===null){$expected=$keys;}else{same($expected,$keys);}
        same(true,strpos($text,'Language: '.$locale)!==false);
    }
});
test('bloqueio mantém processamento nativo que apresenta os erros ao autor', function () {
    withAdmissionDoc(function($path){
        $form=admissionSetup($path);$plugin=new EstrabaoPlugin();$plugin->enabled=true;$value=null;
        same(false,$plugin->validateAdmission('submissionsubmitstep4form::validate',[$form,&$value]));
        same(false,$value);same(true,count($form->errors)>1);
        same(true,isset($form->errors['estrabaoCorrection0']));
    });
});
test('devolutiva APA contém correções comprovadas sem copiar dados bibliográficos', function () {
    withAdmissionDoc(function($path){
        $form=admissionSetup($path);$analysis=(new EstrabaoAdmissionBridge())->analyze($form);
        same('not_accepted',$analysis['decision']);same(true,count($analysis['corrections'])>0);
        same(true,count($analysis['corrections'])<=5);$text=implode(' ',$analysis['corrections']);
        same(true,strpos($text,'APA 7')!==false);same(false,strpos($text,'SILVA')!==false);same(false,strpos($text,'São Paulo')!==false);
    });
});
test('fonte inconclusiva não recebe acusação ABNT nem correções inventadas', function () {
    withReview(function($service,$request,$form){
        $analysis=(new EstrabaoAdmissionBridge())->analyze($form);same('manual_review_required',$analysis['decision']);
        same([],$analysis['corrections']);$plugin=new EstrabaoPlugin();$plugin->enabled=true;$value=null;
        same(false,$plugin->validateAdmission('submissionsubmitstep4form::validate',[$form,&$value]));
        same(['estrabaoAdmission'=>'plugins.generic.estrabao.apaReviewRequired'],$form->errors);
    });
});
