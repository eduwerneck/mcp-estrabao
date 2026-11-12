<?php
declare(strict_types=1);
namespace Estrabao\Text;

/** Two explicit technical tokenizations; not an official editorial definition of word. */
final class WordInterval
{
    public static function counts(string $text): array
    {
        if (preg_match('//u', $text) !== 1 || preg_match('/\p{M}/u', $text)) {
            throw new \RuntimeException('Unsupported text normalization.');
        }
        $lower = 0;
        $tokens = preg_split('/[\s\p{Z}]+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) { throw new \RuntimeException('Tokenization unavailable.'); }
        foreach ($tokens as $token) {
            if (preg_match('/[\p{L}\p{N}]/u', $token)) { $lower++; }
        }
        $upper = preg_match_all('/[\p{L}\p{N}]+/u', $text);
        if ($upper === false) { throw new \RuntimeException('Tokenization unavailable.'); }
        return [$lower, max($lower, $upper)];
    }
}
