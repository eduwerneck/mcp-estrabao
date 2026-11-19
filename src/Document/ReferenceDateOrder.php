<?php
declare(strict_types=1);
namespace Estrabao\Document;
use Estrabao\Contracts\CheckResult;

/** Exact declared calendar dates only; never fills a missing month/day or selects an edition. */
final class ReferenceDateOrder
{
    public static function emptyResult(): array
    {
        return ['full_date_order_status'=>CheckResult::UNKNOWN,'full_date_order_samples'=>[],
            'full_date_pairs_checked'=>0,'full_date_suffix_review_required'=>false,
            'full_date_suffix_policy'=>'manual_review_for_precise_dates','full_date_review'=>[]];
    }

    public static function analyze(array $keys): array
    {
        $r=self::emptyResult();$blocked=[];$groups=[];$seen=false;$uncertain=false;$status=CheckResult::PASS;
        foreach($keys as $k){
            if(isset($k['original_year']) || !empty($k['original_year_review'])){$blocked[$k['signature']]=true;}
            if(preg_match('/^[0-9]{4}/D',$k['year'])){$groups[$k['signature']."\2".substr($k['year'],0,4)][]=$k;}
            if(strpos($k['year'],',')!==false){$seen=true;}
        }
        foreach($groups as $g){
            if(count($g)>1 && count(array_filter($g,function($k){return strpos($k['year'],',')!==false;}))){$r['full_date_suffix_review_required']=true;}
        }
        for($i=1;$i<count($keys);$i++){
            $a=$keys[$i-1];$b=$keys[$i];
            if($a['signature']!==$b['signature']){continue;}
            $left=$a['publication_parts']??null;$right=$b['publication_parts']??null;
            if($left===null && $right===null){continue;}
            if($left===null || $right===null || isset($blocked[$a['signature']])){$uncertain=true;continue;}
            $cmp=$left<=>$right;
            if($cmp===0){$uncertain=true;continue;}
            $r['full_date_pairs_checked']++;
            if($cmp>0){$status=CheckResult::FAIL;if(count($r['full_date_order_samples'])<16){$r['full_date_order_samples'][]=[$a['index'],$b['index']];}}
        }
        $r['full_date_order_status']=$status===CheckResult::FAIL?$status:($uncertain || !$r['full_date_pairs_checked']?CheckResult::UNKNOWN:CheckResult::PASS);
        if($seen){$r['full_date_review'][]='Confira na fonte as datas declaradas e se mês e dia se aplicam ao tipo de obra e à edição consultada. Não complete datas ausentes.';}
        if($r['full_date_suffix_review_required']){$r['full_date_review'][]='Confira os sufixos e sua correspondência nas citações para obras da mesma autoria e ano com datas precisas; a comparação de calendário não atribui letras nem identifica a edição.';}
        return $r;
    }
}
