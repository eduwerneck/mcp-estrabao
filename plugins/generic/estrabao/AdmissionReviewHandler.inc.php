<?php
import('classes.handler.Handler');
require_once __DIR__ . '/AdmissionReviewService.inc.php';

class EstrabaoAdmissionReviewHandler extends Handler
{
    public static $plugin;

    function authorize($request, &$args, $roleAssignments)
    {
        try {
            (new EstrabaoAdmissionReviewService())->requireEditor($request);
            return parent::authorize($request, $args, $roleAssignments);
        } catch (\Throwable $error) { return false; }
    }

    function review($args, $request)
    {
        $this->renderReview($request, $request->getUserVar('submissionId'), null);
    }

    function download($args, $request)
    {
        try { (new EstrabaoAdmissionReviewService())->download($request, $request->getUserVar('submissionId')); }
        catch (\Throwable $error) { http_response_code(403); }
    }

    function saveReview($args, $request)
    {
        try {
            (new EstrabaoAdmissionReviewService())->save($request, $request->getUserVar('submissionId'));
            $message = 'plugins.generic.estrabao.reviewSaved';
        } catch (\Throwable $error) {
            $message = 'plugins.generic.estrabao.reviewNotSaved';
        }
        $this->renderReview($request, $request->getUserVar('submissionId'), $message);
    }

    private function renderReview($request, $submissionId, $message)
    {
        try { (new EstrabaoAdmissionReviewService())->requireEditor($request); }
        catch (\Throwable $error) { http_response_code(403); return; }
        $this->setupTemplate($request);
        $manager = TemplateManager::getManager($request);
        $model = null;
        if ($submissionId !== null && $submissionId !== '') {
            try { $model = (new EstrabaoAdmissionReviewService())->view($request, $submissionId); }
            catch (\Throwable $error) { $message = 'plugins.generic.estrabao.reviewUnavailable'; }
        }
        $manager->assign('downloadReviewUrl', $model && $model['analysis']['fingerprint']
            ? $request->getRouter()->url($request, null, 'estrabaoAdmission', 'download', null,
                ['submissionId' => $model['submission_id'], 'fingerprint' => $model['analysis']['fingerprint']]) : null);
        $manager->assign('reviewModel', $model);
        $manager->assign('reviewMessage', $message);
        $reviewUrl = $request->getRouter()->url($request, null, 'estrabaoAdmission', 'review');
        $lookupParams = [];
        parse_str(parse_url($reviewUrl, PHP_URL_QUERY) ?: '', $lookupParams);
        $manager->assign('reviewLookupParams', array_filter($lookupParams, 'is_scalar'));
        $manager->assign('reviewUrl', $reviewUrl);
        $manager->assign('saveReviewUrl', $request->getRouter()->url($request, null, 'estrabaoAdmission', 'saveReview'));
        $manager->display(self::$plugin->getTemplateResource('admissionReview.tpl'));
    }
}
