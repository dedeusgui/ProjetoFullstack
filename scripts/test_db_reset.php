<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Composer autoload not found. Run composer install first.\n");
    exit(1);
}

require_once $autoload;
require_once __DIR__ . '/../tests/Support/SqlDumpImporter.php';
require_once __DIR__ . '/../tests/Support/TestDatabase.php';

$db = \Tests\Support\TestDatabase::shared();
$db->resetSchema();

fwrite(STDOUT, "Test database reset completed: " . $db->getDatabaseName() . PHP_EOL);
