<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Contracts\CheckResult;

/** Adjacent declared keys only. No identity assignment, locale collation or text rewriting. */
final class ReferenceOrder
{
    public static function analyze(array $paragraphs,bool $complete,array $heads=[]): array
    {
        $result=['alphabetical_order_status'=>CheckResult::UNKNOWN,'chronological_order_status'=>CheckResult::UNKNOWN,
            'combined_order_status'=>CheckResult::UNKNOWN,'order_scope'=>'adjacent_declared_personal_keys_safe_prefix_and_numeric_years',
            'order_samples'=>[],'chronological_order_samples'=>[],'chronological_pairs_checked'=>0];
        if (!$complete || !$paragraphs || count($paragraphs)>ReferenceAnalysis::MAX_ENTRIES) { return $result; }
        $keys=[];$safe=[];
        foreach ($paragraphs as $position=>$p) {
            $e=ReferenceAnalysis::parseEntry($p['text']);
            if ($e===null && ($heads[$position]['kind']??null)==='personal' && isset($heads[$position]['author_key'])) {
                // Reuse a verified author prefix only. Full dates remain ineligible for chronology.
                $e=ReferenceAnalysis::parseEntry($heads[$position]['author_key'].' (2020). Placeholder.');
                if ($e!==null) { $e['year']=$heads[$position]['date_token']; }
            }
            if ($e===null) { return $result; }
            $list=ApaPersonalAuthors::inspect($e['author_key']);
            if ($list===null || $list['abbreviated'] || !$list['separators_valid'] || !$list['unabridged_list_length_valid']) { return $result; }
            $parts=[];
            foreach ($e['personal_keys'] as $person) {
                $fields=explode(',',$person,2);
                $parts[]=self::fold($fields[0])."\0".preg_replace('/\s+/u','',self::fold($fields[1]));
            }
            $signature=implode("\1",$parts);
            $numeric=preg_match('/^[0-9]{4}$/D',$e['year'])===1 && (int)$e['year']>0
                && !isset($e['original_year']) && empty($e['original_year_review']);
            $safe[$signature]=($safe[$signature]??true)&&$numeric;
            $keys[]=['surname'=>self::fold($e['authors'][0]),'signature'=>$signature,'year'=>$e['year'],'index'=>$p['index']];
        }
        $alpha=CheckResult::PASS;$chrono=CheckResult::PASS;
        for ($i=1;$i<count($keys);$i++) {
            $a=$keys[$i-1];$b=$keys[$i];$cmp=self::compare($a['surname'],$b['surname']);
            if ($cmp===null) { if ($alpha!==CheckResult::FAIL) { $alpha=CheckResult::UNKNOWN; } continue; }
            if ($cmp>0) {
                $alpha=CheckResult::FAIL;
                if (count($result['order_samples'])<16) { $result['order_samples'][]=[$a['index'],$b['index']]; }
            } elseif ($cmp===0) {
                if ($a['signature']!==$b['signature']) {
                    if ($alpha!==CheckResult::FAIL) { $alpha=CheckResult::UNKNOWN; }
                    if ($chrono!==CheckResult::FAIL) { $chrono=CheckResult::UNKNOWN; }
                } elseif (!$safe[$a['signature']] || $a['year']===$b['year']) {
                    if ($chrono!==CheckResult::FAIL) { $chrono=CheckResult::UNKNOWN; }
                } else {
                    $result['chronological_pairs_checked']++;
                    if ((int)$a['year']>(int)$b['year']) {
                        $chrono=CheckResult::FAIL;
                        if (count($result['chronological_order_samples'])<16) { $result['chronological_order_samples'][]=[$a['index'],$b['index']]; }
                    }
                }
            }
        }
        $result['alphabetical_order_status']=$alpha;$result['chronological_order_status']=$chrono;
        $result['combined_order_status']=in_array(CheckResult::FAIL,[$alpha,$chrono],true)?CheckResult::FAIL
            :(in_array(CheckResult::UNKNOWN,[$alpha,$chrono],true)?CheckResult::UNKNOWN:CheckResult::PASS);
        return $result;
    }

    private static function fold(string $text): string
    {
        return strtr(strtolower(preg_replace('/\s+/u',' ',trim($text))),array_combine(
            preg_split('//u','ÁÀÂÃÄÅÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇÑ',-1,PREG_SPLIT_NO_EMPTY),
            preg_split('//u','áàâãäåéèêëíìîïóòôõöúùûüçñ',-1,PREG_SPLIT_NO_EMPTY)));
    }

    private static function compare(string $left,string $right): ?int
    {
        $particles='/^(?:(?:da|das|de|del|della|di|do|dos|du|la|le|van|von|der|den|ten|ter) )+/u';
        if ($left!==$right && (substr($left,-strlen(' '.$right))===' '.$right || substr($right,-strlen(' '.$left))===' '.$left
            || preg_replace($particles,'',$left)===preg_replace($particles,'',$right))) { return null; }
        $a=preg_split('//u',$left,-1,PREG_SPLIT_NO_EMPTY);$b=preg_split('//u',$right,-1,PREG_SPLIT_NO_EMPTY);
        for ($i=0;$i<min(count($a),count($b));$i++) {
            if ($a[$i]===$b[$i]) { continue; }
            if (preg_match('/^[a-z]$/D',$a[$i]) && preg_match('/^[a-z]$/D',$b[$i])) { return strcmp($a[$i],$b[$i]); }
            return null; // No accent folding, particle removal or arbitrary punctuation order.
        }
        if (count($a)===count($b)) { return 0; }
        $tail=count($a)<count($b)?$b[count($a)]:$a[count($b)];
        return preg_match('/^[a-z]$/D',$tail)?(count($a)<count($b)?-1:1):null;
    }
}
