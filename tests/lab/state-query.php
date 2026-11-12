<?php
chdir('/var/www/html');$c=parse_ini_file('config.inc.php',true)['database'];$db=new PDO('mysql:host='.$c['host'].';dbname='.$c['name'],$c['username'],$c['password']);echo json_encode($db->query('SELECT submission_id,submission_progress,date_submitted,status FROM submissions ORDER BY submission_id')->fetchAll(PDO::FETCH_ASSOC));
