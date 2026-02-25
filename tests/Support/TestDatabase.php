<?php

declare(strict_types=1);

namespace Tests\Support;

final class TestDatabase
{
    private static ?self $shared = null;

    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $databaseName;
    private ?\mysqli $connection = null;

    private function __construct()
    {
        $this->host = (string) (getenv('DB_HOST') ?: 'localhost');
        $this->port = (int) (getenv('DB_PORT') ?: 3306);
        $this->user = (string) (getenv('DB_USER') ?: 'root');
        $this->pass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';
        $this->databaseName = (string) (getenv('TEST_DB_NAME') ?: getenv('DB_NAME') ?: 'doitly_test');
    }

    public static function shared(): self
    {
        if (self::$shared === null) {
            self::$shared = new self();
        }

        return self::$shared;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function resetSchema(): void
    {
        if ($this->connection instanceof \mysqli) {
            $this->connection->close();
            $this->connection = null;
        }

        $serverConn = $this->connectToServer();
        $dbName = $this->databaseName;

        $serverConn->query('DROP DATABASE IF EXISTS `' . $serverConn->real_escape_string($dbName) . '`');
        $serverConn->query('CREATE DATABASE `' . $serverConn->real_escape_string($dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $serverConn->select_db($dbName);

        $sqlPath = dirname(__DIR__, 2) . '/sql/doitly_unified.sql';
        SqlDumpImporter::import($serverConn, $sqlPath, $dbName);

        $this->connection?->close();
        $this->connection = null;
    }

    public function connection(): \mysqli
    {
        if ($this->connection instanceof \mysqli) {
            return $this->connection;
        }

        $this->connection = $this->connectToDatabase();
        return $this->connection;
    }

    public function cleanupMutableTables(): void
    {
        $conn = $this->connection();
        $conn->query('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'habit_completions',
            'habits',
            'user_recommendations',
            'user_achievements',
            'sessions',
            'user_settings',
            'users',
        ];

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $conn->query('TRUNCATE TABLE `' . $table . '`');
            }
        }

        $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function query(string $sql): \mysqli_result|bool
    {
        return $this->connection()->query($sql);
    }

    public function execute(string $sql): void
    {
        if (!$this->connection()->query($sql)) {
            throw new \RuntimeException('SQL execute failed: ' . $this->connection()->error);
        }
    }

    public function prepare(string $sql): \mysqli_stmt
    {
        $stmt = $this->connection()->prepare($sql);
        if (!$stmt instanceof \mysqli_stmt) {
            throw new \RuntimeException('Prepare failed: ' . $this->connection()->error);
        }

        return $stmt;
    }

    public function fetchOne(string $sql): ?array
    {
        $result = $this->connection()->query($sql);
        if (!$result instanceof \mysqli_result) {
            throw new \RuntimeException('Query failed: ' . $this->connection()->error);
        }

        $row = $result->fetch_assoc();
        $result->free();

        return $row ?: null;
    }

    public function fetchAll(string $sql): array
    {
        $result = $this->connection()->query($sql);
        if (!$result instanceof \mysqli_result) {
            throw new \RuntimeException('Query failed: ' . $this->connection()->error);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $rows;
    }

    private function tableExists(string $table): bool
    {
        $escaped = $this->connection()->real_escape_string($table);
        $result = $this->connection()->query("SHOW TABLES LIKE '{$escaped}'");
        if (!$result instanceof \mysqli_result) {
            return false;
        }

        $exists = $result->num_rows > 0;
        $result->free();

        return $exists;
    }

    private function connectToServer(): \mysqli
    {
        $conn = new \mysqli($this->host, $this->user, $this->pass, '', $this->port);
        if ($conn->connect_error) {
            throw new \RuntimeException('Test DB server connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');

        return $conn;
    }

    private function connectToDatabase(): \mysqli
    {
        $conn = new \mysqli($this->host, $this->user, $this->pass, $this->databaseName, $this->port);
        if ($conn->connect_error) {
            throw new \RuntimeException('Test DB connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');

        return $conn;
    }
}
