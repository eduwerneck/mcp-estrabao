<?php
// Browser transport for the existing, isolated synthetic lab. Never installed in the web root.
$c=parse_ini_file('/var/www/html/config.inc.php',true);
if(($c['general']['base_url']??'')!=='http://127.0.0.1:18080'||($c['database']['name']??'')!=='estrabao_synthetic'||($c['database']['host']??'')!=='estrabao-lab-62-restore-db'){exit(1);}
$p=json_decode(stream_get_contents(STDIN),true);
if(!is_string($p['path']??null)||substr($p['path'],0,1)!=='/'||substr($p['path'],0,2)==='//'){exit(1);}
$ch=curl_init('http://127.0.0.1:18080'.$p['path']);
$headers=[];foreach($p['headers']??[]as$k=>$v){if(!in_array(strtolower($k),['host','content-length','connection','accept-encoding'])){$headers[]=$k.': '.$v;}}
curl_setopt_array($ch,[CURLOPT_PROXY=>'',CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CUSTOMREQUEST=>$p['method'],CURLOPT_HTTPHEADER=>$headers,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false]);
if(isset($p['body'])){curl_setopt($ch,CURLOPT_POSTFIELDS,base64_decode($p['body']));}
$out=curl_exec($ch);if($out===false){exit(1);}$n=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
echo json_encode(['status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),'headers'=>substr($out,0,$n),'body'=>base64_encode(substr($out,$n))]);
