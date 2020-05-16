<?php

/**
 * upload.php
 * 
 * Google disavow file generator
 * 
 * @autor    Ludovic Toinel
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

require_once __DIR__ . '/bootstrap.php';

set_time_limit(0);
$toxicSeo = new ToxicSEO($settings);
$error = "";
$success = "";

if (isset($_FILES['csvFile'])){
        
    $uploaddir = dirname(__FILE__).'/upload/';
    $uploadfile = $uploaddir . basename($_FILES['csvFile']['name']);
    
    //print_r($_FILES);

    // For the file upload
    if (move_uploaded_file($_FILES['csvFile']['tmp_name'], $uploadfile)) {

        // We check if it's a CSV file
        $file_parts = pathinfo($uploadfile);

        if ($file_parts['extension'] == "csv"){
            ini_set('auto_detect_line_endings',TRUE);
            $handle = fopen($uploadfile,'r');
            $count = 0;

            // We parse the file as an CSV file
            while ( ($data = fgetcsv($handle) ) !== FALSE ) {
                if (strpos("http", $data[0]) == 0){
                    $toxicSeo->saveOrUpdate($data[0]);
                    $count++;
                } 
            }
            
            $success = "$count backlinks imported !";

            ini_set('auto_detect_line_endings',FALSE);
        } else {
            $error = "This is not a CSV file";
        }

        // We remove the file
        unlink($uploadfile);
    } else {
        $error ="Could not move the temp file to the working directory : " . $uploadfile;
    }


}

$template = $twig->load('upload.twig');
echo $template->render(array("error"=>$error, "success"=> $success));