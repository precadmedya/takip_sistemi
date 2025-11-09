<?php
class PHPMailer {
    public $Host;
    public $Port = 25;
    public $SMTPSecure = 'ssl';
    public $Username;
    public $Password;
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $CharSet = 'UTF-8';
    public $Timeout = 10;
    public $HeloDomain = 'localhost';
    private $to = [];
    public $ErrorInfo = '';
    private $lastResponse = '';

    public function addAddress($addr){
        $this->to[] = $addr;
    }
    private function sendCmd($fp,$cmd,$expect=true){
        if($cmd!==null){
            fwrite($fp,$cmd."\r\n");
        }
        $resp='';
        while(($line=fgets($fp,512))!==false){
            $resp.=$line;
            if(strlen($line)>3 && $line[3]!== '-') break;
        }
        $this->lastResponse = trim($resp);
        if($expect){
            $code=(int)substr($resp,0,3);
            if($code>=400){
                $this->ErrorInfo=trim($resp);
                return false;
            }
        }
        return true;
    }
    public function send(){
        $secure = strtolower((string)$this->SMTPSecure);
        $host = $this->Host;
        if($secure === 'ssl'){
            $host = 'ssl://'.$host;
        }
        $errstr='';
        $errno=0;
        $fp = @fsockopen($host,$this->Port,$errno,$errstr,$this->Timeout);
        if(!$fp){
            $this->ErrorInfo = $errstr ? "Bağlantı kurulamadı: $errstr ($errno)" : 'Bağlantı kurulamadı';
            return false;
        }
        stream_set_timeout($fp,$this->Timeout);
        if(!$this->sendCmd($fp,null)) return false; // server greeting
        if(!$this->sendCmd($fp, 'EHLO '.$this->HeloDomain)) return false;
        if($secure==='tls'){
            if(!$this->sendCmd($fp,'STARTTLS')) return false;
            if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)){
                $this->ErrorInfo = 'TLS bağlantısı kurulamadı';
                return false;
            }
            if(!$this->sendCmd($fp,'EHLO '.$this->HeloDomain)) return false;
        }
        if($this->Username){
            if(!$this->sendCmd($fp,'AUTH LOGIN')) return false;
            if(!$this->sendCmd($fp,base64_encode($this->Username))) return false;
            if(!$this->sendCmd($fp,base64_encode($this->Password))) return false;
        }
        if(!$this->sendCmd($fp,'MAIL FROM:<'.$this->From.'>')) return false;
        foreach($this->to as $t){
            if(!$this->sendCmd($fp,'RCPT TO:<'.$t.'>')) return false;
        }
        if(!$this->sendCmd($fp,'DATA')) return false;
        $headers = "From: {$this->FromName} <{$this->From}>\r\n".
                   "MIME-Version: 1.0\r\n".
                   "Content-Type: text/html; charset={$this->CharSet}\r\n";
        $msg = $headers.
               "Subject: {$this->Subject}\r\n".
               "To: ".implode(',', $this->to)."\r\n\r\n".
               $this->Body;
        fwrite($fp,$msg."\r\n.\r\n");
        if(!$this->sendCmd($fp,null)) return false;
        $this->sendCmd($fp,'QUIT',false);
        fclose($fp);
        return true;
    }
}
?>
