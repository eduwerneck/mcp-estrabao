<?php
require_once __DIR__ . '/AdmissionBridge.inc.php';
require_once __DIR__ . '/AdmissionReviewStore.inc.php';

class EstrabaoAdmissionReviewService
{
    function requireEditor($request)
    {
        $context = $request->getContext(); $user = $request->getUser();
        if (Config::getVar('estrabao', 'admission_gate') !== true || !$context || !$user || !$user->getId()
            || !DAORegistry::getDAO('RoleDAO')->userHasRole($context->getId(), $user->getId(), ROLE_ID_MANAGER)) {
            throw new RuntimeException('review_forbidden');
        }
    }

    function form($request, $submissionId)
    {
        $this->requireEditor($request);
        if (!is_scalar($submissionId) || !preg_match('/^[1-9][0-9]{0,8}$/D', (string) $submissionId)) {
            throw new RuntimeException('review_unavailable');
        }
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById((int) $submissionId);
        if (!$submission || (int) $submission->getContextId() !== (int) $request->getContext()->getId()
            || (int) $submission->getSubmissionProgress() !== 4) { throw new RuntimeException('review_unavailable'); }
        $form = new stdClass(); $form->context = $request->getContext(); $form->submission = $submission;
        $form->submissionId = (int) $submissionId; $form->step = 4;
        return $form;
    }

    function view($request, $submissionId)
    {
        $form = $this->form($request, $submissionId);
        $analysis = (new EstrabaoAdmissionBridge())->analyze($form);
        $ledger = (new EstrabaoAdmissionReviewStore())->transaction($form->context->getId(), $form->submissionId, function (&$ledger) { return $ledger; });
        $events = $ledger['events'];
        foreach ($events as &$event) {
            $event['display_time'] = (new DateTimeImmutable($event['recorded_at']))
                ->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s T');
        }
        unset($event);
        return ['submission_id' => $form->submissionId, 'analysis' => $analysis,
            'revision' => count($events), 'events' => $events,
            'revocation_fingerprint' => $events ? $events[count($events) - 1]['fingerprint'] : null,
            'release_active' => $this->allows($form, $analysis)];
    }

    function draft($request, $submissionId)
    {
        $this->requireEditor($request);
        if (!$request->isPost() || !$request->getUserVar('csrfToken') || !$request->checkCSRF()) { throw new RuntimeException('review_forbidden'); }
        $type = $request->getUserVar('manuscriptType');
        $expected = $request->getUserVar('fingerprint');
        if (!in_array($type, ['article', 'research_communication'], true) || !is_string($expected)
            || !preg_match('/^[a-f0-9]{64}$/D', $expected)) { throw new RuntimeException('desk_source_unavailable'); }
        $form = $this->form($request, $submissionId);
        $bridge = new EstrabaoAdmissionBridge(); $analysis = $bridge->analyze($form, true);
        if (!$analysis['fingerprint'] || !hash_equals($expected, $analysis['fingerprint']) || !isset($analysis['document'])
            || !class_exists('Estrabao\\Reporting\\DeskResponse')) { throw new RuntimeException('desk_source_unavailable'); }
        require_once __DIR__ . '/SubmissionSnapshot.inc.php';
        $source = (new EstrabaoSubmissionSnapshot())->read($form);
        $snapshot = $source['snapshot'];
        $report = \Estrabao\Reporting\Report::generate($snapshot,
            \Estrabao\Rules\DefaultRules::all($analysis['document'], $type, $analysis['binding']['language']));
        $draft = \Estrabao\Reporting\DeskResponse::generate($report);
        // Recheck both document and current publication before returning any draft.
        $fresh = $this->form($request, $submissionId);
        $after = $bridge->analyze($fresh);
        $current = (new EstrabaoSubmissionSnapshot())->read($fresh);
        if (!$after['fingerprint'] || !hash_equals($expected, $after['fingerprint'])
            || !hash_equals($snapshot->digest(), $current['snapshot']->digest())
            || $source['publication_id'] !== $current['publication_id']
            || $source['publication_version'] !== $current['publication_version']) { throw new RuntimeException('desk_source_changed'); }
        $groups = [];
        foreach ($draft['pending_review'] as $pending) {
            $id = $pending['rule_id'] ?? '';
            $key = strpos($id, 'manuscript.credit.') === 0 ? 'deskPendingCredit' : (strpos($id, 'manuscript.footnotes.') === 0 ? 'deskPendingFootnotes' : ($id === 'manuscript.funding' ? 'deskPendingFunding'
                : (strpos($id, 'metadata.') === 0 ? 'deskPendingMetadata'
                : (strpos($id, 'abstract.') === 0 ? 'deskPendingAbstracts'
                : (strpos($id, 'author.') === 0 ? 'deskPendingAuthors'
                : (strpos($id, 'references.') === 0 ? 'deskPendingReferences' : 'deskPendingManuscript'))))));
            $groups[$key] = ($groups[$key] ?? 0) + 1;
        }
        $footnoteReview = null;
        foreach ($report['checks'] as $check) {
            if ($check['rule_id'] === 'manuscript.footnotes.lines' && $check['rule_version'] === '2.0.0'
                && $check['status'] === 'indeterminado') { $footnoteReview = $check['evidence']['review_plan']; }
        }
        return ['draft' => $draft, 'pending_groups' => $groups, 'footnote_review'=>$footnoteReview, 'manuscript_type' => $type,
            'fingerprint' => hash('sha256', json_encode([$expected, $snapshot->digest(), $source['publication_id'],
                $source['publication_version'], $type, $report['engine_version'], $draft['template_version']]))];
    }

