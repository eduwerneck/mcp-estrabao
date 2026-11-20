<?php
/** Bounded identification only: never extracts Word text, converts or executes content. */
class EstrabaoLegacyDocSource
{
    const MODE = 'doc_manual_v1';
    const MAX_FILE = 10485760;

    function read($path)
    {
        $unknown = ['available'=>false,'reason'=>'legacy_doc_manual_only','sha256'=>null,'paragraphs'=>[]];
        try {
            if (!is_string($path) || preg_match('/^[a-z][a-z0-9+.-]*:/i',$path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'doc'
                || !is_file($path) || !is_readable($path)) { return $unknown; }
            $stream = @fopen($path,'rb'); if (!$stream) { return $unknown; }
            try { $bytes=stream_get_contents($stream,self::MAX_FILE+1); } finally { fclose($stream); }
            if (!is_string($bytes) || strlen($bytes)>self::MAX_FILE || strlen($bytes)<512) { return $unknown; }
            // Conservative subset: CFB version 3, header DIFAT only, no conversion fallback.
            if (substr($bytes,0,8)!==hex2bin('d0cf11e0a1b11ae1') || substr($bytes,8,16)!==str_repeat("\0",16)
                || $this->u16($bytes,26)!==3 || $this->u16($bytes,28)!==0xfffe
                || $this->u16($bytes,30)!==9 || $this->u16($bytes,32)!==6
                || $this->u32($bytes,40)!==0 || $this->u32($bytes,56)!==4096
                || $this->u32($bytes,68)!==0xfffffffe || $this->u32($bytes,72)!==0
                || strlen($bytes)%512!==0) { return $unknown; }
            $sectors=intdiv(strlen($bytes),512)-1; $fatCount=$this->u32($bytes,44);
            if ($fatCount<1 || $fatCount>109 || $fatCount>$sectors) { return $unknown; }
            $fat=[];$used=[];
            for($i=0;$i<109;$i++) {
                $id=$this->u32($bytes,76+4*$i);
                if($i>=$fatCount){if($id!==0xffffffff){return $unknown;}continue;}
                if($id>=$sectors || isset($used[$id])){return $unknown;}$used[$id]=true;
                $fat=array_merge($fat,array_values(unpack('V*',substr($bytes,($id+1)*512,512))));
            }
            foreach(array_keys($used)as$id){if(($fat[$id]??null)!==0xfffffffd){return $unknown;}}
            $directory=$this->chain($bytes,$fat,$this->u32($bytes,48),$sectors,$used,256);
            if($directory===null){return $unknown;}$entries=[];
            for($i=0;$i<strlen($directory);$i+=128){
                $entry=substr($directory,$i,128);$type=ord($entry[66]);
                if($type===0){$entries[]=null;continue;}
                $length=$this->u16($entry,64);
                if(!in_array($type,[1,2,5],true)||$length<2||$length>64||$length%2||substr($entry,$length-2,2)!=="\0\0"||$this->u32($entry,124)!==0){return $unknown;}
                $name=substr($entry,0,$length-2);$ascii=preg_match('/^(?:[\x20-\x7e]\x00)+$/D',$name)?str_replace("\0",'',$name):'';
                // Known active-content indicators are held; absence is not a malware certificate.
                if(in_array(strtolower($ascii),['vba','macros','_vba_project','objectpool'],true)){return $unknown;}
                $entries[]=['type'=>$type,'name'=>$ascii,'key'=>strtolower($ascii)?:bin2hex($name),'left'=>$this->u32($entry,68),'right'=>$this->u32($entry,72),'child'=>$this->u32($entry,76),'start'=>$this->u32($entry,116),'size'=>$this->u32($entry,120)];
            }
            if(($entries[0]['type']??null)!==5){return $unknown;}
            $queue=[[$entries[0]['child'],0,0]];$seen=[0=>true];$names=[];$word=null;$tables=[];
            while($queue){
                [$id,$parent,$depth]=array_pop($queue);if($id===0xffffffff){continue;}
                if($depth>32||isset($seen[$id])||!isset($entries[$id])||!in_array($entries[$id]['type'],[1,2],true)){return $unknown;}
                $seen[$id]=true;$e=$entries[$id];$key=$parent.':'.$e['key'];if(isset($names[$key])){return $unknown;}$names[$key]=true;
                $queue[]=[$e['left'],$parent,$depth+1];$queue[]=[$e['right'],$parent,$depth+1];
                if($e['type']===1){$queue[]=[$e['child'],$id,$depth+1];}elseif($e['child']!==0xffffffff){return $unknown;}
                if($parent===0&&$e['type']===2){if($e['name']==='WordDocument'){$word=$e;}if(in_array($e['name'],['0Table','1Table'],true)){$tables[$e['name']]=$e;}}
            }
            foreach($entries as$id=>$entry){if($entry!==null&&!isset($seen[$id])){return $unknown;}}
            // FIB must be in a regular stream. Mini-stream WordDocument and other CFB variants stay pending.
            if(!$word||$word['size']<4096||$word['size']>self::MAX_FILE){return $unknown;}
            $body=$this->chain($bytes,$fat,$word['start'],$sectors,$used,intdiv(self::MAX_FILE,512));
            if($body===null||strlen($body)!==((int)ceil($word['size']/512))*512||$this->u16($body,0)!==0xa5ec
                || !in_array($this->u16($body,2),[0x00c1,0x00d9,0x0101,0x010c,0x0112],true)) {return $unknown;}
            $flags=$this->u16($body,10);$table=$tables[($flags&0x0200)?'1Table':'0Table']??null;
            if(($flags&0x8100)!==0 || !$table || $table['size']<1 || $table['size']>self::MAX_FILE || $table['start']>=0xfffffffa){return $unknown;}
            // Identification is deliberately not text extraction or proof that every stream is readable.
            $unknown['sha256']=hash('sha256',$bytes);$unknown['manual_source_identified']=true;
            return $unknown;
        } catch(Throwable $error){return $unknown;}
    }

    private function u16($bytes,$offset){return unpack('v',substr($bytes,$offset,2))[1];}
    private function u32($bytes,$offset){return unpack('V',substr($bytes,$offset,4))[1];}
    private function chain($bytes,$fat,$id,$sectors,&$used,$limit)
    {
        $body='';$count=0;
        while($id!==0xfffffffe){
            if($id>=$sectors||isset($used[$id])||!isset($fat[$id])||++$count>$limit){return null;}
            $used[$id]=true;$body.=substr($bytes,($id+1)*512,512);$id=$fat[$id];
        }
        return $count?$body:null;
    }
}
