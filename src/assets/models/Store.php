<?php

namespace App\Models;

use App\Config\Database;

class Store
{
    private Database $db;
    private string $table = 'products';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query(
            "SELECT id, name, description, price, image, inStock, quantity FROM {$this->table}"
        )->getAll();
    }

    public function find(int $id): array|false
    {
        return $this->db
            ->query(
                "SELECT id, name, description, price, image, inStock, quantity
             FROM {$this->table}
             WHERE id = :id
             LIMIT 1",
                ['id' => $id]
            )
            ->find();
    }

    public function create(array $data): int
    {
        $payload = [
            'name' => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'price' => (float) ($data['price'] ?? 0),
            'image' => trim($data['image'] ?? ''),
            'inStock' => (int) ($data['inStock'] ?? 1),
            'quantity' => (int) ($data['quantity'] ?? 0),
        ];

        $this->db->query(
            "INSERT INTO {$this->table}
             (name, description, price, image, inStock, quantity)
             VALUES (:name, :description, :price, :image, :inStock, :quantity)",
            $payload
        );

        // Access lastInsertId() through the public method
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'price', 'image', 'inStock', 'quantity'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                $params[$col] = match ($col) {
                    'price' => (float) $data[$col],
                    'inStock' => (int) $data[$col],
                    'quantity' => (int) $data[$col],
                    default => trim((string) $data[$col])
                };
            }
        }

        if (!$sets)
            return false;   // nothing to update

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return (bool) $this->db->query($sql, $params)->statement->rowCount();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM {$this->table} WHERE id=:id",
            ['id' => $id]
        )->statement->rowCount();
    }
}