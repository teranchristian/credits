<?php
require_once __DIR__.'/../vendor/autoload.php';
define("BASE_PATH", realpath(__DIR__.'/../'));

use Credits\Finder;

$videoDirectory = BASE_PATH."/videos";
$iter = new DirectoryIterator($videoDirectory);
foreach ($iter as $file) { 
    if ($file->isDot()) {
        continue;
    }
    $t = new Finder($file);
    $f = $t->findCredits();
    $startTime = $t->findCreditsStartTime($f);
    echo $file->getBasename();
    var_dump(gmdate("H:i:s", $startTime));
    echo "</br>";
}
