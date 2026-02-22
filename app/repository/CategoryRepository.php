<?php

namespace App\Repository;

class CategoryRepository
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findIdByName(string $categoryName): ?int
    {
        $stmt = $this->conn->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $categoryName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return isset($row['id']) ? (int) $row['id'] : null;
    }
}
