<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Explicit declared layouts only; no inference of degree, publication or publisher. */
final class ReferenceLongWorks
{
    public static function fields(string $content, int $offset): ?array
    {
        if (strlen($content)>8192) { return null; }
        $degree="(?:Doctoral dissertation|Master['’]s thesis|Tese de doutorado|Dissertação de mestrado)";
        $unpublished="(?:Unpublished (?:doctoral dissertation|master['’]s thesis)|(?:Tese de doutorado|Dissertação de mestrado) não publicada)";
        $institution="[\\p{L}][\\p{L}\\p{N} &’'–-]{1,255}";
        $title='[^\[\]()]{1,1024}';
        $source="[\\p{L}][\\p{L}\\p{N} &’'–-]{1,255}";
        // Published/in-repository and unpublished layouts are kept distinct.
        if (preg_match('~^('.$title.') (\[(?:'.$degree.', '.$institution.'|'.$unpublished.')\])\. ('.$source.')$~iuD',$content,$m,PREG_OFFSET_CAPTURE)) {
            $type=preg_match('~^\['.$unpublished.'\]$~iuD',$m[2][0])?'unpublished_thesis_declared':'repository_thesis_declared';
            return self::result($type,$m,$offset);
        }
        // Numbered reports only. An absent report number is never an incompatibility.
        $number='(?:Report No\.|Relatório n[º°.]|Relatório No\.) [A-Za-z0-9][A-Za-z0-9/–-]{0,63}';
        if (preg_match('~^('.$title.') (\('.$number.'\))\. ('.$source.')$~iuD',$content,$m,PREG_OFFSET_CAPTURE)) {
            return self::result('numbered_report_declared',$m,$offset);
        }
        return null;
    }

    private static function result(string $type,array $matches,int $offset): array
    {
        $r=['type'=>$type];
        foreach (['title'=>1,'description'=>2,'source'=>3] as $key=>$i) {
            $r[$key]=['start'=>$offset+$matches[$i][1],'end'=>$offset+$matches[$i][1]+strlen($matches[$i][0])];
        }
        return $r;
    }
}
