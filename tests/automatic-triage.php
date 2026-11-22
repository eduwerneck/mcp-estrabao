<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../plugins/generic/estrabao/AutomaticTriagePolicy.inc.php';
$failures=0;$count=0;
function verifyAuto($label,$condition){global $count,$failures;$count++;if(!$condition){$failures++;}echo ($condition?'PASS ':'FAIL ').$label."\n";}
$apa="Silva, A. (2020). Paisagem sintética. Editora Exemplo.\nSouza, B. (2021). Ensino sintético. Editora Exemplo.";
$abnt='SILVA, Ana. Paisagem sintética. São Paulo: Editora Exemplo, 2020.';
$declared=['pt_BR'=>['Sem financiamento']];
verifyAuto('empty bibliography and funding produce separate corrections',count(EstrabaoAutomaticTriagePolicy::metadataIssues('',[]))===2);
verifyAuto('explicit no funding is a valid declaration',count(EstrabaoAutomaticTriagePolicy::metadataIssues($apa,$declared))===0);
verifyAuto('declared agency is valid',count(EstrabaoAutomaticTriagePolicy::metadataIssues($apa,['pt_BR'=>['CNPq — processo sintético']]))===0);
$issues=EstrabaoAutomaticTriagePolicy::metadataIssues($abnt,$declared);
verifyAuto('recognized ABNT in OJS bibliography produces a correction',count($issues)===1&&strpos($issues[0],'Referências nos metadados')!==false);
verifyAuto('uppercase alone is inconclusive, not ABNT',count(EstrabaoAutomaticTriagePolicy::metadataIssues('NASA',$declared))===0);
verifyAuto('unresolved bibliography does not cause automatic accusation',count(EstrabaoAutomaticTriagePolicy::metadataIssues('Obra sem identificação segura',$declared))===0);
verifyAuto('empty HTML is absent bibliography',count(EstrabaoAutomaticTriagePolicy::metadataIssues('<p>&nbsp;</p>',$declared))===1);
verifyAuto('letter requires new submission after rejection',strpos(EstrabaoAutomaticTriagePolicy::letter($issues),'nova submissão')!==false);
verifyAuto('pending letter does not reject',strpos(EstrabaoAutomaticTriagePolicy::letter([]),'aguarda conferência editorial')!==false);
verifyAuto('letter preserves reason without pending accusation',strpos(EstrabaoAutomaticTriagePolicy::letter($issues),'26')===false);
echo "$count testes, $failures falhas\n";exit($failures?1:0);
