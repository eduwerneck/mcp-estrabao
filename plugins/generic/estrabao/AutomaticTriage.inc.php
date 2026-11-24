<?php
/** OJS 3.3.0.20 opt-in post-completion triage. Never impersonates the submitter as editor. */
require_once __DIR__ . '/TestScope.inc.php';
class EstrabaoAutomaticTriage
{
    const SETTING = 'estrabaoAutomaticTriage';

    static function enabled() { return Config::getVar('estrabao', 'post_submission_triage') === true; }

    static function state($id)
    {
        $raw = \Illuminate\Database\Capsule\Manager::table('submission_settings')
            ->where('submission_id', (int)$id)->where('locale', '')->where('setting_name', self::SETTING)->value('setting_value');
        return $raw ? json_decode($raw, true, 32, JSON_THROW_ON_ERROR) : null;
    }

    static function store($id, $state)
    {
        $raw = json_encode($state, JSON_THROW_ON_ERROR);
        if (strlen($raw) > 131072) { throw new RuntimeException('triage_result_too_large'); }
        \Illuminate\Database\Capsule\Manager::table('submission_settings')->updateOrInsert(
            ['submission_id'=>(int)$id,'locale'=>'','setting_name'=>self::SETTING], ['setting_value'=>$raw]);
    }

