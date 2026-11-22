<?php
/** Policy for trusted, server-read metadata and engine evidence. No OJS writes. */
class EstrabaoAutomaticTriagePolicy
{
    const VERSION = '1.0.0';

    function evaluate($form)
    {
        $bootstrap = Config::getVar('estrabao', 'engine_bootstrap');
        if (!is_string($bootstrap) || !is_readable($bootstrap)) { throw new RuntimeException('triage_engine_unavailable'); }
        require_once $bootstrap;
        require_once __DIR__ . '/AdmissionBridge.inc.php';
        require_once __DIR__ . '/SubmissionSnapshot.inc.php';
        $analysis = (new EstrabaoAdmissionBridge())->analyze($form, true);
        $source = (new EstrabaoSubmissionSnapshot())->read($form);
        $report = \Estrabao\Reporting\Report::generate($source['snapshot'],
            \Estrabao\Rules\DefaultRules::all($analysis['document'] ?? null, null, $analysis['binding']['language'] ?? null));
        $draft = \Estrabao\Reporting\DeskResponse::generate($report);
        $issues = [];
        // Mapped corrections carry evidence; unresolved checks never become accusations.
        foreach ($draft['corrections'] as $item) {
            $issues[] = $item['area'] . ': ' . $item['requirement'] . ' ' . $item['finding'] . ' ' . $item['correction'];
        }
        $publication = $form->submission->getCurrentPublication();
        $agencies = DAORegistry::getDAO('SubmissionAgencyDAO')->getAgencies($publication->getId());
        $issues = array_merge($issues, self::metadataIssues($publication->getData('citationsRaw'), $agencies));
        $issues = array_values(array_unique($issues));
        // The engine does not certify complete APA compliance. No automatic publication acceptance.
        return ['policy'=>self::VERSION, 'outcome'=>$issues ? 'declined' : 'editorial_review',
            'issues'=>$issues, 'fingerprint'=>$analysis['fingerprint'] ?? null,
            'metadata_digest'=>$source['snapshot']->digest(), 'publication_id'=>$source['publication_id'],
            'publication_version'=>$source['publication_version'],
            'subject'=>$issues ? 'Submissão rejeitada na triagem — adequação às diretrizes editoriais' : 'Submissão recebida — conferência editorial pendente',
            'body'=>self::letter($issues)];
    }

    static function metadataIssues($references, array $agencies)
    {
        $issues = [];
        if ($references === null || (is_string($references) && !preg_match('/[^\s\p{Z}\p{Cf}]/u', html_entity_decode(strip_tags($references), ENT_QUOTES | ENT_HTML5, 'UTF-8')))) {
            $issues[] = 'Referências nos metadados: preencher o campo Referências do OJS com a bibliografia completa em APA 7, uma referência por parágrafo.';
        } elseif (is_string($references) && strlen($references) <= 262144) {
            $text = \Estrabao\Text\PlainText::normalize($references);
            if ($text === null) { throw new RuntimeException('triage_references_unreadable'); }
            $paragraphs = [['index'=>0,'text'=>'Referências']];
            foreach (preg_split('/\R/u', $text) as $line) {
                $paragraphs[] = ['index'=>count($paragraphs),'text'=>trim($line), 'outline'=>['known'=>true,'level'=>9], 'format'=>['bold'=>false]];
            }
            $audit = \Estrabao\Document\ReferenceStyleAudit::analyze(['paragraphs'=>$paragraphs], 'pt');
            if ($audit['recognized_formats_status'] === \Estrabao\Contracts\CheckResult::FAIL) {
                $issues[] = 'Referências nos metadados: foram reconhecidas incompatibilidades de formato ou ordenação com APA 7 no campo Referências. Reestruturar a lista em APA 7; maiúsculas isoladas não determinam ABNT.';
            }
        }
        // Agencies are a native controlled vocabulary, not a publication setting.
        $declared = false;
        foreach ($agencies as $values) {
            foreach ($values as $value) { if (is_string($value) && trim($value) !== '') { $declared = true; } }
        }
        if (!$declared) {
            $issues[] = 'Financiamento nos metadados: informar as agências e os recursos recebidos. Quando não houver apoio, preencher Agências de fomento com “Sem financiamento”.';
        }
        return $issues;
    }

    static function letter(array $issues)
    {
        if (!$issues) { return "Prezados(as) autores(as),\n\nSua submissão foi recebida e aguarda conferência editorial. As verificações automáticas não permitem uma decisão conclusiva. Aguarde o contato da equipe editorial; não é necessário criar outra submissão neste momento.\n\nEquipe editorial da Revista Estrabão"; }
        $body = "Prezados(as) autores(as),\n\nA submissão foi recebida e rejeitada automaticamente na triagem de conformidade, pelos seguintes pontos identificados:\n\n";
        foreach ($issues as $i=>$issue) { $body .= ($i+1) . '. ' . $issue . "\n\n"; }
        return $body . "Corrija os itens indicados e apresente uma nova submissão com todos os metadados e arquivos atualizados. A nova submissão passará por nova triagem. Esta decisão trata da conformidade com as diretrizes e não constitui avaliação do mérito científico.\n\nAtenciosamente,\nEquipe editorial da Revista Estrabão";
    }
}
