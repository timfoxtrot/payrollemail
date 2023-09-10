<?php

//Tim Dominguez (timfox@coufu.com) 8/18/2023

//dependencies
include 'functions.php';

switch($_GET[action]){
    default:         users_only();    final_check();  break;
    case "send";     users_only();    mass_email();   break;
    case "oneemail"; users_only();    one_email();    break;
    case "deptemail";users_only();    dept_email();   break;
}

//Email template
function email_content($lastname, $firstname){
    $content = '
    Hafa Adai, '.$firstname.' '.$lastname.'!<br><br>
    
    Your pay stub is attached.<br><br>  
    
    Contact us at 671-648-7946/7947/7948/7951/7991 if you have any inquiries.<br><br>
    
    Thank you,<br>
    GMHA Payroll Office';

    return $content;
}

//logging success/failed emails
function email_log($reportdate, $empno, $deptno, $email, $status, $userid){

    $insertemaillog[reportdate] = $reportdate;
    $insertemaillog[datetime]   = time();
    $insertemaillog[empno]      = $empno;
    $insertemaillog[deptno]     = $deptno;
    $insertemaillog[email]      = $email;
    $insertemaillog[status]     = $status;
    $insertemaillog[sender]     = $userid;
    
    $db = new MyDB;
    $db->insertarray("email_log", $insertemaillog);
}

//last check before sending mass emails. estimated 30 minutes for 1000 employees
function final_check(){
    //display top header
    payroll_top(NULL, 400);

    $table = new Ctable;
    $table->pushth( "ADMIN LOGIN" );
    $table->push( '<font size="5">ARE YOU SURE</font>');
    echo "<form action=\"email.php?action=send\" method=\"post\">";
    $table->show();
    echo "<br>";
    echo "<input type=\"submit\" value=\"YES\" /> ";
    echo "<input type=\"reset\" value=\"Reset\" /> ";
    echo "</form>";

}

//send individual email
function one_email(){

    $db = new MyDB;
    $db->query( "SELECT * FROM employee_tbl where STATUS = 1 and empno = '$_GET[empno]'"); 
    $row = $db->getrow();
    
    //Email Template
        //Recipient
        $to = $row[email]; 

        // Sender 
        $from = 'payroll@gmha.org'; 
        $fromName = 'Payroll'; 
    
        // Email subject 
        $ppedate =  getppedate();
        $subject = 'GMHA Paystub for PPE ';  
        $subject .= $ppedate;
    
        // Attachment file 
        $file = getfolderpath();
        $file .= $row[empno];
        $file .= ".pdf";
    
        // Email body content 
        $htmlContent = email_content($row[lastname], $row[firstname]);

        // Header for sender info 
        $headers = "From: $fromName"." <".$from.">"; 
    
        // Boundary  
        $semi_rand = md5(time());  
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
        
        // Headers for attachment  
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
        
        // Multipart boundary  
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
        "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";  
        
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
        $message .= "--{$mime_boundary}--"; 
        $returnpath = "-f" . $from; 
    
    // Send email 
    if ($fp == FALSE){
        email_log($ppedate, $row[empno], $row[deptno], $row[email], "NOPAYCHECK", $_COOKIE[userid]);
    }else{
        $mail = @mail($to, $subject, $message, $headers, $returnpath);
        if($mail)  email_log($ppedate, $row[empno], $row[deptno], $row[email], "SUCCESS", $_COOKIE[userid]);
        if(!$mail) email_log($ppedate, $row[empno], $row[deptno], $row[email], "FAILED", $_COOKIE[userid]);
    }
    
    // Email sending status 
    echo $mail?"<h3>$row[firstname] $row[lastname] - SUCCESS</h3>":"<h3>$row[firstname] $row[lastname] - FAILED</h3>";

    echo 'Click <a href="index.php">here</a> to go back';
}

