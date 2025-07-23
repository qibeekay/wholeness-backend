<?php

namespace App\Models;

use App\Config\Database;

class Users
{
    private $connection;
    private $table = 'users';

    public readonly string $name;
    public readonly string $google_id;
    public readonly string $email;
    public readonly string $avatar;

    public function __construct(Database $db, string $name, string $email, string $google_id, string $avatar)
    {
        $this->connection = $db;
        $this->name = $name;
        $this->email = $email;
        $this->google_id = $google_id;
        $this->avatar = $avatar;
    }

    public function findOrCreateGoogleUser(): bool
    {
        $query = "SELECT id, name, email, google_id, avatar FROM {$this->table} WHERE google_id = :google_id OR email = :email LIMIT 1";
        $statement = $this->connection->query($query, [
            'google_id' => $this->google_id,
            'email' => $this->email
        ]);
        $result = $statement->find();

        if ($result) {
            $query = "UPDATE {$this->table} SET name = :name, avatar = :avatar WHERE google_id = :google_id";
            $statement = $this->connection->query($query, [
                'name' => $this->name,
                'avatar' => $this->avatar,
                'google_id' => $this->google_id
            ]);
            return $statement !== false;
        } else {
            $query = "INSERT INTO {$this->table} (name, email, google_id, avatar, created_at) VALUES (:name, :email, :google_id, :avatar, NOW())";
            $statement = $this->connection->query($query, [
                'name' => $this->name,
                'email' => $this->email,
                'google_id' => $this->google_id,
                'avatar' => $this->avatar
            ]);
            return $statement !== false;
        }
    }

    public function getUserByGoogleId(): ?array
    {
        $query = "SELECT id, name, email, google_id, avatar, created_at FROM {$this->table} WHERE google_id = :google_id LIMIT 1";
        $statement = $this->connection->query($query, ['google_id' => $this->google_id]);
        return $statement->find() ?: null;
    }
}