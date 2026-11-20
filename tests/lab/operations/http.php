<?php
if(getenv('ESTRABAO_SYNTHETIC_LAB')!=='1'){exit(1);}
$c=parse_ini_file('/var/www/html/config.inc.php',true);
if(($c['general']['base_url']??'')!=='http://127.0.0.1:18080'||($c['database']['name']??'')!=='estrabao_synthetic'){exit(1);}
$p=json_decode(stream_get_contents(STDIN),true);$id=$p['session']??'';$path=$p['path']??'';$method=$p['method']??'';
if(!preg_match('/^[a-z0-9_]{1,24}$/D',$id)||strpos($path,'/index.php/')!==0||!in_array($method,['GET','POST'],true)){exit(1);}
umask(0077);$dir='/tmp/estrabao62-http';if(!is_dir($dir)){mkdir($dir,0700);} $jar=$dir.'/'.$id;
$ch=curl_init('http://127.0.0.1:18080'.$path);curl_setopt_array($ch,[CURLOPT_PROXY=>'',CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_COOKIEJAR=>$jar,CURLOPT_COOKIEFILE=>$jar,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>4,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTP]);
if($method==='POST'){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($p['data']??[]));}
$body=curl_exec($ch);if($body===false){exit(1);}echo json_encode(['status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),'body'=>$body]);curl_close($ch);
