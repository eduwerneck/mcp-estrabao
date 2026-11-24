<?php
class Config { public static $values=[]; static function getVar($section,$key){return self::$values[$key]??null;} }
require __DIR__.'/../plugins/generic/estrabao/TestScope.inc.php';
$n=0;$failed=0;
function checkScope($name,$value){global $n,$failed;$n++;if(!$value){$failed++;}echo ($value?'PASS ':'FAIL ').$name."\n";}
checkScope('absent setting preserves unrestricted lab mode',EstrabaoTestScope::matches(23));
Config::$values['test_submission_id']=23;
checkScope('configured ID is accepted',EstrabaoTestScope::matches('23'));
checkScope('another submission is excluded',!EstrabaoTestScope::matches(24));
checkScope('missing request ID cannot select target',!EstrabaoTestScope::matches(null));
foreach([0,-1,'',false,true,23.0,[], '23&submissionId=24','2.3','023','9999999999']as$id){Config::$values['test_submission_id']=$id;checkScope('invalid scope fails closed '.json_encode($id),!EstrabaoTestScope::matches(23));}
Config::$values=['test_submission_id'=>23,'test_recipient'=>'scope-only@synthetic.invalid'];
checkScope('controlled recipient is accepted',EstrabaoTestScope::recipient()==='scope-only@synthetic.invalid');
Config::$values['test_recipient']="scope-only@synthetic.invalid\r\nBcc:other@example.com";
checkScope('recipient header injection is refused',EstrabaoTestScope::recipient()===null);
echo "$n testes, $failed falhas\n";exit($failed?1:0);
