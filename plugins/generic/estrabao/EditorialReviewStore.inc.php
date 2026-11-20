<?php
/** Separate private checklist ledger; never read by APA admission/release. */
class EstrabaoEditorialReviewStore
{
    const VERSION = '1.0.0';
    const ITEMS = ['metadata','abstracts','authors','structure','length','presentation','visuals','footnotes','credit','funding','ethics','ai'];
    const CONDITIONAL = ['visuals','footnotes','funding','ethics'];

    static function states($values)
    {
        if (!is_array($values) || count($values) !== count(self::ITEMS) || array_diff(array_keys($values), self::ITEMS)) { throw new RuntimeException('editorial_unavailable'); }
        $states = [];
        foreach (self::ITEMS as $item) {
            $allowed = ['pending','reviewed','adjustments'];
            if (in_array($item, self::CONDITIONAL, true)) { $allowed[] = 'not_applicable'; }
            if (!is_string($values[$item] ?? null) || !in_array($values[$item], $allowed, true)) { throw new RuntimeException('editorial_unavailable'); }
            $states[$item] = $values[$item];
        }
        return $states;
    }

    private function validate($ledger, $contextId, $submissionId)
    {
        if (!is_array($ledger) || array_keys($ledger) !== ['schema_version','context_id','submission_id','events']
            || $ledger['schema_version'] !== 1 || $ledger['context_id'] !== $contextId || $ledger['submission_id'] !== $submissionId
            || !is_array($ledger['events']) || array_values($ledger['events']) !== $ledger['events'] || count($ledger['events']) > 100) { throw new RuntimeException('editorial_unavailable'); }
        foreach ($ledger['events'] as $i => $event) {
            if (!is_array($event) || array_keys($event) !== ['sequence','reviewer_id','recorded_at','fingerprint','manuscript_type','checklist_version','states']
                || $event['sequence'] !== $i + 1 || !is_int($event['reviewer_id']) || $event['reviewer_id'] <= 0
                || !is_string($event['fingerprint']) || !preg_match('/^[a-f0-9]{64}$/D', $event['fingerprint'])
                || !in_array($event['manuscript_type'], ['article','research_communication'], true) || $event['checklist_version'] !== self::VERSION
                || !is_string($event['recorded_at'])) { throw new RuntimeException('editorial_unavailable'); }
            $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $event['recorded_at'], new DateTimeZone('UTC'));
            if (!$time || $time->format('Y-m-d\TH:i:s\Z') !== $event['recorded_at']) { throw new RuntimeException('editorial_unavailable'); }
            self::states($event['states']);
        }
    }

    function transaction($contextId, $submissionId, $callback)
    {
        $contextId = (int) $contextId; $submissionId = (int) $submissionId;
        if ($contextId <= 0 || $submissionId <= 0) { throw new RuntimeException('editorial_unavailable'); }
        $configured = Config::getVar('estrabao', 'review_dir');
        $files = realpath(Config::getVar('files', 'files_dir')); $root = is_string($configured) ? realpath($configured) : false;
        if (!$files || !$root || !is_dir($root) || is_link($configured) || strpos($root, $files . DIRECTORY_SEPARATOR) !== 0
            || (fileperms($root) & 0077) !== 0) { throw new RuntimeException('editorial_unavailable'); }
        $path = $root . '/editorial-' . $contextId . '-' . $submissionId . '.json';
        if (is_link($path) || is_link($path . '.lock')) { throw new RuntimeException('editorial_unavailable'); }
        $handle = fopen($path . '.lock', 'c+b');
        if (!$handle) { throw new RuntimeException('editorial_unavailable'); }
        try {
            if (!chmod($path . '.lock', 0600) || !flock($handle, LOCK_EX | LOCK_NB)) { throw new RuntimeException('editorial_unavailable'); }
            clearstatcache(true, $path);
            if (is_file($path) && (filesize($path) > 262144 || (fileperms($path) & 0077) !== 0)) { throw new RuntimeException('editorial_unavailable'); }
            $raw = is_file($path) ? file_get_contents($path) : '';
            if ($raw === false || ($raw === '' && is_file($path))) { throw new RuntimeException('editorial_unavailable'); }
            $ledger = $raw === '' ? ['schema_version'=>1,'context_id'=>$contextId,'submission_id'=>$submissionId,'events'=>[]] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            $this->validate($ledger, $contextId, $submissionId); $before = $ledger;
            $result = $callback($ledger);
            $this->validate($ledger, $contextId, $submissionId);
            if ($ledger !== $before) {
                // Only append; recorded reviews are never overwritten or deleted.
                if (count($ledger['events']) !== count($before['events']) + 1 || array_slice($ledger['events'], 0, count($before['events'])) !== $before['events']) { throw new RuntimeException('editorial_unavailable'); }
                $encoded = json_encode($ledger, JSON_THROW_ON_ERROR);
                if (strlen($encoded) > 262144) { throw new RuntimeException('editorial_unavailable'); }
                $temporary = tempnam($root, '.editorial-');
                if (!$temporary) { throw new RuntimeException('editorial_unavailable'); }
                try {
                    if (!chmod($temporary, 0600) || file_put_contents($temporary, $encoded) !== strlen($encoded) || !rename($temporary, $path)) { throw new RuntimeException('editorial_unavailable'); }
                } finally { if (is_file($temporary)) { unlink($temporary); } }
            }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
}
