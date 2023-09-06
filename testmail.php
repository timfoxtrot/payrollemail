<?php

//dependencies
include 'drtlib.php';
include 'config.php';

echo 'hello';

$email = "timfox@coufu.com";
$message = "THIS IS A TEST";
$file = "E:/GMHA/paystubs/paystub databases/PROJECT FOLDER/pdf/14435.pdf";

// Preparing attachment 
if(!empty($file) > 0){ 
    if(is_file($file)){ 
        $message .= "--{$mime_boundary}\n"; 
        $fp =    @fopen($file,"rb"); 
        $data =  @fread($fp,filesize($file)); 
 
        @fclose($fp); 
        $data = chunk_split(base64_encode($data)); 
        $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" .  
        "Content-Description: ".basename($file)."\n" . 
        "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" .  
        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n"; 
    } 
} 


$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
global $config_from_email;
$headers .= 'From: ' . $config_from_email;
$subject =  "TEST";

$mail = mail($email, $subject, $message, $headers);

?>