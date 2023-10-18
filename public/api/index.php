<?php
require __DIR__ . '/../../vendor/autoload.php';
use App\Bootstrap;

$container = Bootstrap::boot();
$container->getAPI()->run();