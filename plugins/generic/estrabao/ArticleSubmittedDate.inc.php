<?php
/** Public calendar date from the authorized OJS Submission, never request input. */
class EstrabaoArticleSubmittedDate
{
    static function model($article, $publication, $firstPublication, $contextId)
    {
        foreach ([$article, $publication, $firstPublication] as $record) {
            if (!is_object($record) || !method_exists($record, 'getData') || !method_exists($record, 'getId')) { return null; }
        }
        if ((int) $contextId <= 0 || (int) $article->getId() <= 0
            || (int) $article->getData('contextId') !== (int) $contextId
            || (int) $article->getData('status') !== 3 || (int) $article->getData('submissionProgress') !== 0
            || (int) $publication->getId() <= 0 || (int) $firstPublication->getId() <= 0) { return null; }
        foreach ([$publication, $firstPublication] as $record) {
            if ((int) $record->getData('submissionId') !== (int) $article->getId()
                || (int) $record->getData('status') !== 3) { return null; }
        }
        $submitted = self::day($article->getData('dateSubmitted'));
        $published = self::day($publication->getData('datePublished'));
        $first = self::day($firstPublication->getData('datePublished'));
        if ($submitted === null || $published === null || $first === null || $submitted > $first || $first > $published) { return null; }
        return ['iso_date'=>$submitted];
    }

    private static function day($value)
    {
        if (!is_string($value) || !preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})(?: ([0-9]{2}):([0-9]{2}):([0-9]{2}))?$/D', $value, $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            || (isset($m[4]) && ((int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59))) { return null; }
        // Keep the calendar day stored by OJS; do not guess a UTC offset for a zone-less value.
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    static function insert($html, $markup)
    {
        if (!is_string($html) || !is_string($markup) || $markup === '' || strlen($html) > 16777216
            || strpos($html, 'estrabao-submitted-date') !== false) { return $html; }
        // Explicit compatibility profile: OJS 3.3 default article details markup.
        $anchors = ['<article class="obj_article_details">', '<div class="entry_details">', '<div class="item published">'];
        $positions = [];
        foreach ($anchors as $anchor) {
            if (substr_count($html, $anchor) !== 1) { return $html; }
            $positions[] = strpos($html, $anchor);
        }
        if ($positions[0] >= $positions[1] || $positions[1] >= $positions[2]) { return $html; }
        $end = strpos($html, '</article>', $positions[0]);
        if ($end === false || $positions[2] >= $end) { return $html; }
        $offset = $positions[2] + strlen($anchors[2]);
        return substr($html, 0, $offset) . $markup . substr($html, $offset);
    }
}
