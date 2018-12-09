<?php
$pharFile = dirname(__FILE__).'/perspective.phar';
$binFile  = dirname(__FILE__).'/perspective';
if (file_exists($pharFile) === true) {
    unlink($pharFile);
}

if (file_exists($binFile) === true) {
    unlink($binFile);
}

$p = new Phar($pharFile);

$p->startBuffering();
$defaultStub = $p->createDefaultStub('perspective.php');
$p->buildFromDirectory('./');
$stub = "#!/usr/bin/env php \n".$defaultStub;
$p->setStub($stub);
$p->stopBuffering();

$contents = "#!/usr/bin/env php \n".file_get_contents($binFile.'.php');
file_put_contents($binFile, $contents);