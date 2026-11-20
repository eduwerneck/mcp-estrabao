<?php
require_once __DIR__ . '/AdmissionReviewService.inc.php';
require_once __DIR__ . '/EditorialReviewStore.inc.php';

class EstrabaoEditorialReviewService
{
    private function authorDigest($form)
    {
        $publication = $form->submission->getCurrentPublication();
        if (!$publication || (int)$publication->getId() !== (int)$form->submission->getData('currentPublicationId')
            || (int)$publication->getData('submissionId') !== $form->submissionId) { throw new RuntimeException('editorial_unavailable'); }
        $authors = $publication->getData('authors'); $rows = null;
        if ($authors !== null) {
            if (!is_array($authors) || count($authors)>2048) { throw new RuntimeException('editorial_unavailable'); }
            $rows=[];
            foreach ($authors as $author) {
                if (!is_object($author) || !method_exists($author,'getData')) { throw new RuntimeException('editorial_unavailable'); }
                $row=[];
                foreach (['id','seq','givenName','familyName','affiliation','orcid','country','email'] as $field) {
                    $value=$author->getData($field);
                    if ($value!==null && !is_scalar($value) && !is_array($value)) { throw new RuntimeException('editorial_unavailable'); }
                    if (is_array($value)) { foreach ($value as $part) { if ($part!==null && !is_scalar($part)) { throw new RuntimeException('editorial_unavailable'); } } }
                    $row[$field]=$value;
                }
                $rows[]=$row;
            }
        }
        $encoded=json_encode($rows,JSON_THROW_ON_ERROR);
        if(strlen($encoded)>1048576){throw new RuntimeException('editorial_unavailable');}
        return hash('sha256',$encoded);
    }

    private function fingerprint(array $draft,$authorDigest)
    {
        if(!is_string($draft['fingerprint']??null) || !preg_match('/^[a-f0-9]{64}$/D',$draft['fingerprint']) || !in_array($draft['manuscript_type']??null,['article','research_communication'],true)){throw new RuntimeException('editorial_unavailable');}
        return hash('sha256', 'editorial-checklist-'.EstrabaoEditorialReviewStore::VERSION."\0".$draft['fingerprint']."\0".$authorDigest);
    }

    function view($request, $submissionId, array $draft)
    {
        $form = (new EstrabaoAdmissionReviewService())->form($request, $submissionId);
        $fingerprint = $this->fingerprint($draft,$this->authorDigest($form));
        $ledger = (new EstrabaoEditorialReviewStore())->transaction($form->context->getId(), $form->submissionId, function (&$ledger) { return $ledger; });
        $events = $ledger['events']; $last = $events ? $events[count($events)-1] : null;
        $current = $last && hash_equals($fingerprint, $last['fingerprint']);
        $states = $current ? $last['states'] : array_fill_keys(EstrabaoEditorialReviewStore::ITEMS, 'pending');
        $items = [];
        foreach ($states as $item => $state) { $items[] = ['id'=>$item,'label'=>'editorialItem_' . $item,'state'=>$state,'conditional'=>in_array($item, EstrabaoEditorialReviewStore::CONDITIONAL, true)]; }
        foreach ($events as &$event) {
            $event['display_time'] = (new DateTimeImmutable($event['recorded_at']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i:s T');
            $event['matches_analysis'] = hash_equals($fingerprint, $event['fingerprint']);
        }
        unset($event);
        return ['available'=>true,'fingerprint'=>$fingerprint,'revision'=>count($events),'items'=>$items,'events'=>$events,
            'current'=>$current,'has_history'=>(bool)$events,'can_save'=>count($events)<100];
    }

    function save($request, $submissionId)
    {
        $service = new EstrabaoAdmissionReviewService(); $service->requireEditor($request);
        if (!$request->isPost() || !$request->getUserVar('csrfToken') || !$request->checkCSRF() || $request->getUserVar('editorialReviewed') !== '1') { throw new RuntimeException('editorial_unavailable'); }
        $expected = $request->getUserVar('editorialFingerprint'); $revision = $request->getUserVar('editorialRevision');
        if (!is_string($expected) || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !is_scalar($revision) || !preg_match('/^(0|[1-9][0-9]{0,2})$/D', (string)$revision)) { throw new RuntimeException('editorial_unavailable'); }
        $states = EstrabaoEditorialReviewStore::states($request->getUserVar('editorialStates'));
        $form = $service->form($request, $submissionId);
        return (new EstrabaoEditorialReviewStore())->transaction($form->context->getId(), $form->submissionId,
            function (&$ledger) use ($service, $request, $submissionId, $expected, $revision, $states) {
                $service->requireEditor($request);
                if ((int)$revision !== count($ledger['events']) || count($ledger['events']) >= 100) { throw new RuntimeException('editorial_unavailable'); }
                // Server-resolved snapshot and file are checked again while holding the checklist lock.
                $before=$this->authorDigest($service->form($request,$submissionId));
                $draft = $service->draft($request, $submissionId);
                $after=$this->authorDigest($service->form($request,$submissionId));
                if (!hash_equals($before,$after) || !hash_equals($expected, $this->fingerprint($draft,$after))) { throw new RuntimeException('editorial_unavailable'); }
                $ledger['events'][] = ['sequence'=>count($ledger['events'])+1,'reviewer_id'=>(int)$request->getUser()->getId(),
                    'recorded_at'=>gmdate('Y-m-d\TH:i:s\Z'),'fingerprint'=>$expected,'manuscript_type'=>$draft['manuscript_type'],
                    'checklist_version'=>EstrabaoEditorialReviewStore::VERSION,'states'=>$states];
                return $draft;
            });
    }
}
