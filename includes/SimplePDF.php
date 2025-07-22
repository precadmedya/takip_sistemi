<?php
class SimplePDF{
    protected array $pages=[];
    protected string $content='';
    protected int $page=0;
    protected float $w=210;
    protected float $h=297;
    protected float $k=72/25.4;
    protected float $x=10;
    protected float $y=287;
    protected int $fontSize=12;
    protected ?array $imageInfo=null;
    protected string $imageData='';
    public function AddPage(){
        $this->page++;
        $this->content='';
        $this->x=10;
        $this->y=$this->h-10;
    }
    public function SetFont(string $family='Helvetica',string $style='',int $size=12){
        $this->fontSize=$size;
    }
    protected function escape(string $s):string{
        return str_replace(['\\','(',')'],['\\\\','\(','\)'],$s);
    }
    public function Cell(float $w,float $h,string $txt='',int $ln=0){
        $txt=$this->escape($txt);
        $this->content.=sprintf("BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET\n",
            $this->fontSize,$this->x*$this->k,($this->h-$this->y)*$this->k,$txt);
        $this->x+=$w;
        if($ln>0){
            $this->x=10;
            $this->y-=$h;
        }
    }
    public function Ln(float $h=5){
        $this->x=10;
        $this->y-=$h;
    }
    public function Image(string $file,float $x,float $y,float $w){
        $data=@file_get_contents($file);
        if(!$data) return;
        $info=@getimagesize($file);
        if(!$info) return;
        $h=$w*$info[1]/$info[0];
        $this->imageData=$data;
        $this->imageInfo=['w'=>$info[0],'h'=>$info[1],'type'=>strpos($info['mime'],'png')!==false?'FlateDecode':'DCTDecode'];
        $this->content.=sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /I1 Do Q\n",
            $w*$this->k,$h*$this->k,$x*$this->k,($this->h-$y-$h)*$this->k);
    }
    public function Output(string $name='document.pdf'){
        $objects=[];
        $objects[]="<< /Type /Catalog /Pages 2 0 R >>";
        $objects[]="<< /Type /Pages /Kids [4 0 R] /Count 1 >>";
        $objects[]="<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $resources="<< /Font << /F1 3 0 R >>";
        if($this->imageData) $resources.=" /XObject << /I1 6 0 R >>";
        $resources.=" >>";
        $objects[]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ".($this->w*$this->k)." ".($this->h*$this->k)."] /Contents 5 0 R /Resources $resources >>";
        $objects[]="<< /Length ".strlen($this->content)." >>\nstream\n".$this->content."endstream";
        if($this->imageData){
            $objects[]="<< /Type /XObject /Subtype /Image /Width {$this->imageInfo['w']} /Height {$this->imageInfo['h']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /{$this->imageInfo['type']} /Length ".strlen($this->imageData)." >>\nstream\n".$this->imageData."\nendstream";
        }
        $pdf="%PDF-1.4\n";
        $xref="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        $offset=0; $i=1;
        foreach($objects as $obj){
            $offset+=strlen($pdf);
            $xref.=sprintf("%010d 00000 n \n",$offset);
            $pdf.="$i 0 obj\n$obj\nendobj\n";
            $i++;
        }
        $start=strlen($pdf);
        $pdf.=$xref;
        $pdf.="trailer\n<< /Root 1 0 R /Size ".(count($objects)+1)." >>\nstartxref\n$start\n%%EOF";
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        echo $pdf;
    }
}
?>