    /** Called only from the native, authorized confirmation handler, after native validation. */
    function complete($form, $request)
    {
        $id = (int)$form->submissionId;
        $cid = (int)$request->getContext()->getId();
        if (!EstrabaoTestScope::matches($id)
            || (EstrabaoTestScope::active() && !EstrabaoTestScope::recipient())
            || !self::enabled() || !$request->isPost() || !$request->checkCSRF() || !$request->getUser()
            || $id <= 0 || (int)$form->step !== 4 || (int)$form->submission->getContextId() !== $cid) {
            throw new RuntimeException('triage_forbidden');
        }
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $lock = 'estrabao-triage-' . $cid . '-' . $id;
        $acquired = $db->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lock]);
        if ((int)$acquired->acquired !== 1) { throw new RuntimeException('triage_busy'); }
        try {
            $fresh = DAORegistry::getDAO('SubmissionDAO')->getById($id);
            if (!$fresh || (int)$fresh->getContextId() !== $cid) { throw new RuntimeException('triage_forbidden'); }
            // Duplicate POSTs must not send acknowledgements or record another decision.
            if ((int)$fresh->getSubmissionProgress() !== 4) { return self::state($id); }
            $form->submission = $fresh;
            $form->execute(); // Native completion, assignments, acknowledgement and event log.
            try { return $this->process($form, $request, $db); }
            catch (\Throwable $error) {
                // Completion must survive an unavailable analyzer. Native editor tasks remain active.
                error_log('Estrabao automatic triage requires editorial recovery: submission ' . $id);
                $state = self::state($id);
                if (!$state) {
                    $state = ['policy'=>'1.0.0','outcome'=>'editorial_review','issues'=>[],
                        'subject'=>'Submissão recebida — conferência editorial pendente',
                        'body'=>'A submissão foi recebida e aguarda conferência editorial. A análise automática não pôde ser concluída.',
                        'mail'=>'not_attempted','recorded_at'=>gmdate('c')];
                    self::store($id, $state);
                    $this->notifyEditors($request, $id);
                }
                return $state;
            }
        } finally { $db->select('SELECT RELEASE_LOCK(?)', [$lock]); }
    }

    private function process($form, $request, $db)
    {
        require_once __DIR__ . '/AutomaticTriagePolicy.inc.php';
        $policy = new EstrabaoAutomaticTriagePolicy();
        $result = $policy->evaluate($form);
        $editorId = (int)Config::getVar('estrabao', 'automation_editor_id');
        if (!$editorId || !DAORegistry::getDAO('RoleDAO')->userHasRole($form->context->getId(), $editorId, ROLE_ID_MANAGER)) {
            throw new RuntimeException('triage_editor_unavailable');
        }
        $id = (int)$form->submissionId;
        $state = $db->transaction(function () use ($db, $id, $form, $result, $policy, $request, $editorId) {
            $db->table('submissions')->where('submission_id', $id)->lockForUpdate()->first();
            if ($previous = self::state($id)) { return $previous; }
            $submission = DAORegistry::getDAO('SubmissionDAO')->getById($id);
            if (!$submission || (int)$submission->getSubmissionProgress() !== 0
                || (int)$submission->getStatus() !== STATUS_QUEUED
                || (int)$submission->getStageId() !== WORKFLOW_STAGE_ID_SUBMISSION) { throw new RuntimeException('triage_source_changed'); }
            $form->submission = $submission;
            $fresh = $policy->evaluate($form);
            if ($fresh !== $result) { throw new RuntimeException('triage_source_changed'); }
            $state = $result + ['editor_id'=>$editorId, 'recorded_at'=>gmdate('c'), 'mail'=>'pending'];
            if ($result['outcome'] === 'declined') {
                import('classes.workflow.EditorDecisionActionsManager');
                DAORegistry::getDAO('EditDecisionDAO')->updateEditorDecision($id,
                    ['editDecisionId'=>null,'editorId'=>$editorId,'decision'=>SUBMISSION_EDITOR_DECISION_DECLINE,
                        'dateDecided'=>Core::getCurrentDate()], WORKFLOW_STAGE_ID_SUBMISSION);
                $submission->setStatus(STATUS_DECLINED);
                $submission->stampLastActivity(); $submission->stampModified();
                DAORegistry::getDAO('SubmissionDAO')->updateObject($submission);
            }
            self::store($id, $state);
            import('classes.log.SubmissionEventLogEntry');
            $logs = DAORegistry::getDAO('SubmissionEventLogDAO');
            $entry = $logs->newDataObject(); $entry->setDateLogged(Core::getCurrentDate());
            $entry->setUserId($editorId); $entry->setSubmissionId($id);
            $entry->setEventType(SUBMISSION_LOG_EDITOR_DECISION);
            $entry->setMessage('plugins.generic.estrabao.autoLog');
            $entry->setParams(['outcome'=>$result['outcome'],'policy'=>'1.0.0','editorId'=>$editorId]);
            $entry->setIsTranslated(0); $logs->insertObject($entry);
            // updateObject must not lose the durable marker on older DAO implementations.
            self::store($id, $state);
            return $state;
        });
        if ($state['outcome'] === 'editorial_review') { $this->notifyEditors($request, $id); }
        // Persist 'sending' before handing off to the mailer. An uncertain send is never retried automatically.
        $state['mail'] = 'sending'; self::store($id, $state);
        try {
            import('classes.mail.ArticleMailTemplate');
            $mail = new ArticleMailTemplate($form->submission, null, null, null, false);
            $context = $request->getContext();
            $mail->setFrom($context->getData('contactEmail'), $context->getData('contactName'));
            $recipients = [];
            foreach (EstrabaoTestScope::active() ? [] : $form->submission->getAuthors() as $author) {
                $email = $author->getEmail();
                if ($email && !isset($recipients[strtolower($email)])) {
                    $mail->addRecipient($email, $author->getFullName()); $recipients[strtolower($email)] = true;
                }
            }
            $user = $request->getUser();
            if (EstrabaoTestScope::active()) {
                $mail->addRecipient(EstrabaoTestScope::recipient());
                $state['subject'] = '[TESTE HOMOLOGAÇÃO] ' . $state['subject'];
            } elseif (!isset($recipients[strtolower($user->getEmail())])) { $mail->addRecipient($user->getEmail(), $user->getFullName()); }
            $mail->setSubject($state['subject']);
            $mail->setBody(nl2br(htmlspecialchars($state['body'], ENT_QUOTES, 'UTF-8')));
            $state['mail'] = $mail->send($request) ? 'sent' : 'failed';
        } catch (\Throwable $error) { $state['mail'] = 'failed'; }
        self::store($id, $state);
        if ($state['mail'] !== 'sent') { $this->notifyEditors($request, $id); }
        return $state;
    }

    private function notifyEditors($request, $id)
    {
        import('classes.notification.NotificationManager');
        $users = DAORegistry::getDAO('RoleDAO')->getUsersByRoleId(ROLE_ID_MANAGER, $request->getContext()->getId());
        $manager = new NotificationManager();
        while ($user = $users->next()) {
            $manager->createNotification($request, $user->getId(), NOTIFICATION_TYPE_SUBMISSION_SUBMITTED,
                $request->getContext()->getId(), ASSOC_TYPE_SUBMISSION, $id);
        }
    }
}
