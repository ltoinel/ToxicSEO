<?php

/**
 * bootstrap.php
 * 
 * The Boostrap PHP file for this project
 * 
 * @autor    Ludovic Toinel
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

// Load our autoloader
require_once __DIR__.'/vendor/autoload.php';

// ToxiSEO Loading
require_once 'lib/ToxicSEO.class.php';
require_once'conf/config.php';

// We instanciate a new instance
$toxicSeo = new ToxicSEO($settings);

// Specify our Twig templates location
$loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/templates/');

// Instantiate our Twig
$twig = new \Twig\Environment($loader, [
    //'cache' => __DIR__.'/cache',
    'cache' => false,
    'debug' => true,
    'auto_reload' => true
]);

