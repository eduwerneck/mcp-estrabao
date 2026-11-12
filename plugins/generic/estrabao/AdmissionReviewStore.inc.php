<?php
/** Private, bounded ledger. No manuscript contents, titles or paths are persisted. */
class EstrabaoAdmissionReviewStore
{
    function transaction($contextId, $submissionId, $callback)
    {
        if ((int) $contextId <= 0 || (int) $submissionId <= 0) { throw new RuntimeException('review_unavailable'); }
        $configured = Config::getVar('estrabao', 'review_dir');
        $files = realpath(Config::getVar('files', 'files_dir'));
        $root = is_string($configured) ? realpath($configured) : false;
        if (!$files || !$root || !is_dir($root) || is_link($configured)
            || strpos($root, $files . DIRECTORY_SEPARATOR) !== 0 || (fileperms($root) & 0077) !== 0) {
            throw new RuntimeException('review_unavailable');
        }
        $path = $root . '/review-' . (int) $contextId . '-' . (int) $submissionId . '.json';
        if (is_link($path) || is_link($path . '.lock')) { throw new RuntimeException('review_unavailable'); }
        $handle = fopen($path . '.lock', 'c+b');
        if (!$handle) { throw new RuntimeException('review_unavailable'); }
        try {
            if (!chmod($path . '.lock', 0600) || !flock($handle, LOCK_EX | LOCK_NB)) { throw new RuntimeException('review_unavailable'); }
            clearstatcache(true, $path);
            if (is_file($path) && (filesize($path) > 262144 || (fileperms($path) & 0077) !== 0)) { throw new RuntimeException('review_unavailable'); }
            $raw = is_file($path) ? file_get_contents($path) : '';
            if ($raw === false) { throw new RuntimeException('review_unavailable'); }
            $ledger = $raw === '' ? ['schema_version' => 1, 'context_id' => (int) $contextId,
                'submission_id' => (int) $submissionId, 'events' => []] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($ledger) || ($ledger['schema_version'] ?? null) !== 1
                || ($ledger['context_id'] ?? null) !== (int) $contextId
                || ($ledger['submission_id'] ?? null) !== (int) $submissionId
                || !isset($ledger['events']) || !is_array($ledger['events'])
                || array_values($ledger['events']) !== $ledger['events'] || count($ledger['events']) > 100) {
                throw new RuntimeException('review_unavailable');
            }
            foreach ($ledger['events'] as $i => $event) {
                if (!is_array($event) || ($event['sequence'] ?? null) !== $i + 1
                    || !in_array($event['action'] ?? null, ['approve', 'revoke'], true)
                    || !is_int($event['reviewer_id'] ?? null) || $event['reviewer_id'] <= 0
                    || !is_string($event['fingerprint'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $event['fingerprint'])
                    || !is_string($event['recorded_at'] ?? null)
                    || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $event['recorded_at'])) { throw new RuntimeException('review_unavailable'); }
            }
            $before = $ledger;
            $result = $callback($ledger);
            if ($ledger !== $before) {
                $encoded = json_encode($ledger, JSON_THROW_ON_ERROR);
                if (strlen($encoded) > 262144 || count($ledger['events']) > 100) { throw new RuntimeException('review_unavailable'); }
                $temporary = tempnam($root, '.review-');
                if (!$temporary) { throw new RuntimeException('review_unavailable'); }
                try {
                    if (!chmod($temporary, 0600) || file_put_contents($temporary, $encoded) !== strlen($encoded)
                        || !rename($temporary, $path)) { throw new RuntimeException('review_unavailable'); }
                } finally { if (is_file($temporary)) { unlink($temporary); } }
            }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
}
