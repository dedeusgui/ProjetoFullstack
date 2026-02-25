<?php

declare(strict_types=1);

namespace Tests\Support;

final class SqlDumpImporter
{
    public static function import(\mysqli $conn, string $path, string $databaseName): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException('SQL dump not found: ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open SQL dump: ' . $path);
        }

        try {
            $delimiter = ';';
            $buffer = '';

            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);

                if ($trimmedLine === '') {
                    continue;
                }

                if (str_starts_with($trimmedLine, '--')) {
                    continue;
                }

                if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmedLine, $matches) === 1) {
                    $delimiter = $matches[1];
                    continue;
                }

                if (str_starts_with($trimmedLine, '/*') && str_ends_with($trimmedLine, '*/;')) {
                    continue;
                }

                $buffer .= $line;

                if (!self::endsWithDelimiter($buffer, $delimiter)) {
                    continue;
                }

                $statement = self::stripDelimiter($buffer, $delimiter);
                $buffer = '';

                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }

                $normalized = self::normalizeStatement($statement, $databaseName);
                if ($normalized === '') {
                    continue;
                }

                if (!$conn->query($normalized)) {
                    throw new \RuntimeException(
                        'Failed SQL import statement: ' . $conn->error . PHP_EOL . self::preview($normalized)
                    );
                }
            }

            if (trim($buffer) !== '') {
                $normalized = self::normalizeStatement(trim($buffer), $databaseName);
                if ($normalized !== '' && !$conn->query($normalized)) {
                    throw new \RuntimeException(
                        'Failed trailing SQL import statement: ' . $conn->error . PHP_EOL . self::preview($normalized)
                    );
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private static function endsWithDelimiter(string $buffer, string $delimiter): bool
    {
        $trimmed = rtrim($buffer);
        if ($trimmed === '') {
            return false;
        }

        return str_ends_with($trimmed, $delimiter);
    }

    private static function stripDelimiter(string $buffer, string $delimiter): string
    {
        $trimmed = rtrim($buffer);
        if (str_ends_with($trimmed, $delimiter)) {
            return substr($trimmed, 0, -strlen($delimiter));
        }

        return $trimmed;
    }

    private static function normalizeStatement(string $statement, string $databaseName): string
    {
        $statement = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', $statement) ?? $statement;

        if (preg_match('/^CREATE\s+DATABASE\s+/i', $statement) === 1) {
            return 'CREATE DATABASE IF NOT EXISTS `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }

        if (preg_match('/^USE\s+`[^`]+`$/i', $statement) === 1) {
            return 'USE `' . $databaseName . '`';
        }

        if (preg_match('/^USE\s+[a-zA-Z0-9_]+$/i', $statement) === 1) {
            return 'USE `' . $databaseName . '`';
        }

        return $statement;
    }

    private static function preview(string $statement): string
    {
        $statement = preg_replace('/\s+/', ' ', $statement) ?? $statement;
        return substr($statement, 0, 240);
    }
}