//send department email
function dept_email(){
    ?>
    
    <!-- Progress bar holder -->
    <div id="progress" style="width:500px;border:1px solid #ccc;"></div>
    <!-- Progress information -->
    <div id="information" style="width"></div>
    
    <?php

    $db = new MyDB;
    $db->query("SELECT * FROM employee_tbl where STATUS = 1 and deptno = '$_GET[deptno]' ORDER by empno ASC"); 

    //loop variables
    $total = $db->getnumrows();
    $i = 1;

    $successcount = 0;
    $failedcount  = 0;
    $skipcount    = 0;

    while($row = $db->getrow()){
        
        $percent = intval($i/$total * 100)."%";

        echo '<script language="javascript">
        document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#008000;\">&nbsp;</div>";
        document.getElementById("information").innerHTML="'.$i.' checkstub(s) processed out of '.$total.'.";
        </script>';

        echo str_repeat(' ',1024*64);

        flush();

        sleep(1);

        // Recipient
        $to = $row[email]; 

        // Sender 
        $from     = 'payroll@gmha.org'; 
        $fromName = 'Payroll'; 

        // Email subject 
        $ppedate =  getppedate();
        $subject = 'GMHA Paystub for PPE ';  
        $subject .= $ppedate;

        // Attachment file 
        $file  = getfolderpath();
        $file .= $row[empno];
        $file .= ".pdf";

        // Email body content 
        $htmlContent = email_content($row[lastname], $row[firstname]);
    
        // Header for sender info 
        $headers = "From: $fromName"." <".$from.">"; 

        // Boundary  
        $semi_rand = md5(time());  
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
        
        // Headers for attachment  
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
        
        // Multipart boundary  
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
        "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";  
        
        // Preparing attachment 
        if(!empty($file) > 0){ 
            if(is_file($file)){ 
                $message .= "--{$mime_boundary}\n"; 
                $fp       =  @fopen($file,"rb"); 
                $data     =  @fread($fp,filesize($file)); 
        
                @fclose($fp); 
                $data     = chunk_split(base64_encode($data)); 
                $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" .  
                "Content-Description: ".basename($file)."\n" . 
                "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" .  
                "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n"; 
            } 
        } 
        $message   .= "--{$mime_boundary}--"; 
        $returnpath = "-f" . $from; 
        
        /*******************
         SEND EMAIL BLOCK
        ********************/

        // Check if employee already received email
        $db2 = new MyDB;
        $db2->query("SELECT * FROM email_log where empno = '$row[empno]' and status = 'SUCCESS' and reportdate = '$ppedate'");
        $row2 = $db2->getrow();

        // Skip employee
        if($row[empno] == $row2[empno]){
            $skipcount++;
            $i++;
            continue;
        }
        
        // Check if no attachment/file, else continue with email attempt
        if ($fp == FALSE){
            email_log($ppedate, $row[empno], $row[deptno], $row[email], "NOPAYCHECK", $_COOKIE[userid]);
            $failedcount++;
        }else{
            $mail = @mail($to, $subject, $message, $headers, $returnpath);
            if($mail){
                email_log($ppedate, $row[empno], $row[deptno], $row[email], "SUCCESS", $_COOKIE[userid]);
                $successcount++;
            }
            if(!$mail){
                email_log($ppedate, $row[empno], $row[deptno], $row[email], "FAILED", $_COOKIE[userid]);
                $failedcount++;
            }
        }
        $i++;
        $fp = 0;
    }

    //echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';

    //after action report
    echo '<br><br>';
    echo "Successful Emails: <b>$successcount</b><br><br>";
    echo "Failed Emails: <b>$failedcount</b><br><br>";
    echo "Skipped Emails: <b>$skipcount</b><br><br>";
    echo '<form action="report.php" method="post">';
    echo '<input type="hidden" name="reportdate" value="'.$ppedate.'">';
    echo '<input type="submit" value="View Report"/><br><br>';
    echo 'Click <a href="index.php">here</a> to go back';
    
}

