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
if ($mode === 'hash') { echo hash_file('sha256', $source); exit; }
if ($mode === 'restore') { if (is_file($backup)) { copy($backup, $source); unlink($backup); } exit; }
if (!in_array($mode, ['3', '4', '0', 'endnotes', 'mixed', 'compound', 'abbreviated', 'citation-many', 'citation-period', 'institution-abnt', 'institution-doi', 'institution-alias', 'secondary-apud', 'secondary-missing', 'secondary-correct', 'model-book', 'model-chapter', 'model-article', 'model-correct', 'matching-compound-style', 'matching-compound-missing', 'matching-compound-correct', 'matching-compound-partial', 'year-suffix', 'year-suffix-late', 'year-order', 'work-order', 'year-correct', 'year-authorship', 'year-suffix-ambiguous', 'year-partial-suffix', 'etal-collision', 'etal-prefix-collision', 'etal-single-rest', 'etal-expanded-correct', 'etal-full-correct', 'etal-initials-review', 'etal-duplicate-review', 'special-style', 'special-missing', 'special-apud', 'special-undated', 'special-press', 'special-divergent', 'special-suffix', 'longwork-title', 'longwork-description', 'longwork-report', 'longwork-source', 'longwork-repository', 'longwork-unpublished', 'longwork-correct-report', 'longwork-partial', 'web-publication', 'web-retrieval', 'web-url', 'web-empty-host', 'web-correct', 'web-year', 'web-nosite', 'web-unknownmonth', 'digital-title', 'digital-version', 'digital-description', 'digital-source', 'digital-dataset', 'digital-optional', 'digital-case', 'digital-partial', 'revision-translator', 'revision-edition', 'revision-original', 'revision-years', 'revision-separators', 'revision-correct', 'revision-republished', 'revision-original-citation', 'revision-slash', 'revision-partial', 'reforder-chronological', 'reforder-coauthors', 'reforder-accent', 'reforder-particle', 'reforder-shared-accent', 'reforder-correct', 'reforder-initials', 'reforder-tie', 'reforder-diacritic', 'reforder-original', 'reforder-special', 'quote-range', 'quote-marker', 'quote-single', 'quote-short', 'quote-long', 'quote-boundary', 'quote-missing', 'quote-paragraph', 'quote-time', 'quote-nested'], true)) { exit(1); }
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
if (in_array($mode, ['compound', 'abbreviated'], true)) {
    $authors = $mode === 'compound' ? 'van der Meer, A.; de Silva, B.' : 'García Márquez, A., … de Silva, B.';
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>' . htmlspecialchars($authors, ENT_XML1) . ' (2020). Título sintético. Editora Exemplo.</w:t></w:r></w:p>';
}
$main = '<w:p><w:r><w:t>Texto de laboratório.</w:t></w:r>' . $refs . '</w:p>';
if (in_array($mode, ['citation-many', 'citation-period'], true)) {
    $citation = $mode === 'citation-many' ? 'Texto (Silva, Souza, & Lima, 2020).' : 'Texto (Silva et al, 2020).';
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>Silva, A., Souza, B., &amp; Lima, C. (2020). Título sintético. Editora Exemplo.</w:t></w:r></w:p>';
}
if (in_array($mode, ['institution-abnt', 'institution-doi', 'institution-alias'], true)) {
    $main = '';
    foreach (['Introdução', 'Texto (Instituto de Estudos Sintéticos [IES], 2020).', 'Texto (IES, 2020).', 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $text = $mode === 'institution-abnt' ? 'Instituto de Estudos Sintéticos. Título sintético. São Paulo: Editora Exemplo, 2020.' : 'Instituto de Estudos Sintéticos. (2020). Título sintético. Editora Exemplo.';
    if ($mode === 'institution-doi') { $text .= ' DOI: 10.1234/synthetic'; }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
}
if (in_array($mode, ['secondary-apud', 'secondary-missing', 'secondary-correct'], true)) {
    $citation = $mode === 'secondary-apud' ? 'Texto (Silva, 1980, apud Souza, 2020).' : 'Texto (Silva, 1980, como citado em Souza, 2020).';
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $sourceName = $mode === 'secondary-missing' ? 'Lima' : 'Souza';
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>' . $sourceName . ', A. (2020). Título sintético. Editora Exemplo.</w:t></w:r></w:p>';
}
if (in_array($mode, ['model-book', 'model-chapter', 'model-article', 'model-correct'], true)) {
    $main = '';
    foreach (['Introdução', 'Texto (Silva, 2020).', 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    if ($mode === 'model-book') {
        $pieces = [['Silva, A. (2020). ', false], ['Livro sintético', true], [' (', false], ['2nd ed.', true], ['). Editora Exemplo.', false]];
    } elseif ($mode === 'model-article') {
        $pieces = [['Silva, A. (2020). Artigo sintético. ', false], ['Revista Sintética', true], [', ', false], ['8', true], ['(2), 20–10.', false]];
    } else {
        $editors = $mode === 'model-chapter' ? 'B. Editor; C. Outro; D. Terceiro' : 'B. Editor, C. Outro, & D. Terceiro';
        $pieces = [['Silva, A. (2020). Capítulo sintético. In ' . $editors . ' (Eds.), ', false], ['Livro sintético', true], [' (2nd ed., pp. 10–20). Editora Exemplo.', false]];
    }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p>';
    foreach ($pieces as $piece) { $reference .= '<w:r>' . ($piece[1] ? '<w:rPr><w:i/></w:rPr>' : '') . '<w:t xml:space="preserve">' . htmlspecialchars($piece[0], ENT_XML1) . '</w:t></w:r>'; }
    $reference .= '</w:p>';
}
if (in_array($mode, ['matching-compound-style', 'matching-compound-missing', 'matching-compound-correct', 'matching-compound-partial'], true)) {
    $citation = ['matching-compound-style'=>'Texto (da Silva e García Márquez, 2020).', 'matching-compound-missing'=>'Texto (Ausente, 2020).', 'matching-compound-correct'=>'Texto (da Silva & García Márquez, 2020).', 'matching-compound-partial'=>'Texto (Silva & García Márquez, 2020).'][$mode];
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>da Silva, A., &amp; García Márquez, B. (2020). Título sintético. Editora Exemplo.</w:t></w:r></w:p>';
}
if (in_array($mode, ['year-suffix', 'year-suffix-late', 'year-order', 'work-order', 'year-correct', 'year-authorship', 'year-suffix-ambiguous', 'year-partial-suffix'], true)) {
    $citation = ['year-suffix'=>'Texto (Silva, 2020).', 'year-suffix-late'=>'Texto (Silva, 2020c).', 'year-order'=>'Texto (Silva, 2020, 2019).', 'work-order'=>'Texto (Souza, 2019; Silva, 2020).', 'year-correct'=>'Silva (2019, 2020a, 2020b).', 'year-authorship'=>'Texto (Silva, 2020, 2019).', 'year-suffix-ambiguous'=>'Texto (Silva, 2020).', 'year-partial-suffix'=>'Texto (Silva, 2020).'][$mode];
    $main = '';
    $texts = ['Introdução'];
    if ($mode === 'year-suffix-late') { $texts = array_merge($texts, array_fill(0, 40, 'Texto (Silva, 2020a, 2020a).')); }
    $texts = array_merge($texts, [$citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.']);
    foreach ($texts as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $entries = [['Silva', 'A', '2020a'], ['Silva', 'A', '2020b']];
    if ($mode === 'year-order' || $mode === 'year-authorship') { $entries = [['Silva', $mode === 'year-authorship' ? 'B' : 'A', '2019'], ['Silva', 'A', '2020']]; }
    if ($mode === 'work-order') { $entries = [['Silva', 'A', '2020'], ['Souza', 'B', '2019']]; }
    if ($mode === 'year-correct') { array_unshift($entries, ['Silva', 'A', '2019']); }
    if ($mode === 'year-suffix-ambiguous') { $entries[1][1] = 'B'; }
    if ($mode === 'year-partial-suffix') { $entries[0][0] = 'da Silva'; $entries[1][0] = 'da Silva'; }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p>';
    foreach ($entries as $e) { $reference .= '<w:p><w:r><w:t>' . $e[0] . ', ' . $e[1] . '. (' . $e[2] . '). Título ' . $e[2] . '. Editora Exemplo.</w:t></w:r></w:p>'; }
}
if (strpos($mode, 'etal-') === 0) {
    $citation = ['etal-collision'=>'Texto (Silva et al., 2020).', 'etal-prefix-collision'=>'Texto (Silva, Souza, et al., 2020).', 'etal-single-rest'=>'Texto (Silva, Souza, et al., 2020).', 'etal-expanded-correct'=>'Texto (Silva, Souza, et al., 2020).', 'etal-full-correct'=>'Texto (Silva, Souza, & Lima, 2020).', 'etal-initials-review'=>'Texto (Silva et al., 2020).', 'etal-duplicate-review'=>'Texto (Silva et al., 2020).'][$mode];
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $lists = [['Silva','Souza','Lima','Ramos','Nunes'], ['Silva','Costa','Alves','Paiva','Rocha']];
    if ($mode === 'etal-prefix-collision') { $lists[1] = ['Silva','Souza','Costa','Paiva','Rocha']; }
    if ($mode === 'etal-single-rest' || $mode === 'etal-full-correct') { $lists = [['Silva','Souza','Lima'], ['Silva','Costa','Ramos']]; }
    if ($mode === 'etal-duplicate-review') { $lists[1] = $lists[0]; }
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p>';
    foreach ($lists as $row => $names) {
        $authors = []; foreach ($names as $i => $name) { $authors[] = $name . ', ' . ($i === 0 && $row === 1 && $mode === 'etal-initials-review' ? 'B' : chr(65 + $i)) . '.'; }
        $last = array_pop($authors); $text = implode(', ', $authors) . ', & ' . $last . ' (2020). Obra sintética. Editora Exemplo.';
        $reference .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
}
if (strpos($mode, 'special-') === 0) {
    $citation = ['special-style'=>'Texto (Silva e Souza, s.d.).', 'special-missing'=>'Texto (Ausente, no prelo).', 'special-apud'=>'Texto (Original, 1990, apud Silva, no prelo).', 'special-undated'=>'Silva (s.d.).', 'special-press'=>'Texto (Silva, no prelo).', 'special-divergent'=>'Texto (Silva, s.d.).', 'special-suffix'=>'Texto (Silva, s.d.-b).'][$mode];
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $authors = $mode === 'special-style' ? 'Silva, A., & Souza, B.' : 'Silva, A.';
    $date = in_array($mode, ['special-missing','special-apud','special-press'], true) ? 'in press' : ($mode === 'special-divergent' ? '2020' : ($mode === 'special-suffix' ? 'n.d.-b' : 'n.d.'));
    $text = $authors . ' (' . $date . '). Obra sintética. Editora Exemplo.';
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
}
if (strpos($mode, 'longwork-') === 0) {
    $main = '';
    foreach (['Introdução', 'Texto (Silva, 2020).', 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $description = '[Tese de doutorado, Universidade Exemplo]'; $longWorkSource = 'Repositório Exemplo';
    if ($mode === 'longwork-report' || $mode === 'longwork-correct-report') { $description = '(Report No. 2024-1)'; $longWorkSource = 'Editora Exemplo'; }
    if ($mode === 'longwork-unpublished') { $description = '[Dissertação de mestrado não publicada]'; $longWorkSource = 'Universidade Exemplo'; }
    if ($mode === 'longwork-partial') { $description = '[Tese de doutorado]'; $longWorkSource = 'Universidade Exemplo'; }
    $pieces = [['Silva, A. (2020). ', false], ['Obra sintética', $mode !== 'longwork-title' && $mode !== 'longwork-partial'], [' ', false], [$description, $mode === 'longwork-description' || $mode === 'longwork-report'], ['. ', false], [$longWorkSource, $mode === 'longwork-source'], ['.', false]];
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p>';
    foreach ($pieces as $piece) { $reference .= '<w:r>' . ($piece[1] ? '<w:rPr><w:i/></w:rPr>' : '') . '<w:t xml:space="preserve">' . htmlspecialchars($piece[0], ENT_XML1) . '</w:t></w:r>'; }
    $reference .= '</w:p>';
}
if (strpos($mode, 'web-') === 0) {
    $main = '';
    foreach (['Introdução', 'Texto (Silva, 2020).', 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $webDate = $mode === 'web-publication' ? '2020, February 30' : ($mode === 'web-unknownmonth' ? '2020, Foo 2' : (in_array($mode, ['web-year','web-nosite'], true) ? '2020' : '2020, February 29'));
    $webURL = $mode === 'web-url' ? 'https://:80/item' : ($mode === 'web-empty-host' ? 'https://' : 'https://portal.synthetic.invalid/item');
    $webSite = $mode === 'web-nosite' ? '' : ' Portal Sintético.';
    $retrieval = $mode === 'web-retrieval' ? ' Recuperado em 31 de abril de 2020, de' : ($mode === 'web-correct' ? ' Recuperado em 29 de fevereiro de 2020, de' : '');
    $pieces = [['Silva, A. (' . $webDate . '). ', false], ['Página sintética', true], ['.' . $webSite . $retrieval . ' ' . $webURL, false]];
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p>';
    foreach ($pieces as $piece) { $reference .= '<w:r>' . ($piece[1] ? '<w:rPr><w:i/></w:rPr>' : '') . '<w:t xml:space="preserve">' . htmlspecialchars($piece[0], ENT_XML1) . '</w:t></w:r>'; }
    $reference .= '</w:p>';
}
if (strpos($mode, 'digital-') === 0) {
    $main = '';
    foreach (['Introdução', 'Texto (Silva, 2020).', 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $digitalTitle = $mode === 'digital-case' ? 'QGIS' : 'Dados sintéticos';
    $digitalLabel = in_array($mode, ['digital-title','digital-description','digital-dataset'], true) ? 'Data set' : 'Computer software';
    $digitalVersion = $mode === 'digital-optional' ? '' : ($mode === 'digital-partial' ? ' (Version 1.2 stable)' : ' (Version 1.2)');
    $digitalSource = $mode === 'digital-optional' ? '' : '. Repositório Sintético';
    $pieces = [['Silva, A. (2020). ', false], [$digitalTitle, !in_array($mode, ['digital-title','digital-partial', 'revision-translator', 'revision-edition', 'revision-original', 'revision-years', 'revision-separators', 'revision-correct', 'revision-republished', 'revision-original-citation', 'revision-slash', 'revision-partial', 'reforder-chronological', 'reforder-coauthors', 'reforder-accent', 'reforder-particle', 'reforder-shared-accent', 'reforder-correct', 'reforder-initials', 'reforder-tie', 'reforder-diacritic', 'reforder-original', 'reforder-special', 'quote-range', 'quote-marker', 'quote-single', 'quote-short', 'quote-long', 'quote-boundary', 'quote-missing', 'quote-paragraph', 'quote-time', 'quote-nested'], true)], [$digitalVersion, $mode === 'digital-version'], [' ', false], ['[' . $digitalLabel . ']', $mode === 'digital-description'], [$digitalSource, $mode === 'digital-source'], ['.', false]];
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p>';
    foreach ($pieces as $piece) { $reference .= '<w:r>' . ($piece[1] ? '<w:rPr><w:i/></w:rPr>' : '') . '<w:t xml:space="preserve">' . htmlspecialchars($piece[0], ENT_XML1) . '</w:t></w:r>'; }
    $reference .= '</w:p>';
}
if (strpos($mode, 'revision-') === 0) {
    $citation = $mode === 'revision-original-citation' || $mode === 'revision-partial' ? 'Texto (Silva, 1900).' : ($mode === 'revision-slash' ? 'Texto (Silva, 1900/2020).' : 'Texto (Silva, 2020).');
    $main = '';
    foreach (['Introdução', $citation, 'Metodologia', 'Texto sintético.', 'Resultados e Discussão', 'Texto sintético.', 'Considerações Finais', 'Texto sintético.'] as $text) {
        $main .= '<w:p><w:r><w:t>' . htmlspecialchars($text, ENT_XML1) . '</w:t></w:r></w:p>';
    }
    $revisionNames = $mode === 'revision-separators' ? 'A. Tradutor; B. Outro' : 'A. Tradutor';
    $revisionYear = $mode === 'revision-years' ? '2030' : ($mode === 'revision-partial' ? 'desconhecido' : '1900');
    $pieces = [['Silva, A. (2020). ', false], ['Obra traduzida', true]];
    if ($mode === 'revision-republished') { $pieces[] = [' (2nd ed.). Editora Exemplo. ', false]; }
    else {
        $pieces[] = [' (', false]; $pieces[] = [$revisionNames . ', Trans.', $mode === 'revision-translator'];
        $pieces[] = ['; ', false]; $pieces[] = ['2nd ed.', $mode === 'revision-edition']; $pieces[] = ['). Editora Exemplo. ', false];
    }
    $pieces[] = ['(Original work published ' . $revisionYear . ')', $mode === 'revision-original'];
    $reference = '<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p>';
    foreach ($pieces as $piece) { $reference .= '<w:r>' . ($piece[1] ? '<w:rPr><w:i/></w:rPr>' : '') . '<w:t xml:space="preserve">' . htmlspecialchars($piece[0], ENT_XML1) . '</w:t></w:r>'; }
    $reference .= '</w:p>';
}
if (strpos($mode, 'reforder-') === 0) {
    $entries = [['Silva, A.','2021'],['Silva, A.','2019']];$citation='Texto (Silva, 2021).';
    if($mode==='reforder-coauthors'){$entries=[['Silva, A., & Souza, B.','2021'],['Silva, A., & Souza, B.','2019']];$citation='Texto (Silva & Souza, 2021).';}
    if($mode==='reforder-accent'){$entries=[['Souza, A.','2020'],['García, B.','2020']];$citation='Texto (Souza, 2020).';}
    if($mode==='reforder-particle'){$entries=[['da Silva, A.','2020'],['da Costa, B.','2020']];$citation='Texto (da Silva, 2020).';}
    if($mode==='reforder-shared-accent'){$entries=[['Ávila, A.','2020'],['Álvares, B.','2020']];$citation='Texto (Ávila, 2020).';}
    if($mode==='reforder-correct'){$entries=[['Silva, A.','2019'],['Silva, A.','2021']];}
    if($mode==='reforder-initials'){$entries[1][0]='Silva, B.';}
    if($mode==='reforder-tie'){$entries=[['Silva, A.','2020b'],['Silva, A.','2020a']];$citation='Texto (Silva, 2020b).';}
    if($mode==='reforder-diacritic'){$entries=[['Alves, A.','2020'],['Ávila, B.','2020']];$citation='Texto (Alves, 2020).';}
    if($mode==='reforder-special'){$entries=[['Silva, A.','n.d.'],['Silva, A.','2019']];$citation='Texto (Silva, n.d.).';}
    $main='';foreach(['Introdução',$citation,'Metodologia','Texto sintético.','Resultados e Discussão','Texto sintético.','Considerações Finais','Texto sintético.']as$text){$main.='<w:p><w:r><w:t>'.htmlspecialchars($text,ENT_XML1).'</w:t></w:r></w:p>';}
    $reference='<w:p><w:r><w:t>Referências</w:t></w:r></w:p>';
    foreach($entries as$i=>$entry){
        $tail='. Editora Exemplo.'.($mode==='reforder-original'&&$i===0?' (Original work published 1900)':'');
        $reference.='<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($entry[0].' ('.$entry[1].'). ',ENT_XML1).'</w:t></w:r><w:r><w:rPr><w:i/></w:rPr><w:t>Obra sintética</w:t></w:r><w:r><w:t>'.htmlspecialchars($tail,ENT_XML1).'</w:t></w:r></w:p>';
    }
}
if (strpos($mode, 'quote-') === 0) {
    $words=$mode==='quote-short'?39:($mode==='quote-long'?40:12);
    $quoted=implode(' ',array_fill(0,$words,'trecho'));
    if($mode==='quote-boundary'){$quoted=implode(' ',array_fill(0,38,'trecho')).' duas-palavras';}
    $locator=['quote-range'=>'pp. 30–10','quote-marker'=>'p. 10–12','quote-single'=>'pp. 10','quote-short'=>'p. 10','quote-long'=>'pp. 10–12','quote-boundary'=>'p. 10','quote-missing'=>'','quote-paragraph'=>'para. 2','quote-time'=>'01:30','quote-nested'=>'p. 10'][$mode];
    $quoted=$mode==='quote-nested'?'uma transcrição com (Ausente, 1900, pp. 30–10) e continuação':$quoted;
    $citation='“'.$quoted.'” (Silva, 2020'.($locator!==''?', '.$locator:'').').';
    $main='';foreach(['Introdução',$citation,'Metodologia','Texto sintético.','Resultados e Discussão','Texto sintético.','Considerações Finais','Texto sintético.']as$text){$main.='<w:p><w:r><w:t>'.htmlspecialchars($text,ENT_XML1).'</w:t></w:r></w:p>';}
    $reference='<w:p><w:r><w:t>Referências</w:t></w:r></w:p><w:p><w:r><w:t>Silva, A. (2020). Obra sintética. Editora Exemplo.</w:t></w:r></w:p>';
}
$parts = [
    '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/></Types>',
    '_rels/.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="main" Type="' . $rel . 'officeDocument" Target="word/document.xml"/></Relationships>',
    'word/document.xml' => '<w:document xmlns:w="' . $w . '"><w:body>' . $main . $reference . '</w:body></w:document>',
    'word/_rels/document.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="fn" Type="' . $rel . 'footnotes" Target="footnotes.xml"/></Relationships>',
    'word/footnotes.xml' => '<w:footnotes xmlns:w="' . $w . '">' . $notes . '</w:footnotes>',
];
$z = new ZipArchive(); if ($z->open($source, ZipArchive::OVERWRITE) !== true) { exit(1); }
foreach ($parts as $name => $xml) { $z->addFromString($name, $xml); } $z->close();
