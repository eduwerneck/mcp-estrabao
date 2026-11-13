<?php
// Synthetic lab only. Replace file bytes temporarily; do not edit DAO/publication state.
if (getenv('ESTRABAO_SYNTHETIC_LAB') !== '1') { exit(1); }
chdir('/var/www/html');
$c = parse_ini_file('config.inc.php', true);
if (($c['general']['base_url'] ?? null) !== 'http://127.0.0.1:18080'
    || ($c['database']['name'] ?? null) !== 'estrabao_synthetic') { exit(1); }
require 'tools/bootstrap.inc.php'; new CommandLineTool([]);
$s = json_decode(file_get_contents('/tmp/lab-state.json'), true)['submissions']['abnt'];
$f = Services::get('submissionFile')->get($s['submission_file_id']);
$source = Config::getVar('files', 'files_dir') . '/' . $f->getData('path');
$backup = '/tmp/estrabao-stage20-original.docx'; $mode = $argv[1] ?? '';
if ($mode === 'restore') { if (is_file($backup)) { copy($backup, $source); unlink($backup); } exit; }
if (!in_array($mode, ['3', '4', '0', 'endnotes', 'mixed'], true)) { exit(1); }
if (!is_file($backup) && !copy($source, $backup)) { exit(1); }
$w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
$rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
$count = ctype_digit($mode) ? (int) $mode : 0; $refs = ''; $notes = '';
for ($i = 1; $i <= $count; $i++) {
    $refs .= '<w:r><w:footnoteReference w:id="' . $i . '"/></w:r>';
    $notes .= '<w:footnote w:id="' . $i . '"><w:p><w:r><w:footnoteRef/><w:t>Nota de laboratório.</w:t></w:r></w:p></w:footnote>';
}
if ($mode === 'endnotes') { $refs .= '<w:r><w:endnoteReference w:id="7"/></w:r>'; }
$reference = $mode === 'mixed' ? '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>Silva, A.; Souza, B. (2020). Estudo da paisagem. Revista Sintética, v. 8, n. 2, p. 10–20. DOI: 10.1234/synthetic</w:t></w:r></w:p>' : '';
$parts = [
    '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/></Types>',
    '_rels/.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="main" Type="' . $rel . 'officeDocument" Target="word/document.xml"/></Relationships>',
    'word/document.xml' => '<w:document xmlns:w="' . $w . '"><w:body><w:p><w:r><w:t>Texto de laboratório.</w:t></w:r>' . $refs . '</w:p>' . $reference . '</w:body></w:document>',
    'word/_rels/document.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="fn" Type="' . $rel . 'footnotes" Target="footnotes.xml"/></Relationships>',
    'word/footnotes.xml' => '<w:footnotes xmlns:w="' . $w . '">' . $notes . '</w:footnotes>',
];
$z = new ZipArchive(); if ($z->open($source, ZipArchive::OVERWRITE) !== true) { exit(1); }
foreach ($parts as $name => $xml) { $z->addFromString($name, $xml); } $z->close();
