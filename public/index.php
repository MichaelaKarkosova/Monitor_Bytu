<?php
require __DIR__ . '/../vendor/autoload.php';
use App\Bootstrap;
use Tracy\Debugger;

Debugger::enable();

$container = Bootstrap::boot();
// spustíme webovou aplikaci.
$container->getWebApp()->run();