<?php
/** Optional isolation for a single production draft. No request value expands the configured scope. */
class EstrabaoTestScope
{
    static function active()
    {
        return Config::getVar('estrabao', 'test_submission_id') !== null;
    }

    static function id()
    {
        $value = Config::getVar('estrabao', 'test_submission_id');
        return (is_int($value) || is_string($value)) && preg_match('/^[1-9][0-9]{0,8}$/D', (string)$value) ? (int)$value : 0;
    }

    static function matches($id)
    {
        return !self::active() || (self::id() > 0 && (is_int($id) || is_string($id))
            && preg_match('/^[1-9][0-9]{0,8}$/D', (string)$id) && (int)$id === self::id());
    }

    static function requestMatches($request)
    {
        return !self::active() || self::matches($request->getUserVar('submissionId'));
    }

    static function recipient()
    {
        $email = Config::getVar('estrabao', 'test_recipient');
        return is_string($email) && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
