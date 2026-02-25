<?php

declare(strict_types=1);

if (!defined('APP_TESTING')) {
    define('APP_TESTING', true);
}

date_default_timezone_set('UTC');

$_SERVER = (isset($_SERVER) && is_array($_SERVER)) ? $_SERVER : [];
$_POST = (isset($_POST) && is_array($_POST)) ? $_POST : [];
$_GET = (isset($_GET) && is_array($_GET)) ? $_GET : [];
$_SESSION = (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : [];

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Composer autoload not found. Run "composer install".');
}

require_once $autoload;
