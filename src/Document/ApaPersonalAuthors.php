<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Closed personal-name grammar for diagnostics only; never citation matching. */
final class ApaPersonalAuthors
{
    const MAX_AUTHORS = 64;
    public static function inspect(string $prefix): ?array
    {
        if (strlen($prefix) > 2048 || preg_match('//u', $prefix) !== 1 || preg_match('/\p{M}/u', $prefix)) { return null; }
        $person = '(' . ReferenceAnalysis::SURNAME . '),\s*(?:\p{Lu}\.\s*){1,4}';
        preg_match_all('~' . $person . '~u', $prefix, $names);
        $count = count($names[1]);
        if (!$count || $count > self::MAX_AUTHORS) { return null; }
        $shape = preg_replace('~' . $person . '~u', '@', $prefix);
        // Every byte must belong to a recognized personal name or separator.
        if (!preg_match('/^@\s*(?:(?:,\s*&|,|;|&|e|and|y)\s*@\s*)*$/uD', $shape)) { return null; }
        $canonical = $count === 1 || preg_match('/^@\s*(?:,\s*@\s*)*,\s*&\s*@\s*$/D', $shape) === 1;
        return ['authors'=>$names[1], 'count'=>$count, 'separators_valid'=>$canonical,
            'unabridged_list_length_valid'=>$count <= 20];
    }
}
