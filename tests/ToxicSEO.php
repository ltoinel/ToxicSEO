<?php

require_once __DIR__ . '/../bootstrap.php';

$toxicSeo = new ToxicSEO($settings);
$toxicSeo->getAlexaRank("geeek.org");
