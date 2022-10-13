<?php
require __DIR__ . '/../vendor/autoload.php';
use App\Bootstrap;

$container = Bootstrap::boot();
// spustíme webovou aplikaci.
$container->getWebApp()->run();