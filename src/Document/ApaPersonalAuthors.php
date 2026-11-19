<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Bounded name grammar; citation matching adds literal-name and ambiguity guards. Never identity verification. */
final class ApaPersonalAuthors
{
    const MAX_AUTHORS = 64;
    public static function surnamePattern(): string
    {
        // Capitalized surname components and a closed set of particles. No suffixes or institutions inferred.
        $word = '\p{Lu}[\p{L}\p{Pd}\x{2019}\x{0027}]{0,63}';
        $particle = '(?:da|das|de|del|della|di|do|dos|du|la|le|van|von|der|den|ten|ter)';
        $surname = '(?:' . $particle . '\s+){0,3}' . $word . '(?:\s+(?:(?:' . $particle . ')\s+){0,3}' . $word . '){0,3}';
        return '(?:' . $surname . '|' . ReferenceAnalysis::SURNAME . ')';
    }

    public static function inspect(string $prefix): ?array
    {
        if (strlen($prefix) > 2048 || preg_match('//u', $prefix) !== 1 || preg_match('/\p{M}/u', $prefix)) { return null; }
        $person = '(' . self::surnamePattern() . '),\s*(?:\p{Lu}\.\s*){1,4}';
        preg_match_all('~' . $person . '~u', $prefix, $names);
        $count = count($names[1]);
        if (!$count || $count > self::MAX_AUTHORS) { return null; }
        $shape = preg_replace('~' . $person . '~u', '@', $prefix);
        // Ellipses represent omitted authors, never proof of the actual author count.
        if (preg_match('/^(?:@\s*,\s*)+(?:\x{2026}|\.\s*\.\s*\.)\s*,?\s*(&\s*)?@\s*$/uD', $shape, $ellipsis)) {
            return ['authors'=>$names[1], 'count'=>$count, 'abbreviated'=>true,
                'abbreviation_structure_valid'=>$count === 20 && empty($ellipsis[1]),
                'separators_valid'=>null, 'unabridged_list_length_valid'=>null];
        }
        // Every byte must belong to a recognized personal name or separator.
        if (!preg_match('/^@\s*(?:(?:,\s*&|,|;|&|e|and|y)\s*@\s*)*$/uD', $shape)) { return null; }
        $canonical = $count === 1 || preg_match('/^@\s*(?:,\s*@\s*)*,\s*&\s*@\s*$/D', $shape) === 1;
        return ['authors'=>$names[1], 'count'=>$count, 'abbreviated'=>false,
            'separators_valid'=>$canonical, 'unabridged_list_length_valid'=>$count <= 20];
    }
}
