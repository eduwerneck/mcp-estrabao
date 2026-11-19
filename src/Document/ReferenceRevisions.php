<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Explicit translation/republication declarations, never edition or work identity inference. */
final class ReferenceRevisions
{
    private const ORIGINAL='(?:Original work published|Obra original publicada em|Trabalho original publicado em)';

    public static function original(string $content,int $offset): array
    {
        $result=['base'=>$content,'has_marker'=>false,'year'=>null,'statement'=>null];
        if (strlen($content)>8192) { return $result; }
        $count=preg_match_all('~\('.self::ORIGINAL.'\b~iu',$content);
        $result['has_marker']=preg_match('~\b'.self::ORIGINAL.'\b~iu',$content)===1;
        if ($count!==1 || !preg_match('~\s+(?<statement>\('.self::ORIGINAL.' (?<year>[0-9]{4})\))\.?(?<url>\s+https?://[^\s<>]+)?\s*$~iuD',$content,$m,PREG_OFFSET_CAPTURE)) { return $result; }
        $result['year']=$m['year'][0];
        $result['statement']=['start'=>$offset+$m['statement'][1],'end'=>$offset+$m['statement'][1]+strlen($m['statement'][0])];
        $result['base']=rtrim(substr($content,0,$m[0][1]).(isset($m['url']) && $m['url'][1]>=0?$m['url'][0]:''));
        return $result;
    }

    public static function translated(string $content,int $offset): ?array
    {
        if (strlen($content)>8192) { return null; }
        $person=ReferenceModels::editorPattern();
        $names=$person.'(?:\s*(?:,\s*&|,|;|&|e|and|y)\s*'.$person.'){0,19}';
        $source="[\\p{L}][\\p{L}\\p{N} &’'–-]{1,255}";
        $pattern='~^(?<title>[^\[\]()]{1,1024}) \((?<names>'.$names.'), (?<role>Trans\.|Trad\.|Trads\.)(?:; (?<edition>(?<number>[0-9]{1,9})(?<suffix>st|nd|rd|th) ed\.))?\)\. (?<source>'.$source.')$~uD';
        if (!preg_match($pattern,$content,$m,PREG_OFFSET_CAPTURE)) { return null; }
        $list=ReferenceModels::editors($m['names'][0]);
        if ($list===null) { return null; }
        $result=['type'=>'translated_work_layout_candidate','english_separators'=>$m['role'][0]==='Trans.'?$list['separators_valid']:null];
        foreach (['title','source','edition'] as $key) {
            $result[$key]=isset($m[$key]) && $m[$key][1]>=0 && $m[$key][0]!==''
                ? ['start'=>$offset+$m[$key][1],'end'=>$offset+$m[$key][1]+strlen($m[$key][0])]:null;
        }
        $result['translator']=['start'=>$offset+$m['names'][1],'end'=>$offset+$m['role'][1]+strlen($m['role'][0])];
        $result['edition_ordinal']=$result['edition']!==null?ReferenceModels::ordinal($m['number'][0],$m['suffix'][0]):null;
        return $result;
    }
}
