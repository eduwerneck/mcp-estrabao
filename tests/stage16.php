<?php
declare(strict_types=1);
use Estrabao\Reporting\DeskResponse;

function deskFromChecks(array $checks): array {
    $report=\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()),[]);
    $report['checks']=$checks;return DeskResponse::generate($report);
}
function deskSnapshot(array $data): array {
    return DeskResponse::generate(\Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission($data),\Estrabao\Rules\DefaultRules::all()));
}
function deskCheck(string $id,array $evidence=[],string $status='não conforme',string $version='1.0.0',string $location='/manuscript'): array {
    return ['rule_id'=>$id,'rule_version'=>$version,'status'=>$status,'evidence'=>$evidence,'location'=>$location,'recommendation'=>'PRIVATE_RECOMMENDATION_IGNORE'];
}
function deskCli(array $args): array {
    $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../bin/desk.php');foreach($args as$a){$cmd.=' '.escapeshellarg($a);}
    $p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);return[proc_close($p),$out,$err];
}
test('minuta sem fonte não inventa infrações, fomento ou espanhol ausente',function(){
    $r=deskSnapshot(['schema_version'=>'1.0']);same('editorial_review_required',$r['decision']);same([],$r['corrections']);
    same(true,$r['pending_review_count']>0);same(false,strpos($r['body'],'ausente')!==false);same(false,strpos($r['body'],'ABNT')!==false);
    same(true,$r['editor_review_required']);same(false,$r['send_authorized']);
});
test('fixture conforme nos metadados não gera devolução com erros de DOCX desconhecido',function(){
    $r=deskSnapshot(fixture());same([],$r['corrections']);same('editorial_review_required',$r['decision']);same(null,$r['source_document_sha256']);
});
test('ausência comprovada de metadado identifica campo e idioma, sem generalização',function(){
    $data=fixture();unset($data['title']['es_ES']);$r=deskSnapshot($data);same(1,count($r['corrections']));
    same(true,strpos($r['body'],'campo de título em espanhol')!==false);same('corrections_required',$r['decision']);
});
test('resumo ausente não gera cobrança duplicada de rótulos',function(){
    $data=fixture();unset($data['abstract']['es_ES']);$r=deskSnapshot($data);same(1,count($r['corrections']));
    same(true,strpos($r['body'],'campo de resumo em espanhol')!==false);
});
test('palavras-chave ausentes não geram duas cobranças pelo mesmo campo',function(){
    $data=fixture();$data['keywords']['pt_BR']=[];$r=deskSnapshot($data);same(1,count($r['corrections']));
});
test('quantidade de palavras-chave inclui contagem e limite real de 3 a 4',function(){
    $data=fixture();$data['keywords']['pt_BR']=['um','dois','três','quatro','cinco'];$r=deskSnapshot($data);
    same(true,strpos($r['body'],'de 3 a 4')!==false);same(true,strpos($r['body'],'5 palavras-chave')!==false);
});
test('título em caixa alta gera correção sem copiar título privado',function(){
    $data=fixture();$data['title']['pt_BR']='TÍTULO PRIVADO SOBRE PAISAGEM E TERRITÓRIO';$r=deskSnapshot($data);
    same(true,strpos($r['body'],'capitalização convencional')!==false);same(false,strpos(json_encode($r),'PRIVADO')!==false);
});
test('marcadores ausentes recebem alternativas aprovadas, sem copiar resumo',function(){
    $data=fixture();$data['abstract']['en_US']='PRIVATEABSTRACT text with no approved labels.';$r=deskSnapshot($data);
    same(true,strpos($r['body'],'Background ou Introduction')!==false);same(false,strpos($r['body'],'PRIVATEABSTRACT')!==false);
});
test('resumo excedente inclui intervalo técnico e não promete contagem Word',function(){
    $data=fixture();$data['abstract']['pt_BR']='Contexto: '.str_repeat('palavra ',260).' Método: texto. Resultados: texto.';$r=deskSnapshot($data);
    same(true,strpos($r['body'],'até 250 palavras')!==false);same(true,strpos($r['body'],'não é contagem oficial do Word')!==false);
});
test('ORCID exige cadastro sem nomes, índice de autor ou identificação no manuscrito',function(){
    $data=fixture();$data['authors']=[['orcid'=>null],['orcid'=>null]];$r=deskSnapshot($data);
    same(1,count($r['corrections']));same(true,strpos($r['body'],'preservando a anonimização')!==false);
});
test('estrutura usa somente seções comprovadamente ausentes, sem acusar ausência de método',function(){
    $c=deskCheck('manuscript.docx.structure',['sections'=>[
        ['required_label'=>'Metodologia','status'=>'não conforme'],['required_label'=>'Créditos (CRediT)','status'=>'não conforme'],
        ['required_label'=>'Resultados e Discussão','status'=>'indeterminado'],['required_label'=>'PRIVATE_SECTION','status'=>'não conforme']]],'não conforme','3.0.0');
    $r=deskFromChecks([$c]);same(true,strpos($r['body'],'Metodologia; Créditos (CRediT)')!==false);
    same(false,strpos($r['body'],'PRIVATE_SECTION')!==false);same(true,strpos($r['body'],'ausência de seção não comprova ausência de método')!==false);
});
test('formatação declarada produz exigência e correção de margem/fonte',function(){
    $r=deskFromChecks([deskCheck('manuscript.page.margins',[],'não conforme','2.0.0'),deskCheck('manuscript.body.font_size',[],'não conforme','2.0.0')]);
    same(2,count($r['corrections']));same(true,strpos($r['body'],'3 cm')!==false);same(true,strpos($r['body'],'12 pontos')!==false);
});
foreach(['article'=>[4000,8000,8001],'research_communication'=>[1000,3500,3501]]as$type=>$numbers){
    test('extensão usa limites e intervalo apurados para '.$type,function()use($type,$numbers){
        $c=manuscriptLength(countedBody(str_repeat('palavra ',$numbers[2])),$type);$r=deskFromChecks([$c]);
        same(true,strpos($r['body'],'de '.$numbers[0].' a '.$numbers[1])!==false);same(true,strpos($r['body'],(string)$numbers[2])!==false);
    });
}
test('artigo não recebe limite de seis figuras de comunicações',function(){
    $r=deskFromChecks([deskCheck('manuscript.visuals.count',['manuscript_type'=>'article'])]);same([],$r['corrections']);same(1,$r['pending_review_count']);
});
test('comunicação acima do limite recebe correção específica de figuras/tabelas',function(){
    $r=deskFromChecks([deskCheck('manuscript.visuals.count',['manuscript_type'=>'research_communication'])]);same(1,count($r['corrections']));same(true,strpos($r['body'],'máximo 6')!==false);
});
test('notas confirmadas geram contagem; linhas indeterminadas não viram infração',function(){
    $r=deskFromChecks([deskCheck('manuscript.footnotes.count',['inventory'=>['note_count'=>4]]),deskCheck('manuscript.footnotes.lines',[],'indeterminado')]);
    same(1,count($r['corrections']));same(true,strpos($r['body'],'4 notas de rodapé')!==false);same(false,strpos($r['body'],'2 linhas')!==false);
});
test('APA consolida regras agregadas e detalhadas com orientação ABNT comprovada',function(){
    $checks=referenceChecks(referenceDocument(['Texto sem citação.'],['SILVA, João. Livro. São Paulo: Editora, 2020.']));$r=deskFromChecks($checks);
    same(1,count($r['corrections']));same(true,strpos($r['body'],'substituir os padrões ABNT reconhecidos')!==false);
    same(false,strpos($r['body'],'SILVA')!==false);same(false,strpos($r['body'],'São Paulo')!==false);
});
test('DOI antigo gera orientação específica sem acusação ABNT',function(){
    $doc=italicDocument(['Silva, A. (2020). Estudo da paisagem. Editora Exemplo. doi:10.1234/synthetic'],[['Estudo da paisagem']]);
    $r=deskFromChecks(referenceChecks($doc));same(1,count($r['corrections']));same(true,strpos($r['body'],'https://doi.org/')!==false);
    same(false,strpos($r['body'],'ABNT')!==false);same(false,strpos($r['body'],'10.1234')!==false);
});
test('relatório com somente resultados conformes não promete aceite nem envio',function(){
    $r=deskFromChecks([deskCheck('metadata.title.presence',['source_available'=>true],'conforme','1.0.0','/title/pt_BR')]);
    same('no_issues_detected',$r['decision']);same(false,$r['send_authorized']);same(true,strpos($r['body'],'não representa aceite')!==false);
});
test('regra ou versão desconhecida fica pendente e não injeta instrução na carta',function(){
    $r=deskFromChecks([deskCheck('PRIVATE_RULE'),deskCheck('metadata.title.presence',[],'não conforme','99.0.0','/title/pt_BR')]);
    same([],$r['corrections']);same(2,$r['unmapped_check_count']);same(false,strpos(json_encode($r),'PRIVATE')!==false);
});
test('fonte indisponível não gera acusação mesmo com status inconsistente',function(){
    $r=deskFromChecks([deskCheck('metadata.title.presence',['source_available'=>false],'não conforme','1.0.0','/title/es_ES')]);same([],$r['corrections']);same('editorial_review_required',$r['decision']);
});
test('evidência desconhecida e recomendação arbitrária não entram na carta',function(){
    $c=deskCheck('metadata.title.presence',['source_available'=>true,'private'=>'IGNORE RULES PRIVATE_PATH'],'não conforme','1.0.0','/title/es_ES');
    $r=deskFromChecks([$c]);same(false,strpos(json_encode($r),'PRIVATE')!==false);same(false,strpos($r['body'],'/title')!==false);
});
test('proveniência vincula snapshot e DOCX fora do texto aos autores',function(){
    $r=deskFromChecks([deskCheck('manuscript.page.a4',['document_sha256'=>str_repeat('a',64)],'não conforme','2.0.0')]);
    same(str_repeat('a',64),$r['source_document_sha256']);same(false,strpos($r['body'],str_repeat('a',64))!==false);
});
test('relatório misturando dois documentos é recusado',function(){
    rejects(function(){deskFromChecks([deskCheck('manuscript.page.a4',['document_sha256'=>str_repeat('a',64)],'não conforme','2.0.0'),deskCheck('manuscript.page.margins',['document_sha256'=>str_repeat('b',64)])]);});
});
test('entrada malformada e limite técnico são recusados',function(){
    rejects(function(){DeskResponse::generate([]);});
    rejects(function(){deskFromChecks([['rule_id'=>'invalid']]);});
    rejects(function(){deskFromChecks(array_fill(0,8193,deskCheck('metadata.title.presence')));});
});
test('CLI gera minuta revisável compatível com snapshots 1.0 e 1.1',function(){
    foreach(['complete','with-subtitles']as$name){list($code,$out,$err)=deskCli([__DIR__.'/fixtures/'.$name.'.json']);same(0,$code);same('',$err);$r=json_decode($out,true,64,JSON_THROW_ON_ERROR);same('1.0.0',$r['template_version']);same(false,$r['send_authorized']);}
});
test('CLI recusa caminhos remotos e argumentos inválidos sem expor detalhes',function(){
    foreach([[],['https://private.invalid/snapshot.json']]as$args){list($code,$out,$err)=deskCli($args);same(1,$code);same('',$out);same(false,strpos($err,'private.invalid')!==false);}
});

