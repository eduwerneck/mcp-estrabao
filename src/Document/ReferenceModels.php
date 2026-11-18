<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Bounded layout helpers, never bibliographic identity or a source-type authority. */
final class ReferenceModels
{
    public static function editorPattern(): string
    {
        return '(?:\p{Lu}\.\s*){1,4}' . ApaPersonalAuthors::surnamePattern();
    }

    public static function editors(string $text): ?array
    {
        if (strlen($text)>2048 || preg_match('//u',$text)!==1 || preg_match('/\p{M}/u',$text)) { return null; }
        $pattern='~'.self::editorPattern().'~u';
        $count=preg_match_all($pattern,$text,$names);
        if (!$count || $count>20) { return null; }
        $shape=preg_replace($pattern,'@',$text);
        if (!preg_match('/^@\s*(?:(?:,\s*&|,|;|&|e|and|y)\s*@\s*)*$/uD',$shape)) { return null; }
        $valid=$count===1 || ($count===2 ? preg_match('/^@\s*&\s*@\s*$/D',$shape)===1 : preg_match('/^@\s*(?:,\s*@\s*)+,\s*&\s*@\s*$/D',$shape)===1);
        return ['count'=>$count,'separators_valid'=>$valid];
    }

    public static function ordinal(string $number,string $suffix): bool
    {
        $n=(int)$number;$lastTwo=$n%100;
        $expected=$lastTwo>=11&&$lastTwo<=13?'th':([1=>'st',2=>'nd',3=>'rd'][$n%10]??'th');
        return $suffix===$expected;
    }

    public static function pageRange(string $locator): ?bool
    {
        if (!preg_match('/^([0-9]{1,9})[-–]([0-9]{1,9})$/uD',$locator,$m)) { return null; }
        return (int)$m[1]<=(int)$m[2];
    }
}
