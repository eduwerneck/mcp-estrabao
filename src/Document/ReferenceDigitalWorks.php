<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Declared dataset/software layouts, never identification from a URL or a product name. */
final class ReferenceDigitalWorks
{
    public static function fields(string $content,int $offset): ?array
    {
        if (strlen($content)>8192) { return null; }
        $title='[^\[\]()]{1,1024}';
        $version='\((?:Version|Versão) [A-Za-z0-9][A-Za-z0-9._+–-]{0,63}\)';
        $label='Data set|Conjunto de dados|Computer software|Software';
        $source="[\\p{L}][\\p{L}\\p{N} &’'–-]{1,255}";
        if (!preg_match('~^(?<title>'.$title.')(?: (?<version>'.$version.'))? (?<description>\[(?<label>'.$label.')\])(?:\. (?<source>'.$source.'))?$~iuD',$content,$m,PREG_OFFSET_CAPTURE)) { return null; }
        $result=['type'=>preg_match('/^(?:Data set|Conjunto de dados)$/iuD',$m['label'][0])?'dataset_declared':'software_declared'];
        foreach (['title','version','description','source'] as $key) {
            $result[$key]=isset($m[$key]) && $m[$key][1]>=0 && $m[$key][0]!==''
                ? ['start'=>$offset+$m[$key][1],'end'=>$offset+$m[$key][1]+strlen($m[$key][0])]:null;
        }
        return $result;
    }
}
