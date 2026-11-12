<?php
declare(strict_types=1);
namespace Estrabao\Document;

/** Verified direct-body Word text; excluded tables/pictures never become complete text. */
final class BodyTextProfile
{
    const MAX_PARAGRAPHS = 4096;
    const MAX_DRAWINGS = 256;
    private const A='http://schemas.openxmlformats.org/drawingml/2006/main';
    private const WP='http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
    private const PIC='http://schemas.openxmlformats.org/drawingml/2006/picture';
    private const A14='http://schemas.microsoft.com/office/drawing/2010/main';
    private const R='http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    public static function inspect(array $parts,array $entries,array $notes): ?array
    {
        $x=self::xpath($parts['word/document.xml']);
        if(!$notes['coverage_complete']||!self::validTextLocations($x)
            ||$x->query('/w:document/w:body//w:p')->length>self::MAX_PARAGRAPHS
            ||$x->query('/w:document/w:body//w:drawing')->length>self::MAX_DRAWINGS
            ||$x->query('/w:document/w:body/*[not(self::w:p or self::w:tbl or self::w:sectPr)]')->length
            ||$x->query('/w:document/w:body//w:altChunk|/w:document/w:body//w:sdt|/w:document/w:body//w:ins|/w:document/w:body//w:del|/w:document/w:body//w:moveFrom|/w:document/w:body//w:moveTo|/w:document/w:body//w:fldChar|/w:document/w:body//w:fldSimple|/w:document/w:body//w:instrText|/w:document/w:body//w:txbxContent|/w:document/w:body//w:pict|/w:document/w:body//w:object|/w:document/w:body//w:sym|/w:document/w:body//w:endnoteReference')->length
            ||$x->query('//*[namespace-uri()="http://schemas.openxmlformats.org/markup-compatibility/2006"]')->length
            ||$x->query('/w:document/w:body//*[namespace-uri()!="'.DocxReader::W.'" and not(ancestor::w:drawing)]')->length){return null;}
        foreach($parts as$part){$px=self::xpath($part);if($px->query('//w:vanish|//w:webHidden')->length){return null;}}
        $tables=$x->query('/w:document/w:body/w:tbl');
        if($tables->length>VisualInventory::MAX_TABLES){return null;}
        foreach($tables as$table){if(!VisualInventory::simpleTable($x,$table)){return null;}}
        // Validate all Word paragraph content, including text in the excluded tables.
        foreach($x->query('/w:document/w:body//w:p')as$paragraph){
            if($x->query('.//*[not(ancestor::w:drawing or ancestor::w:pPr or ancestor::w:rPr) and not(self::w:r or self::w:t or self::w:tab or self::w:br or self::w:cr or self::w:pPr or self::w:rPr or self::w:hyperlink or self::w:bookmarkStart or self::w:bookmarkEnd or self::w:proofErr or self::w:drawing or self::w:footnoteReference)]',$paragraph)->length){return null;}
        }
        foreach($x->query('/w:document/w:body//w:drawing')as$drawing){if(!self::picture($x,$drawing,$parts,$entries)){return null;}}
        $nestedSections=$x->query('/w:document/w:body//w:sectPr')->length
            !==$x->query('/w:document/w:body/w:sectPr|/w:document/w:body/w:p/w:pPr/w:sectPr')->length;
        if($nestedSections){return null;}
        return ['tables_excluded'=>$tables->length,'drawings_excluded'=>$x->query('/w:document/w:body//w:drawing')->length,
            'table_paragraphs_excluded'=>$x->query('/w:document/w:body/w:tbl//w:p')->length,
            'footnote_content_excluded'=>$notes['note_count']>0,'image_text_read'=>false];
    }

    public static function validTextLocations(\DOMXPath $x,?\DOMNode $context=null): bool
    {
        $prefix=$context===null?'/w:document/w:body//':'.//';
        return $x->query($prefix.'w:t[not(parent::w:r) or ancestor::w:pPr or ancestor::w:rPr or ancestor::w:tblPr or ancestor::w:trPr or ancestor::w:tcPr or ancestor::w:sectPr or ancestor::w:drawing or ancestor::w:pict or ancestor::w:object]',$context)->length===0;
    }

