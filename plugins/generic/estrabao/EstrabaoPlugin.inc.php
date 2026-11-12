<?php
/** OJS 3.3.0.20: API legada; bloqueio de confirmação opt-in em homologação. */
import('lib.pkp.classes.plugins.GenericPlugin');

class EstrabaoPlugin extends GenericPlugin
{
    function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) { return false; }
        if ($this->getEnabled($mainContextId) && Config::getVar('estrabao', 'admission_gate') === true) {
            HookRegistry::register('submissionsubmitstep4form::validate', [$this, 'validateAdmission']);
            HookRegistry::register('LoadHandler', [$this, 'loadReviewHandler']);
        }
        return true;
    }

    function validateAdmission($hookName, $args)
    {
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
        $key = $decision === 'not_accepted' ? 'apaNotAccepted' : 'apaReviewRequired';
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

    function loadReviewHandler($hookName, $args)
    {
        if ($args[0] !== 'estrabaoAdmission' || !in_array($args[1], ['review', 'saveReview', 'download', 'deskDraft'], true)) { return false; }
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
