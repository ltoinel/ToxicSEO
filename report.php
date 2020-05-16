<?php

/**
 * report.php
 * 
 * Backlinks report
 * 
 * @autor    Ludovic Toinel
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */


require_once __DIR__ . '/bootstrap.php';

if (isset($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $type = "success";
}

switch ($type) {
    case "not-found":
        $backlinks = $toxicSeo->report(200,false);
        $title = "Backlinks not found";
        break;
    case "disavow":
        $backlinks = $toxicSeo->report(null,null,true);
        $title = "Backlinks not found";
        break;
    case "error-403":
        $backlinks = $toxicSeo->report(403);
        $title = "Error 403 Websites";
        break;
    case "error-404":
        $backlinks = $toxicSeo->report(404);
        $title = "Error 404 Websites";
        break;
    case "error-500":
        $backlinks = $toxicSeo->report(500);
        $title = "Error 500 Websites";
        break;        
    case "error-502":
        $backlinks = $toxicSeo->report(502);
        $title = "Error 502 Websites";
        break;
    case "error-503":
        $backlinks = $toxicSeo->report(502);
        $title = "Error 502 Websites";
        break;
    case "dead-websites":
        $backlinks = $toxicSeo->report(-1);
        $title = "Dead Websites";
        break;
    case "new":
        $backlinks = $toxicSeo->report(0);
        $title = "New Backlinks";
        break;
    default:
        $backlinks = $toxicSeo->report(200, true);
        $title = "Backlinks in success";
}

$template = $twig->load('report.twig');
echo $template->render(array('backlinks' =>  $backlinks , 'title' => $title));