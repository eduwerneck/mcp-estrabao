<?php
/** Reads only the current, authorized OJS publication; never request metadata. */
class EstrabaoSubmissionSnapshot
{
    function read($form)
    {
        $publication = $form->submission->getCurrentPublication();
        if (!$publication || (int) $publication->getId() <= 0
            || (int) $publication->getId() !== (int) $form->submission->getData('currentPublicationId')
            || (int) $publication->getData('submissionId') !== (int) $form->submissionId) {
            throw new RuntimeException('desk_source_unavailable');
        }
        $data = ['schema_version' => '1.1'];
        foreach (['title', 'subtitle', 'abstract', 'keywords'] as $field) {
            // A successful DAO read of an empty field establishes absence.
            $map = $publication->getData($field);
            if ($map === null) { $map = []; }
            $data[$field] = null;
            if (!is_array($map)) { continue; }
            $normalized = []; $valid = true;
            foreach (array_keys($map) as $key) {
                if (is_string($key) && preg_match('/^(pt|en|es)(?:_|$)/D', $key)
                    && !in_array($key, ['pt_BR', 'en_US', 'es_ES'], true)) { $valid = false; }
            }
            foreach (['pt_BR', 'en_US', 'es_ES'] as $locale) {
                if (!array_key_exists($locale, $map)) { continue; }
                $value = $map[$locale];
                if ($value === null) { continue; }
                if ($field === 'keywords') {
                    if (!is_array($value) || array_values($value) !== $value) { $valid = false; break; }
                    foreach ($value as $item) { if (!is_string($item)) { $valid = false; break; } }
                } elseif (!is_string($value)) { $valid = false; break; }
                $normalized[$locale] = $value;
            }
            if ($valid) { $data[$field] = $normalized; }
        }
        $authors = $publication->getData('authors'); $data['authors'] = null;
        if (is_array($authors) && count($authors) <= 2048) {
            $rows = []; $valid = true;
            foreach ($authors as $author) {
                if (!is_object($author) || !method_exists($author, 'getData')) { $valid = false; break; }
                $orcid = $author->getData('orcid');
                if ($orcid !== null && !is_string($orcid)) { $valid = false; break; }
                $rows[] = ['orcid' => $orcid];
            }
            if ($valid) { $data['authors'] = $rows; }
        }
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 1048576) { throw new RuntimeException('desk_source_unavailable'); }
        return ['snapshot' => new \Estrabao\Contracts\Submission($data),
            'publication_id' => (int) $publication->getId(), 'publication_version' => (int) $publication->getData('version')];
    }
}
