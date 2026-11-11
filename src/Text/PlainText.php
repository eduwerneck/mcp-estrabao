<?php
declare(strict_types=1);
namespace Estrabao\Text;

/** Normalização local, sem DOM, XML, rede ou execução de conteúdo. */
final class PlainText
{
    public static function normalize(string $value): ?string
    {
        if (preg_match('//u', $value) !== 1 || preg_match('/\p{M}/u', $value)
            || preg_match('~<\s*(?:script|style|iframe|object)\b~iu', $value)) {
            return null;
        }
        // Não juntar palavras nem marcadores separados por blocos HTML.
        $value = preg_replace('~<\s*/?\s*(?:p|div|br|li|h[1-6]|tr)\b[^>]*>~iu', "\n", $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    public static function hasContent(string $value): bool
    {
        return preg_match('/[^\s\p{Z}\p{Cf}]/u', $value) === 1;
    }
}
