<?php
/** Server-side adapter for the OJS 3.3.0.20 author confirmation form. */
class EstrabaoAdmissionBridge
{
    /** Uses only server-resolved submission/file records, never request paths/reports. */
    function inspect($form)
    {
        return $this->analyze($form)['decision'];
    }

    function analyze($form, $includeDocument = false)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $submission = $form->submission;
        if (!$request->getUser() || !$context || !$submission || !$form->context
            || (int) $submission->getContextId() !== (int) $context->getId()
            || (int) $form->context->getId() !== (int) $context->getId()
            || (int) $form->submissionId !== (int) $submission->getId()
            || (int) $submission->getId() <= 0 || (int) $form->step !== 4) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        $bootstrap = Config::getVar('estrabao', 'engine_bootstrap');
        if (!is_string($bootstrap) || !is_file($bootstrap) || !is_readable($bootstrap)) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        require_once $bootstrap;
        if (!class_exists('Estrabao\\Document\\DocxReader') || !class_exists('Estrabao\\Rules\\References')) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        if (!defined('SUBMISSION_FILE_SUBMISSION')) { import('lib.pkp.classes.submission.SubmissionFile'); }
        // Ambiguous file sets are held, rather than choosing an arbitrary attachment.
        $files = Services::get('submissionFile')->getMany([
            'submissionIds' => [(int) $submission->getId()],
            'fileStages' => [SUBMISSION_FILE_SUBMISSION],
            'includeDependentFiles' => false,
        ]);
        $file = null;
        foreach ($files as $candidate) {
            if ($file !== null) { return ['decision' => 'manual_review_required', 'fingerprint' => null]; }
            $file = $candidate;
        }
        if (!$file || (int) $file->getData('submissionId') !== (int) $submission->getId()
            || (int) $file->getData('fileStage') !== SUBMISSION_FILE_SUBMISSION) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        $relative = $file->getData('path');
        $root = realpath(Config::getVar('files', 'files_dir'));
        if (!$root || !is_string($relative) || $relative === '' || strpos($relative, "\0") !== false
            || preg_match('~(^[/\\\\]|^[a-z][a-z0-9+.-]*:|(^|[/\\\\])\.\.([/\\\\]|$))~i', $relative)) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if (!$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || !is_readable($path)) {
            return ['decision' => 'manual_review_required', 'fingerprint' => null];
        }
        $language = ['pt_BR' => 'pt', 'en_US' => 'en', 'es_ES' => 'es'][$submission->getLocale()] ?? null;
        if (!$language) { return ['decision' => 'manual_review_required', 'fingerprint' => null]; }
        $before = hash_file('sha256', $path);
        $document = (new \Estrabao\Document\DocxReader())->read($path);
        $checks = (new \Estrabao\Rules\References($document, $language))->evaluate(
            new \Estrabao\Contracts\Submission(['schema_version' => '1.0'])
        );
        clearstatcache(true, $path);
        $after = hash_file('sha256', $path);
        if (!$before || !$after || !hash_equals($before, $after)
            || !hash_equals($before, $document['sha256'] ?? '')) { return ['decision' => 'manual_review_required', 'fingerprint' => null]; }
        foreach ($checks as $check) {
            $result = $check->jsonSerialize();
            if ($result['rule_id'] === 'references.apa7') {
                $policy = $result['evidence']['admission_policy'] ?? [];
                $decision = ($policy['decision'] ?? null) === 'not_accepted' ? 'not_accepted' : 'manual_review_required';
                $binding = [
                    'context_id' => (int) $context->getId(), 'submission_id' => (int) $submission->getId(),
                    'submission_file_id' => (int) $file->getId(), 'file_id' => (int) $file->getData('fileId'),
                    'document_sha256' => $before, 'language' => $language,
                    'rule_version' => $result['rule_version'], 'engine_version' => \Estrabao\Reporting\Report::ENGINE_VERSION,
                    'policy_version' => $policy['policy_version'] ?? null,
                ];
                $reviewable = $decision !== 'not_accepted' && ($result['evidence']['source_available'] ?? false)
                    && $binding['submission_file_id'] > 0 && $binding['file_id'] > 0
                    && $binding['rule_version'] === '5.0.0' && $binding['policy_version'] === 'apa-required-2026-11-12';
                $corrections = [];
                foreach ($result['evidence']['style_audit']['samples'] ?? [] as $sample) {
                    foreach ($sample['checks'] ?? [] as $entryCheck) {
                        if (($entryCheck['status'] ?? null) === 'não conforme') { $corrections[] = $entryCheck['message']; }
                    }
                }
                foreach ($result['evidence']['analysis']['citation_style_samples'] ?? [] as $sample) {
                    if (($sample['status'] ?? null) === 'não conforme') { $corrections = array_merge($corrections, $sample['guidance'] ?? []); }
                }
                $response = ['decision' => $decision, 'corrections' => array_slice(array_values(array_unique($corrections)), 0, 5),
                    'fingerprint' => hash('sha256', json_encode($binding)),
                    'binding' => $binding, 'reviewable' => $reviewable];
                if ($includeDocument) { $response['document'] = $document; }
                return $response;
            }
        }
        return ['decision' => 'manual_review_required', 'fingerprint' => null];
    }
}
