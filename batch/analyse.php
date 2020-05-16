<?php

/**
 * analyse.php
 * 
 * This batch analyze all the new links imported on the portal.
 * 
 * @autor    Ludovic Toinel
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 */

require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
$toxicSeo = new ToxicSEO($settings);
$toxicSeo->analyze();