//send everyone email
function mass_email(){
    $db = new MyDB;
    $db->query('SELECT * FROM employee_tbl where STATUS = 1 ORDER by deptno ASC, empno ASC'); 

    ?>
    <!-- Progress bar holder -->
    <div id="progress" style="width:500px;border:1px solid #ccc;"></div>
    <!-- Progress information -->
    <div id="information" style="width"></div>
    <?php

    //loop variables
    $total = $db->getnumrows();
    $i = 1;

    $successcount = 0;
    $failedcount  = 0;
    $skipcount    = 0;

    while($row = $db->getrow()){

        $percent = intval($i/$total * 100)."%";

        echo '<script language="javascript">
        document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#008000;\">&nbsp;</div>";
        document.getElementById("information").innerHTML="'.$i.' checkstub(s) processed out of '.$total.'.";
        </script>';

        echo str_repeat(' ',1024*64);

        flush();

        sleep(1);
        
        //Recipient
        $to = $row[email]; 

        // Sender 
        $from     = 'payroll@gmha.org'; 
        $fromName = 'Payroll'; 

        // Email subject 
        $ppedate =  getppedate();
        $subject = 'GMHA Paystub for PPE ';  
        $subject .= $ppedate;

        // Attachment file 
        $file  = getfolderpath();
        $file .= $row[empno];
        $file .= ".pdf";

        // Email body content 
        $htmlContent = email_content($row[lastname], $row[firstname]);
    
        // Header for sender info 
        $headers = "From: $fromName"." <".$from.">"; 

        // Boundary  
        $semi_rand = md5(time());  
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
        
        // Headers for attachment  
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 
        
        // Multipart boundary  
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
        "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";  
        
        // Preparing attachment 
        if(!empty($file) > 0){ 
            if(is_file($file)){ 
                $message .= "--{$mime_boundary}\n"; 
                $fp       =  @fopen($file,"rb"); 
                $data     =  @fread($fp,filesize($file)); 
        
                @fclose($fp); 
                $data     = chunk_split(base64_encode($data)); 
                $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" .  
                "Content-Description: ".basename($file)."\n" . 
                "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" .  
                "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n"; 
            } 
        } 
        $message   .= "--{$mime_boundary}--"; 
        $returnpath = "-f" . $from; 
        
        /*******************
         SEND EMAIL BLOCK
        ********************/

        // Check if employee already received email
        $db2 = new MyDB;
        $db2->query("SELECT * FROM email_log where empno = '$row[empno]' and status = 'SUCCESS' and reportdate = '$ppedate'");
        $row2 = $db2->getrow();

        // Skip employee
        if($row[empno] == $row2[empno]){
            $skipcount++;
            $i++;
            continue;
        }
        
        // Check if no attachment/file, else continue with email attempt
        if ($fp == FALSE){
            email_log($ppedate, $row[empno], $row[deptno], $row[email], "NOPAYCHECK", $_COOKIE[userid]);
            $failedcount++;
        }else{
            $mail = @mail($to, $subject, $message, $headers, $returnpath);
            if($mail){
                email_log($ppedate, $row[empno], $row[deptno], $row[email], "SUCCESS", $_COOKIE[userid]);
                $successcount++;
            }
            if(!$mail){
                email_log($ppedate, $row[empno], $row[deptno], $row[email], "FAILED", $_COOKIE[userid]);
                $failedcount++;
            }
        }
        $i++;
        $fp = 0;
    }

    //after action report
    echo '<br><br>';
    echo "Successful Emails: <b>$successcount</b><br><br>";
    echo "Failed Emails: <b>$failedcount</b><br><br>";
    echo "Skipped Emails: <b>$skipcount</b><br><br>";
    echo '<form action="report.php" method="post">';
    echo '<input type="hidden" name="reportdate" value="'.$ppedate.'">';
    echo '<input type="submit" value="View Report"/><br><br>';
    echo 'Click <a href="index.php">here</a> to go back';
}

?>