<?php

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

$dbManager = new Manager();

$dbManager->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:'
]);

$dbManager->setEventDispatcher(new Dispatcher());
$dbManager->setAsGlobal();
$dbManager->bootEloquent();

require_once 'migration.php';
