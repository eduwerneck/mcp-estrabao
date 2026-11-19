<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Contracts\CheckResult;

/** Complete same-author/year cohorts only; technical title keys never leave this class. */
final class ReferenceTitleOrder
{
    public static function analyze(array $keys): array
    {
        $r=['title_order_status'=>CheckResult::UNKNOWN,'year_suffix_order_status'=>CheckResult::UNKNOWN,
            'title_order_samples'=>[],'year_suffix_order_samples'=>[],'title_cohorts_checked'=>0,'title_pairs_checked'=>0];
        $groups=[];$blocked=[];$ambiguous=[];
        foreach($keys as $k){
            if(!empty($k['original_year_review'])){$ambiguous[$k['signature']]=true;}
            if(isset($k['original_year'])){$blocked[$k['signature']][(string)$k['original_year']]=true;}
        }
        foreach($keys as $k){if(preg_match('/^[0-9]{4}/D',$k['year'])){$groups[$k['signature']."\2".substr($k['year'],0,4)][]=$k;}}
        $title=CheckResult::PASS;$suffix=CheckResult::PASS;$uncertain=false;
        foreach($groups as $group){
            $n=count($group);if($n<2){continue;}
            $valid=$n<=26 && empty($ambiguous[$group[0]['signature']]) && empty($blocked[$group[0]['signature']][substr($group[0]['year'],0,4)]);$titles=[];$ranks=array_fill(0,$n,0);
            foreach($group as $k){
                $t=$k['order_title']??null;
                if(empty($k['title_year_eligible']) || !is_string($t) || strlen($t)>512
                    || !preg_match('/^\p{L}+(?: \p{L}+)*$/uD',$t)
                    || preg_match('/^(?:a|an|the|o|os|as|um|uma|uns|umas|el|la|los|las|un|una|unos|unas)(?: |$)/iuD',$t)){$valid=false;}
                $titles[]=$t;
            }
            if(!$valid){$uncertain=true;continue;}
            for($i=0;$i<$n;$i++){for($j=$i+1;$j<$n;$j++){
                $cmp=self::compare($titles[$i],$titles[$j]);
                if($cmp===null || $cmp===0){$valid=false;break 2;}
                if($cmp>0){$ranks[$i]++;}else{$ranks[$j]++;}
            }}
            if(!$valid){$uncertain=true;continue;}
            $r['title_cohorts_checked']++;$r['title_pairs_checked']+=intdiv($n*($n-1),2);
            for($i=0;$i<$n;$i++){
                if($i>0 && $ranks[$i-1]>$ranks[$i]){
                    $title=CheckResult::FAIL;
                    if(count($r['title_order_samples'])<16){$r['title_order_samples'][]=[$group[$i-1]['index'],$group[$i]['index']];}
                }
                if(substr($group[$i]['year'],4)!==chr(97+$ranks[$i])){
                    $suffix=CheckResult::FAIL;
                    if(count($r['year_suffix_order_samples'])<16){$r['year_suffix_order_samples'][]=$group[$i]['index'];}
                }
            }
        }
        $r['title_order_status']=$title===CheckResult::FAIL?$title:($uncertain || !$r['title_cohorts_checked']?CheckResult::UNKNOWN:CheckResult::PASS);
        $r['year_suffix_order_status']=$suffix===CheckResult::FAIL?$suffix:($uncertain || !$r['title_cohorts_checked']?CheckResult::UNKNOWN:CheckResult::PASS);
        return $r;
    }

    private static function compare(string $left,string $right): ?int
    {
        $map=array_combine(preg_split('//u','ÁÀÂÃÄÅÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇÑ',-1,PREG_SPLIT_NO_EMPTY),preg_split('//u','áàâãäåéèêëíìîïóòôõöúùûüçñ',-1,PREG_SPLIT_NO_EMPTY));
        $a=preg_split('//u',strtr(strtolower($left),$map),-1,PREG_SPLIT_NO_EMPTY);$b=preg_split('//u',strtr(strtolower($right),$map),-1,PREG_SPLIT_NO_EMPTY);
        for($i=0;$i<min(count($a),count($b));$i++){
            if($a[$i]===$b[$i]){continue;}
            if(preg_match('/^[a-z]$/D',$a[$i]) && preg_match('/^[a-z]$/D',$b[$i])){return strcmp($a[$i],$b[$i]);}
            return null;
        }
        return count($a)<=>count($b);
    }
}