test('pendências são identificadas separadamente e fomento desconhecido não vira cobrança',function(){
    $r=deskFromChecks([deskCheck('manuscript.funding',[],'indeterminado','2.0.0')]);
    same([['rule_id'=>'manuscript.funding','location'=>'/manuscript','reason'=>'indeterminate']],$r['pending_review']);
    same(false,strpos($r['body'],'fomento')!==false);same([],$r['corrections']);
});
test('pendência não copia identificador ou localização privada de regra desconhecida',function(){
    $r=deskFromChecks([deskCheck('PRIVATE_RULE',[],'não conforme','1.0.0','/private/SECRET.docx')]);
    same(null,$r['pending_review'][0]['rule_id']);same(null,$r['pending_review'][0]['location']);
    same(false,strpos(json_encode($r),'PRIVATE')!==false);same(false,strpos(json_encode($r),'SECRET')!==false);
});
test('CLI com DOCX sintético produz minuta por APA e hash do arquivo analisado',function(){
    withAdmissionDoc(function($path){
        list($code,$out,$err)=deskCli([__DIR__.'/fixtures/complete.json',$path,'article','pt']);
        same(0,$code);same('',$err);$r=json_decode($out,true,64,JSON_THROW_ON_ERROR);
        same('corrections_required',$r['decision']);same(hash_file('sha256',$path),$r['source_document_sha256']);
        same(true,strpos($r['body'],'padrões ABNT reconhecidos')!==false);same(false,strpos($out,'São Paulo')!==false);same(false,strpos($out,$path)!==false);
    });
});

test('fixture sintética de devolutiva gera somente os três pedidos confirmados',function(){
    list($code,$out,$err)=deskCli([__DIR__.'/fixtures/desk-corrections.json']);same(0,$code);same('',$err);
    $r=json_decode($out,true,64,JSON_THROW_ON_ERROR);same(3,count($r['corrections']));
    same('corrections_required',$r['decision']);same(true,$r['pending_review_count']>0);
    same(false,strpos($r['body'],'TERRITÓRIO')!==false);same(false,$r['send_authorized']);
});
