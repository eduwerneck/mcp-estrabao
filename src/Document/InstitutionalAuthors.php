<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Literal institutional candidates and explicitly declared aliases, never an authority lookup. */
final class InstitutionalAuthors
{
    public static function name(string $text): bool
    {
        if (strlen($text)>512 || preg_match('//u',$text)!==1 || preg_match('/\p{M}/u',$text)) { return false; }
        return preg_match("/^(?:Universidade|Universidad|University|Instituto|Institute|Organização|Organización|Organization|Ministério|Ministerio|Ministry|World Health Organization|National Institute) [\\p{L}][\\p{L}\\s\\p{Pd}'’&]{1,220}$/iuD", $text)===1
            || preg_match('/^World Health Organization$/iuD',$text)===1;
    }

    public static function entry(string $text): ?array
    {
        if (strlen($text)>8192 || preg_match_all('/\(' . ReferenceAnalysis::DATE_TOKEN . '\)\.\s+/iu',$text)!==1 || !preg_match('/^(.{2,512}?)\.\s+\((' . ReferenceAnalysis::DATE_TOKEN . ')\)\.\s+(\S.*)$/iuD',$text,$m) || !self::name($m[1])) { return null; }
        return ['authors'=>[$m[1]],'year'=>ReferenceAnalysis::dateKey($m[2]),'kind'=>'institutional'];
    }

    public static function aliases(array $paragraphs, array $entries): array
    {
        $aliases=[];
        foreach ($entries as $entry) {
            if (($entry['kind']??null)!=='institutional') { continue; }
            $full=$entry['authors'][0];$quoted=preg_quote($full,'~');
            foreach ($paragraphs as $p) {
                // Only explicit name/acronym introductions; never derive acronyms from initials.
                preg_match_all('~(?:^|\()\s*(?:(?:Segundo|Conforme)\s+)?'.$quoted.'\s*(?:\[([A-Z][A-Z0-9]{1,15})\]|\(([A-Z][A-Z0-9]{1,15}),\s*[0-9]{4}[a-z]?\))~u',$p['text'],$matches,PREG_SET_ORDER);
                foreach ($matches as $m) { $alias=$m[1]!==''?$m[1]:$m[2];$aliases[$alias][$full]=true; }
            }
        }
        return $aliases;
    }

    public static function citation(string $text, array $entries, array $aliases): ?array
    {
        if (strlen($text)>512) { return null; }
        if (preg_match('/^(.+?)\s*\[([A-Z][A-Z0-9]{1,15})\]$/uD',$text,$m) && self::name($m[1])) {
            return ['authors'=>[$m[1]],'form'=>'institutional','institutional_alias'=>true];
        }
        foreach ($entries as $entry) {
            if (($entry['kind']??null)==='institutional' && preg_match('/^'.preg_quote($entry['authors'][0],'/').'$/iuD',$text)) {
                return ['authors'=>[$entry['authors'][0]],'form'=>'institutional','institutional_alias'=>false];
            }
        }
        if (isset($aliases[$text])) {
            $names=array_keys($aliases[$text]);$collision=count($names)!==1;
            foreach ($entries as $entry) {
                if (($entry['kind']??null)!=='institutional' && preg_match('/^'.preg_quote($text,'/').'$/iuD',$entry['authors'][0])) { $collision=true; }
            }
            return ['authors'=>[$collision?$text:$names[0]],'form'=>'institutional','institutional_alias'=>true,'institutional_ambiguous'=>$collision];
        }
        return null;
    }

    public static function abntLead(string $text): bool
    {
        return preg_match('/^(.{2,512}?)\.\s+\S/u',$text,$m)===1 && self::name($m[1]);
    }
}
