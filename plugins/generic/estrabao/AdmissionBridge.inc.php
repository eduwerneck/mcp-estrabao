<?php
/** Server-side adapter for the OJS 3.3.0.20 author confirmation form. */
class EstrabaoAdmissionBridge
{
    /** Uses only server-resolved submission/file records, never request paths/reports. */
    function inspect($form)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $submission = $form->submission;
        if (!$request->getUser() || !$context || !$submission || !$form->context
            || (int) $submission->getContextId() !== (int) $context->getId()
            || (int) $form->context->getId() !== (int) $context->getId()
            || (int) $form->submissionId !== (int) $submission->getId()
            || (int) $submission->getId() <= 0 || (int) $form->step !== 4) {
            return 'manual_review_required';
        }
        $bootstrap = Config::getVar('estrabao', 'engine_bootstrap');
        if (!is_string($bootstrap) || !is_file($bootstrap) || !is_readable($bootstrap)) {
            return 'manual_review_required';
        }
        require_once $bootstrap;
        if (!class_exists('Estrabao\\Document\\DocxReader') || !class_exists('Estrabao\\Rules\\References')) {
            return 'manual_review_required';
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
            if ($file !== null) { return 'manual_review_required'; }
            $file = $candidate;
        }
        if (!$file || (int) $file->getData('submissionId') !== (int) $submission->getId()
            || (int) $file->getData('fileStage') !== SUBMISSION_FILE_SUBMISSION) {
            return 'manual_review_required';
        }
        $relative = $file->getData('path');
        $root = realpath(Config::getVar('files', 'files_dir'));
        if (!$root || !is_string($relative) || $relative === '' || strpos($relative, "\0") !== false
            || preg_match('~(^[/\\\\]|^[a-z][a-z0-9+.-]*:|(^|[/\\\\])\.\.([/\\\\]|$))~i', $relative)) {
            return 'manual_review_required';
        }
        $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if (!$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path) || !is_readable($path)) {
            return 'manual_review_required';
        }
        $language = ['pt_BR' => 'pt', 'en_US' => 'en', 'es_ES' => 'es'][$submission->getLocale()] ?? null;
        if (!$language) { return 'manual_review_required'; }
        $before = hash_file('sha256', $path);
        $document = (new \Estrabao\Document\DocxReader())->read($path);
        $checks = (new \Estrabao\Rules\References($document, $language))->evaluate(
            new \Estrabao\Contracts\Submission(['schema_version' => '1.0'])
        );
        clearstatcache(true, $path);
        $after = hash_file('sha256', $path);
        if (!$before || !$after || !hash_equals($before, $after)
            || !hash_equals($before, $document['sha256'] ?? '')) { return 'manual_review_required'; }
        foreach ($checks as $check) {
            $result = $check->jsonSerialize();
            if ($result['rule_id'] === 'references.apa7') {
                $policy = $result['evidence']['admission_policy'] ?? [];
                return ($policy['decision'] ?? null) === 'not_accepted' ? 'not_accepted' : 'manual_review_required';
            }
        }
        return 'manual_review_required';
    }
}
