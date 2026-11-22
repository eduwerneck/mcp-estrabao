<?php
/** OJS 3.3.0.20: API legada; bloqueio de confirmação opt-in em homologação. */
import('lib.pkp.classes.plugins.GenericPlugin');

class EstrabaoPlugin extends GenericPlugin
{
    function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) { return false; }
        if ($this->getEnabled($mainContextId)) {
            HookRegistry::register('TemplateManager::display', [$this, 'articleSubmittedDate']);
            HookRegistry::register('Form::config::after', [$this, 'titleFormConfig']);
            HookRegistry::register('TemplateManager::display', [$this, 'titleFormAssets']);
        }
        if ($this->getEnabled($mainContextId) && Config::getVar('estrabao', 'admission_gate') === true) {
            HookRegistry::register('SubmissionHandler::saveSubmit', [$this, 'automaticConfirmation']);
            HookRegistry::register('submissionsubmitstep3form::Constructor', [$this, 'automaticMetadata']);
            HookRegistry::register('TemplateManager::fetch', [$this, 'automaticCompletion']);
            HookRegistry::register('submissionsubmitstep4form::validate', [$this, 'validateAdmission']);
            HookRegistry::register('LoadHandler', [$this, 'loadReviewHandler']);
            HookRegistry::register('TemplateManager::display', [$this, 'reviewNavigationAssets']);
        }
        return true;
    }

    /** Only the public article landing page; no galley, preview or metadata writes. */
    function articleSubmittedDate($hookName, $args)
    {
        if (($args[1] ?? null) !== 'frontend/pages/article.tpl') { return false; }
        $request = Application::get()->getRequest(); $context = $request->getContext();
        if (!$context || !$this->getEnabled($context->getId())) { return false; }
        try {
            $manager = $args[0];
            require_once __DIR__ . '/ArticleSubmittedDate.inc.php';
            $model = EstrabaoArticleSubmittedDate::model($manager->getTemplateVars('article'),
                $manager->getTemplateVars('publication'), $manager->getTemplateVars('firstPublication'), $context->getId());
            if ($model === null) { return false; }
            $manager->assign('estrabaoSubmittedDate', $model);
            $markup = $manager->fetch($this->getTemplateResource('articleSubmittedDate.tpl'));
            $manager->registerFilter('output', function ($html) use ($markup) {
                return EstrabaoArticleSubmittedDate::insert($html, $markup);
            });
        } catch (\Throwable $error) {
            // Date unavailable: leave the native published box intact, without guessing.
        }
        return false;
    }

    /** Advisory descriptions in the publication metadata form (author/editor). */
    function titleFormConfig($hookName, $args)
    {
        $context = Application::get()->getRequest()->getContext();
        if (!$context || !$this->getEnabled($context->getId())) { return false; }
        $config =& $args[0];
        if (($config['id'] ?? null) !== 'titleAbstract') { return false; }
        foreach ($config['fields'] as &$field) {
            if (!in_array($field['name'] ?? null, ['title', 'subtitle'], true)) { continue; }
            $hint = htmlspecialchars(__('plugins.generic.estrabao.titleGuidance'), ENT_QUOTES, 'UTF-8');
            $field['description'] = ($field['description'] ?? '') . '<p>' . $hint . '</p>';
        }
        unset($field);
        return false;
    }

    /** Legacy wizard fields are inserted by AJAX; the script only adds help text. */
    function titleFormAssets($hookName, $args)
    {
        $request = Application::get()->getRequest(); $context = $request->getContext();
        if (!$context || !$this->getEnabled($context->getId())) { return false; }
        $manager = $args[0];
        $manager->addJavaScript('estrabao-title-guidance-config', 'window.estrabaoTitleGuidance=' .
            json_encode(__('plugins.generic.estrabao.titleGuidance'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';',
            ['inline'=>true, 'contexts'=>['backend'], 'priority'=>10]);
        $manager->addJavaScript('estrabao-title-guidance', $request->getBaseUrl() . '/' . $this->getPluginPath() . '/title-guidance.js',
            ['contexts'=>['backend'], 'priority'=>20]);
        $manager->addJavaScript('estrabao-reference-guidance-config', 'window.estrabaoReferenceGuidance=' .
            json_encode(['help'=>__('plugins.generic.estrabao.referenceHelp'),
                'warning'=>__('plugins.generic.estrabao.referenceJoinedWarning'),
                'review'=>__('plugins.generic.estrabao.referenceSeparationReview')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';',
            ['inline'=>true, 'contexts'=>['backend'], 'priority'=>10]);
        $manager->addJavaScript('estrabao-reference-guidance', $request->getBaseUrl() . '/' . $this->getPluginPath() . '/reference-guidance.js',
            ['contexts'=>['backend'], 'priority'=>20]);
        if (Config::getVar('estrabao', 'post_submission_triage') === true) {
            $manager->addJavaScript('estrabao-funding-guidance', $request->getBaseUrl() . '/' . $this->getPluginPath() . '/funding-guidance.js',
                ['contexts'=>['backend'], 'priority'=>20]);
        }
        return false;
    }

    /** Add a contextual action to expanded submission-list rows, for editorial managers only. */
    function reviewNavigationAssets($hookName, $args)
    {
        if (($args[1] ?? null) !== 'dashboard/index.tpl') { return false; }
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !$this->getEnabled($context->getId())
            || Config::getVar('estrabao', 'admission_gate') !== true) { return false; }
        require_once __DIR__ . '/AdmissionReviewService.inc.php';
        try { (new EstrabaoAdmissionReviewService())->requireEditor($request); }
        catch (\Throwable $error) { return false; }
        $manager = $args[0];
        $config = ['url' => $request->getRouter()->url($request, null, 'estrabaoAdmission', 'review'),
            'label' => __('plugins.generic.estrabao.viewTriage')];
        $manager->addJavaScript('estrabao-review-navigation-config', 'window.estrabaoReviewNavigation=' .
            json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';',
            ['inline'=>true, 'contexts'=>['backend'], 'priority'=>10]);
        $manager->addJavaScript('estrabao-review-navigation', $request->getBaseUrl() . '/' . $this->getPluginPath() . '/review-navigation.js',
            ['contexts'=>['backend'], 'priority'=>20]);
        return false;
    }

    function validateAdmission($hookName, $args)
    {
        if (Config::getVar('estrabao', 'post_submission_triage') === true) { return false; }
        $form = $args[0];
        try {
            $context = Application::get()->getRequest()->getContext();
            if ($context && !$this->getEnabled($context->getId())) { return false; }
            require_once __DIR__ . '/AdmissionBridge.inc.php';
            $analysis = (new EstrabaoAdmissionBridge())->analyze($form);
            $decision = $analysis['decision'];
            require_once __DIR__ . '/AdmissionReviewService.inc.php';
            if ((new EstrabaoAdmissionReviewService())->allows($form, $analysis)) {
                // Let Form::validate preserve all native errors, including POST/CSRF.
                return false;
            }
        } catch (\Throwable $error) {
            // Never disclose internal paths, manuscript content or exception details.
            $decision = 'manual_review_required';
        }
        $key = $decision === 'not_accepted' ? 'apaNotAccepted' : ((($analysis['source_format']??null)==='doc') ? 'docAuthorPending' : 'apaReviewRequired');
        $form->addError('estrabaoAdmission', __('plugins.generic.estrabao.' . $key));
        if ($decision === 'not_accepted') {
            foreach ($analysis['corrections'] ?? [] as $index => $correction) {
                $form->addError('estrabaoCorrection' . $index, $correction);
            }
        }
        $args[1] = false;
        // Continue Form::validate: its native notification exposes these errors.
        // Returning true here would skip notification creation and hide the reason.
        return false;
    }

    /** Keep native fields visible in the author wizard, without modifying stored journal settings. */
    function automaticMetadata($hookName, $args)
    {
        $context = Application::get()->getRequest()->getContext();
        if ($context && $this->getEnabled($context->getId())
            && Config::getVar('estrabao', 'post_submission_triage') === true) {
            $context->setData('citations', 'request');
            $context->setData('agencies', 'request');
        }
        return false;
    }

    function automaticConfirmation($hookName, $args)
    {
        $request = Application::get()->getRequest(); $context = $request->getContext();
        if ((int)$args[0] !== 4 || !$context || !$this->getEnabled($context->getId())
            || Config::getVar('estrabao', 'post_submission_triage') !== true) { return false; }
        $form = $args[2];
        // Native handler already authorizes the submission. Native form checks POST and CSRF.
        if (!$form->validate()) { return false; }
        require_once __DIR__ . '/AutomaticTriage.inc.php';
        try {
            (new EstrabaoAutomaticTriage())->complete($form, $request);
            $json = new JSONMessage(true); $json->setEvent('setStep', 5);
        } catch (\Throwable $error) {
            $json = new JSONMessage(false, __('plugins.generic.estrabao.autoRetry'));
        }
        // This native hook has no response slot; emit the same JSON envelope as saveStep.
        header('Content-Type: application/json; charset=utf-8');
        echo $json->getString();
        exit;
    }

    function automaticCompletion($hookName, $args)
    {
        if ($args[1] !== 'submission/form/complete.tpl'
            || Config::getVar('estrabao', 'post_submission_triage') !== true) { return false; }
        $request = Application::get()->getRequest(); $context = $request->getContext();
        if (!$context || !$this->getEnabled($context->getId())) { return false; }
        // This template is fetched only by the authorized native submission handler.
        $manager = $args[0]; $id = (int)$manager->getTemplateVars('submissionId');
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($id);
        if (!$submission || (int)$submission->getContextId() !== (int)$context->getId()) { return false; }
        require_once __DIR__ . '/AutomaticTriage.inc.php';
        $state = EstrabaoAutomaticTriage::state($id);
        if (!$state) { return false; }
        $manager->assign('automaticTriage', $state);
        $args[4] = $manager->fetch($this->getTemplateResource('automaticComplete.tpl'));
        return true;
    }

    function loadReviewHandler($hookName, $args)
    {
        if ($args[0] !== 'estrabaoAdmission' || !in_array($args[1], ['review', 'saveReview', 'download', 'deskDraft', 'saveEditorialReview'], true)) { return false; }
        $context = Application::get()->getRequest()->getContext();
        if (!$context || !$this->getEnabled($context->getId())
            || Config::getVar('estrabao', 'admission_gate') !== true) { return false; }
        require_once __DIR__ . '/AdmissionReviewHandler.inc.php';
        EstrabaoAdmissionReviewHandler::$plugin = $this;
        define('HANDLER_CLASS', 'EstrabaoAdmissionReviewHandler');
        return true;
    }

    function getActions($request, $actionArgs)
    {
        $actions = parent::getActions($request, $actionArgs);
        $context = $request->getContext();
        if ($context && $this->getEnabled($context->getId()) && Config::getVar('estrabao', 'admission_gate') === true) {
            import('lib.pkp.classes.linkAction.LinkAction');
            import('lib.pkp.classes.linkAction.request.RedirectAction');
            $actions[] = new LinkAction('admissionReview', new RedirectAction(
                $request->getRouter()->url($request, null, 'estrabaoAdmission', 'review')
            ), __('plugins.generic.estrabao.reviewTitle'));
        }
        return $actions;
    }

    function getDisplayName()
    {
        return __('plugins.generic.estrabao.displayName');
    }

    function getDescription()
    {
        return __('plugins.generic.estrabao.description');
    }
}
