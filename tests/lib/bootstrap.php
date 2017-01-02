<?php

/**
 * Load composer dependencies, create SQLite database & connection, fire the migration.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

$dbManager = new Manager();

$dbManager->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
]);

$dbManager->setEventDispatcher(new Dispatcher());
$dbManager->setAsGlobal();
$dbManager->bootEloquent();

$dbManager->connection()->setFetchMode(\PDO::FETCH_NUM);

require_once 'migration.php';
