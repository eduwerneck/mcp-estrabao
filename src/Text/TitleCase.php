<?php
declare(strict_types=1);
namespace Estrabao\Text;
use Estrabao\Contracts\CheckResult;

/** Flags full uppercase only. Does not prescribe sentence case or rewrite acronyms. */
final class TitleCase
{
    const POLICY = 'evitar-caixa-alta-confirmado-2026-11-12';
    private const CONNECTORS = [
        'pt' => 'a|as|o|os|de|da|do|das|dos|e|em|na|no|nas|nos|para|com|sem|por|sobre',
        'en' => 'a|an|the|of|and|in|on|for|with|without|by|to',
        'es' => 'el|la|los|las|de|del|y|en|para|con|sin|por|sobre',
    ];

    public static function inspect(?string $text, ?string $language = null, bool $knownOrdinaryHeading = false, bool $forcedAllCaps = false): array
    {
        $result = ['status' => CheckResult::UNKNOWN, 'all_caps' => null, 'reason' => 'text_unavailable'];
        if ($text === null || preg_match('//u', $text) !== 1 || preg_match('/\p{M}/u', $text)) { return $result; }
        $upper = preg_match('/\p{Lu}/u', $text) === 1;
        $lower = preg_match('/[\p{Ll}\p{Lt}]/u', $text) === 1;
        if (!$upper && !$lower) { $result['reason'] = 'no_cased_letters'; return $result; }
        $result['all_caps'] = $forcedAllCaps || ($upper && !$lower);
        if (!$result['all_caps']) { $result['status'] = CheckResult::PASS; $result['reason'] = 'not_full_uppercase'; return $result; }
        if ($knownOrdinaryHeading) { $result['status'] = CheckResult::FAIL; $result['reason'] = 'known_heading_full_uppercase'; return $result; }
        preg_match_all('/\p{L}+/u', $text, $words);
        // An internal connector indicates a phrase; a leading article alone may precede acronyms (A ONU).
        foreach (array_slice($words[0], 1) as $word) {
            if (isset(self::CONNECTORS[$language ?? '']) && preg_match('/^(?:' . self::CONNECTORS[$language] . ')$/iuD', $word)) {
                $result['status'] = CheckResult::FAIL; $result['reason'] = 'full_uppercase_phrase'; return $result;
            }
        }
        $result['reason'] = 'full_uppercase_acronym_review';
        return $result;
    }
}
