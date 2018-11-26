<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->addPsr4('Coded\\TestHelper\\', __DIR__);

require __DIR__ . '/Coded/TestHelper/TestCase/ControllerTestCase.php';
require __DIR__ . '/Coded/TestHelper/TestCase/QueryTestCase.php';
$loader->addPsr4('Coded\\Test\\', __DIR__);
defined("DATABASE_CONFIG_PATH") or define("DATABASE_CONFIG_PATH", __DIR__);
defined("DATABASE_DUMP_PATH") or define("DATABASE_DUMP_PATH", __DIR__);