<?php

include 'functions.php';

sql_connect();

// $dirPath contain path to directory whose files are to be listed 
$dirPath = 'C:\xampp\htdocs\sandbox';

$files = scandir($dirPath);  

foreach ($files as $file) {
    $filePath = $dirPath . '/' . $file;
    if (is_file($filePath)) {
        
        //remove w2.pdf
        $file = substr($file,0,-6);

        //split names into array
        $file = explode(" ",$file);

        //output names
        foreach ($file as $filename){
            echo $filename . " ";
        }
        echo "<br>";
    }
}

?>