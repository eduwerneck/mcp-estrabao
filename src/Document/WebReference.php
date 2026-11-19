<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Lexical web-reference checks only. Never resolves a host or fetches a URL. */
final class WebReference
{
    private const MONTHS=['january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12,
        'janeiro'=>1,'fevereiro'=>2,'março'=>3,'abril'=>4,'maio'=>5,'junho'=>6,'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12];

    public static function inspect(string $base,string $date,string $url): array
    {
        $result=['base'=>$base,'publication_calendar'=>self::publicationDate($date),'retrieval_seen'=>false,'retrieval_calendar'=>null,'url_syntax'=>self::urlSyntax($url)];
        $patterns=[
            '~\s+Retrieved ([\p{L}]+) ([0-9]{1,2}), ([0-9]{4}), from\s*$~iuD',
            '~\s+Recuperado em ([0-9]{1,2}) de ([\p{L}]+) de ([0-9]{4}), de\s*$~iuD',
        ];
        foreach ($patterns as $i=>$pattern) {
            if (preg_match($pattern,$base,$m,PREG_OFFSET_CAPTURE)) {
                $result['base']=rtrim(substr($base,0,$m[0][1]));$result['retrieval_seen']=true;
                $result['retrieval_calendar']=self::calendar($m[$i===0?1:2][0],$m[$i===0?2:1][0],$m[3][0]);break;
            }
        }
        return $result;
    }

    public static function publicationDate(string $date): ?bool
    {
        if (preg_match('/^([0-9]{4}), ([\p{L}]+)(?: ([0-9]{1,2}))?$/iuD',$date,$m)) { return self::calendar($m[2],$m[3]??'1',$m[1]); }
        if (preg_match('/^([0-9]{4}), ([0-9]{1,2}) de ([\p{L}]+)$/iuD',$date,$m)) { return self::calendar($m[3],$m[2],$m[1]); }
        return null;
    }

    /** Valid explicit day, month and year; month-only dates never acquire an invented day. */
    public static function publicationParts(string $date): ?array
    {
        if(strlen($date)>128){return null;}
        if(preg_match('/^([0-9]{4}), ([\\p{L}]+) ([0-9]{1,2})$/iuD',$date,$m)){$year=$m[1];$month=$m[2];$day=$m[3];}
        elseif(preg_match('/^([0-9]{4}), ([0-9]{1,2}) de ([\\p{L}]+)$/iuD',$date,$m)){$year=$m[1];$month=$m[3];$day=$m[2];}
        else{return null;}
        if(self::calendar($month,$day,$year)!==true){return null;}
        $key=strtr(strtolower($month),['Ç'=>'ç']);
        return [(int)$year,self::MONTHS[$key],(int)$day];
    }

    private static function calendar(string $month,string $day,string $year): ?bool
    {
        $key=strtr(strtolower($month),['Ç'=>'ç']);
        if (!isset(self::MONTHS[$key])) { return null; }
        return checkdate(self::MONTHS[$key],(int)$day,(int)$year);
    }

    private static function urlSyntax(string $url): ?bool
    {
        if (strlen($url)>2048) { return null; }
        $parts=parse_url($url);
        if ($parts===false || (!isset($parts['host']) || $parts['host']==='')) { return false; }
        // Non-ASCII/percent-encoded hosts and unusual forms are reviewable, not rejected by an ASCII filter.
        $host=$parts['host'];
        if (preg_match('/[\\x00-\\x20\\x7f<>\\\\]/u',$host)) { return false; }
        if (preg_match('/[^A-Za-z0-9.\-:\[\]]/u',$host)) { return null; }
        return true;
    }
}