    private static function picture(\DOMXPath $x,\DOMElement $drawing,array $parts,array $entries): bool
    {
        if($drawing->parentNode->namespaceURI!==DocxReader::W||$drawing->parentNode->localName!=='r'
            ||$x->query('ancestor::w:pPr|ancestor::w:rPr|ancestor::w:tbl',$drawing)->length){return false;}
        $x->registerNamespace('wp',self::WP);$x->registerNamespace('a',self::A);$x->registerNamespace('pic',self::PIC);$x->registerNamespace('r',self::R);
        if($x->query('./wp:inline|./wp:anchor',$drawing)->length!==1
            ||$x->query('.//pic:pic',$drawing)->length!==1
            ||$x->query('.//a:graphicData',$drawing)->length!==1
            ||$x->query('.//a:graphicData[@uri="'.self::PIC.'"]',$drawing)->length!==1
            ||$x->query('.//a:blip',$drawing)->length!==1
            ||$x->query('.//a:blip/@r:embed',$drawing)->length!==1){return false;}
        $allowed=[
            self::WP=>['inline','anchor','simplePos','positionH','positionV','posOffset','align','extent','effectExtent','wrapNone','wrapSquare','wrapTopAndBottom','wrapTight','wrapThrough','wrapPolygon','start','lineTo','docPr','cNvGraphicFramePr'],
            self::PIC=>['pic','nvPicPr','cNvPr','cNvPicPr','blipFill','spPr'],
            self::A=>['graphicFrameLocks','graphic','graphicData','blip','stretch','fillRect','xfrm','off','ext','prstGeom','avLst','extLst','srcRect','ln','noFill','alphaModFix'],
            self::A14=>['imgProps','imgLayer','imgEffect','saturation','shadowObscured'],
        ];
        $embedded=0;
        foreach($x->query('.//*',$drawing)as$node){
            if(!isset($allowed[$node->namespaceURI])||!in_array($node->localName,$allowed[$node->namespaceURI],true)){return false;}
            foreach($node->childNodes as$child){
                if(($child instanceof \DOMText)&&trim($child->textContent)!==''
                    &&!in_array($node->localName,['posOffset','align'],true)){return false;}
            }
            foreach($node->attributes as$attribute){
                if($attribute->namespaceURI===self::R){
                    if($attribute->localName!=='embed'||!self::image($parts,$entries,$attribute->value,$node->namespaceURI===self::A14&&$node->localName==='imgLayer')){return false;}
                    $embedded++;
                }
            }
        }
        return $embedded>0;
    }

    private static function image(array $parts,array $entries,string $id,bool $effectLayer=false): bool
    {
        if(!isset($parts['word/_rels/document.xml.rels'])){return false;}
        $rx=new \DOMXPath($parts['word/_rels/document.xml.rels']);$rx->registerNamespace('r','http://schemas.openxmlformats.org/package/2006/relationships');
        $links=[];foreach($rx->query('/r:Relationships/r:Relationship')as$link){if($link->getAttribute('Id')===$id){$links[]=$link;}}
        if(count($links)!==1){return false;}$link=$links[0];
        if(!in_array($link->getAttribute('Type'),$effectLayer?[self::R.'/image','http://schemas.microsoft.com/office/2007/relationships/hdphoto']:[self::R.'/image'],true)||!in_array($link->getAttribute('TargetMode'),['','Internal'],true)){return false;}
        $target=$link->getAttribute('Target');
        if($target===''||preg_match('~[:\\\\%?#\x00-\x20]|(?:^|/)\.\.(?:/|$)~',$target)){return false;}
        $name=$target[0]==='/'?substr($target,1):'word/'.$target;
        if(!isset($entries[$name])){return false;}
        $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $mime=['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','tif'=>'image/tiff','tiff'=>'image/tiff','gif'=>'image/gif','bmp'=>'image/bmp','wdp'=>'image/vnd.ms-photo'][$extension]??null;
        if(!$mime||($extension==='wdp'&&!$effectLayer)){return false;}
        $types=new \DOMXPath($parts['[Content_Types].xml']);$types->registerNamespace('c','http://schemas.openxmlformats.org/package/2006/content-types');
        $overrides=[];$defaults=[];
        foreach($types->query('/c:Types/c:Override')as$n){if($n->getAttribute('PartName')==='/'.$name){$overrides[]=$n->getAttribute('ContentType');}}
        foreach($types->query('/c:Types/c:Default')as$n){if(strtolower($n->getAttribute('Extension'))===$extension){$defaults[]=$n->getAttribute('ContentType');}}
        return count($overrides)<=1&&count($defaults)<=1&&($overrides?:$defaults)===[$mime];
    }

    private static function xpath(\DOMDocument $document): \DOMXPath
    {$x=new \DOMXPath($document);$x->registerNamespace('w',DocxReader::W);return $x;}
}
