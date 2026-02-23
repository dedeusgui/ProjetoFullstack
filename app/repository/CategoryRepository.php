<?php

namespace App\Repository;

class CategoryRepository
{
    use InteractsWithDatabase;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findIdByName(string $categoryName): ?int
    {
        $stmt = $this->prepareOrFail('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $categoryName);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc();

        return isset($row['id']) ? (int) $row['id'] : null;
    }
}
