<?php
class PHPMailer {
    public $Host;
    public $Port = 25;
    public $SMTPSecure;
    public $Username;
    public $Password;
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $CharSet = 'UTF-8';
    private $to = [];
    public $ErrorInfo = '';

    public function addAddress($addr){
        $this->to[] = $addr;
    }
    private function sendCmd($fp,$cmd){
        fwrite($fp,$cmd."\r\n");
        return fgets($fp,512);
    }
    public function send(){
        $host = ($this->SMTPSecure==='ssl'?'ssl://':'').$this->Host;
        $fp = fsockopen($host,$this->Port,$errno,$errstr,10);
        if(!$fp){
            $this->ErrorInfo = $errstr ?: 'Bağlantı kurulamadı';
            return false;
        }
        $this->sendCmd($fp, 'EHLO localhost');
        if($this->SMTPSecure==='tls'){
            $this->sendCmd($fp,'STARTTLS');
            stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCmd($fp,'EHLO localhost');
        }
        $this->sendCmd($fp,'AUTH LOGIN');
        $this->sendCmd($fp,base64_encode($this->Username));
        $this->sendCmd($fp,base64_encode($this->Password));
        $this->sendCmd($fp,'MAIL FROM:<'.$this->From.'>');
        foreach($this->to as $t){
            $this->sendCmd($fp,'RCPT TO:<'.$t.'>');
        }
        $this->sendCmd($fp,'DATA');
        $headers = "From: {$this->FromName} <{$this->From}>\r\n".
                   "MIME-Version: 1.0\r\n".
                   "Content-Type: text/html; charset={$this->CharSet}\r\n";
        $msg = $headers.
               "Subject: {$this->Subject}\r\n".
               "To: ".implode(',', $this->to)."\r\n\r\n".
               $this->Body;
        fwrite($fp,$msg."\r\n.\r\n");
        fgets($fp,512);
        $this->sendCmd($fp,'QUIT');
        fclose($fp);
        return true;
    }
}
?>
