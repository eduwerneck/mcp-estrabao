<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Normalize only two explicitly nontextual Word features in private DOM copies. */
final class DocxCompatibility
{
    const W14 = 'http://schemas.microsoft.com/office/word/2010/wordml';
    public static function normalize(array $parts): array
    {
        $normalized=[]; $counts=['cached_page_breaks'=>0,'ligature_properties'=>0];
        $values=['none','standard','contextual','historical','discretionary','standardContextual',
            'standardHistorical','contextualHistorical','standardContextualHistorical','standardDiscretionary',
            'contextualDiscretionary','standardContextualDiscretionary','historicalDiscretionary',
            'standardHistoricalDiscretionary','contextualHistoricalDiscretionary','all'];
        foreach($parts as$name=>$part){
            $probe=new \DOMXPath($part);$probe->registerNamespace('w',DocxReader::W);$probe->registerNamespace('w14',self::W14);
            if($probe->query('//w:r/w:lastRenderedPageBreak|//w:rPr/w14:ligatures')->length===0){$normalized[$name]=$part;continue;}
            $copy=clone $part;$x=new \DOMXPath($copy);$x->registerNamespace('w',DocxReader::W);$x->registerNamespace('w14',self::W14);
            foreach(iterator_to_array($x->query('//w:r/w:lastRenderedPageBreak'))as$node){
                if($node->hasChildNodes()||$node->attributes->length!==0){continue;}
                $node->parentNode->removeChild($node);$counts['cached_page_breaks']++;
            }
            foreach(iterator_to_array($x->query('//w:rPr/w14:ligatures'))as$node){
                if($node->hasChildNodes()||$node->attributes->length!==1
                    ||!$node->hasAttributeNS(self::W14,'val')||!in_array($node->getAttributeNS(self::W14,'val'),$values,true)){continue;}
                $node->parentNode->removeChild($node);$counts['ligature_properties']++;
            }
            $normalized[$name]=$copy;
        }
        return ['parts'=>$normalized,'counts'=>$counts];
    }
}
