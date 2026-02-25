<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected static TestDatabase $testDatabase;
    protected FixtureLoader $fixtures;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$testDatabase = TestDatabase::shared();
        self::$testDatabase->resetSchema();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$testDatabase->cleanupMutableTables();
        $this->fixtures = new FixtureLoader(self::$testDatabase);
    }

    protected function db(): TestDatabase
    {
        return self::$testDatabase;
    }

    protected function conn(): \mysqli
    {
        return self::$testDatabase->connection();
    }
}
