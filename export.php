<?php

$filename = 'email_log_export.csv';
$export_data = unserialize($_POST['export_data']);

// Create File
$file = fopen($filename,"w");

$headers = ["ReportDate", "Time email sent", "EMPNO", "DEPTNO", "Email", "Status", "Sender"];
fputcsv($file,$headers);

foreach ($export_data as $line){
    fputcsv($file,$line);
}

fclose($file);

// Download
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=".$filename);
header("Content-Type: application/csv; "); 

readfile($filename);

// Deleting File
unlink($filename);

exit();

?>