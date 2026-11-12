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
            $decision = (new EstrabaoAdmissionBridge())->inspect($form);
        } catch (\Throwable $error) {
            // Never disclose internal paths, manuscript content or exception details.
            $decision = 'manual_review_required';
        }
        $key = $decision === 'not_accepted' ? 'apaNotAccepted' : 'apaReviewRequired';
        $form->addError('estrabaoAdmission', __('plugins.generic.estrabao.' . $key));
        $args[1] = false;
        return true; // Form::validate returns false; execute() is not reached.
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
