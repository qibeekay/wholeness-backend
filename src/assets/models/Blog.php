<?php

namespace App\Models;

use App\Config\Database;

class Blog
{
    private Database $db;

    private string $table = 'blogs';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // fetch all blogs
    public function all(): array
    {
        return $this->db->query(
            "SELECT id, title, excerpt, content, image, author, created_at FROM {$this->table}"
        )->getAll();
    }

    public function findByExcerpt(string $excerpt): array|false
    {
        return $this->db->query(
            "SELECT id, title, excerpt, content, image, author, created_at FROM {$this->table} WHERE excerpt = :excerpt LIMIT 1",
            ['excerpt' => $excerpt]
        )->find();
    }

    public function find(int $id): array|false
    {
        return $this->db->query(
            "SELECT id, title, excerpt, content, image, author, created_at 
            FROM {$this->table} 
            WHERE id = :id 
            LIMIT 1",
            ['id' => $id]
        )->find();
    }

    // creates a new blog
    public function create(array $data): int
    {
        $payload = [
            'title' => trim($data['title'] ?? ''),
            'excerpt' => trim($data['excerpt'] ?? ''),
             'content' => htmlspecialchars($data['content'] ?? ''),
            'image' => trim($data['image'] ?? ''),
            'author' => trim($data['author'] ?? ''),
        ];

        $this->db->query(
            "INSERT INTO {$this->table} (title, excerpt, content, image, author) VALUES (:title, :excerpt, :content, :image, :author)",
            $payload
        );

        // Access lastInsertId() through the public method
        return (int) $this->db->lastInsertId();
    }

    // updates a blog
    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'excerpt', 'content', 'image', 'author'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                $params[$col] = htmlspecialchars((string) $data[$col]);
            }
        }

        if (!$sets)
            return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        return (bool) $this->db->query($sql, $params)->statement->rowCount();
    }

    // delete a blog
    public function delete(int $id): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM {$this->table} WHERE id=:id",
            ['id' => $id]
        )->statement->rowCount();
    }
}