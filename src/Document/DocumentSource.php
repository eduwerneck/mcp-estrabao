<?php
declare(strict_types=1);
namespace Estrabao\Document;

final class DocumentSource
{
    public static function mainText(?array $document): ?array
    {
        if($document===null){return null;}
        if($document['available']??false){return $document+['full_document_text_available'=>true,'source_profile'=>'full_main_text'];}
        if($document['main_text']['available']??false){
            return $document['main_text']+['full_document_text_available'=>false,'source_profile'=>'verified_main_word_text'];
        }
        return $document+['full_document_text_available'=>false,'source_profile'=>'unavailable'];
    }
}
