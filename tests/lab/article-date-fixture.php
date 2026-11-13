<?php
// Reversible database fixture for an internal synthetic lab; never a deployment tool.
if (getenv('ESTRABAO_SYNTHETIC_LAB') !== '1') { exit(1); }
chdir('/var/www/html'); $all = parse_ini_file('config.inc.php', true);
if (($all['general']['base_url'] ?? null) !== 'http://127.0.0.1:18080'
    || ($all['database']['name'] ?? null) !== 'estrabao_synthetic') { exit(1); }
$c = $all['database']; $db = new PDO('mysql:host='.$c['host'].';dbname='.$c['name'], $c['username'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$backup = '/tmp/estrabao-stage22-dates.json'; $mode = $argv[1] ?? '';
$id = (int) json_decode(file_get_contents('/tmp/lab-state.json'), true)['submissions']['review']['id'];
$s = $db->query('SELECT submission_id,status,submission_progress,date_submitted,current_publication_id FROM submissions WHERE submission_id='.$id)->fetch(PDO::FETCH_ASSOC);
$p = $db->query('SELECT publication_id,status,date_published FROM publications WHERE publication_id='.(int)$s['current_publication_id'])->fetch(PDO::FETCH_ASSOC);
if ($mode === 'state') { echo json_encode([$s,$p]); exit; }
if ($mode === 'restore') {
    if (!is_file($backup)) { exit; } [$s,$p] = json_decode(file_get_contents($backup), true);
    $db->beginTransaction();
    $db->prepare('UPDATE submissions SET status=?,submission_progress=?,date_submitted=? WHERE submission_id=?')->execute([$s['status'],$s['submission_progress'],$s['date_submitted'],$s['submission_id']]);
    $db->prepare('UPDATE publications SET status=?,date_published=? WHERE publication_id=?')->execute([$p['status'],$p['date_published'],$p['publication_id']]);
    $db->commit(); unlink($backup); exit;
}
if (!in_array($mode, ['published','missing','incoherent','preview'], true)) { exit(1); }
if (!is_file($backup)) { file_put_contents($backup, json_encode([$s,$p])); chmod($backup,0600); }
$date = $mode === 'missing' ? null : ($mode === 'incoherent' ? '2026-03-06 12:00:00' : '2026-01-02 00:10:00');
$db->beginTransaction();
$db->prepare('UPDATE submissions SET status=3,submission_progress=0,date_submitted=? WHERE submission_id=?')->execute([$date,$id]);
$db->prepare('UPDATE publications SET status=?,date_published=? WHERE publication_id=?')->execute([$mode==='preview'?1:3,'2026-02-04',$p['publication_id']]);
$db->commit();