    function download($request, $submissionId)
    {
        $form = $this->form($request, $submissionId);
        $analysis = (new EstrabaoAdmissionBridge())->analyze($form);
        $fingerprint = $request->getUserVar('fingerprint');
        if (!is_string($fingerprint) || !$analysis['fingerprint']
            || !hash_equals($analysis['fingerprint'], $fingerprint)
            || ($analysis['binding']['file_id'] ?? 0) <= 0) { throw new RuntimeException('review_stale_or_blocked'); }
        // The file id is server-resolved and belongs to this authorized submission.
        Services::get('file')->download($analysis['binding']['file_id'], 'manuscrito.docx', false);
    }

    function save($request, $submissionId)
    {
        $this->requireEditor($request);
        if (!$request->isPost() || !$request->getUserVar('csrfToken') || !$request->checkCSRF()) { throw new RuntimeException('review_forbidden'); }
        $action = $request->getUserVar('reviewAction');
        $fingerprint = $request->getUserVar('fingerprint'); $revision = $request->getUserVar('revision');
        if (!in_array($action, ['approve', 'revoke'], true) || !is_string($fingerprint)
            || !preg_match('/^[a-f0-9]{64}$/D', $fingerprint) || !is_scalar($revision)
            || !preg_match('/^(0|[1-9][0-9]{0,2})$/D', (string) $revision)
            || ($action === 'approve' && $request->getUserVar('apaReviewed') !== '1')) { throw new RuntimeException('review_unavailable'); }
        $form = $this->form($request, $submissionId);
        return (new EstrabaoAdmissionReviewStore())->transaction($form->context->getId(), $form->submissionId,
            function (&$ledger) use ($request, $form, $action, $fingerprint, $revision) {
                $this->requireEditor($request);
                if ((int) $revision !== count($ledger['events']) || count($ledger['events']) >= 100) { throw new RuntimeException('review_stale_or_blocked'); }
                if ($action === 'approve') {
                    $analysis = (new EstrabaoAdmissionBridge())->analyze($form);
                    if (!$analysis['fingerprint'] || !hash_equals($fingerprint, $analysis['fingerprint'])
                        || count($ledger['events']) >= 99 || !($analysis['reviewable'] ?? false)) { throw new RuntimeException('review_stale_or_blocked'); }
                } else {
                    // Revocation must remain possible even after the manuscript becomes unreadable.
                    $last = end($ledger['events']);
                    if (!$last || !hash_equals($fingerprint, $last['fingerprint'])) { throw new RuntimeException('review_stale_or_blocked'); }
                }
                $ledger['events'][] = ['sequence' => count($ledger['events']) + 1, 'action' => $action,
                    'reviewer_id' => (int) $request->getUser()->getId(), 'recorded_at' => gmdate('Y-m-d\TH:i:s\Z'),
                    'fingerprint' => $fingerprint];
                return true;
            });
    }

    /** A human release never overrides a proven APA failure or an unreadable source. */
    function allows($form, array $analysis)
    {
        if (!($analysis['reviewable'] ?? false) || !$analysis['fingerprint']
            || ($analysis['binding']['context_id'] ?? null) !== (int) $form->context->getId()
            || ($analysis['binding']['submission_id'] ?? null) !== (int) $form->submissionId) { return false; }
        return (new EstrabaoAdmissionReviewStore())->transaction($form->context->getId(), $form->submissionId,
            function (&$ledger) use ($form, $analysis) {
                if (count($ledger['events']) >= 100) { return false; }
                $last = end($ledger['events']);
                return $last && $last['action'] === 'approve' && hash_equals($last['fingerprint'], $analysis['fingerprint'])
                    && DAORegistry::getDAO('RoleDAO')->userHasRole($form->context->getId(), $last['reviewer_id'], ROLE_ID_MANAGER);
            });
    }
}
