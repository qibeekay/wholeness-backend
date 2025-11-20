<?php

namespace App\Models;

use App\Config\Database;

class Events
{
    private Database $db;
    private string $table = 'events';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /* ---------- basic CRUD ---------- */
    public function all(): array
    {
        return $this->db->query(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS booked
             FROM {$this->table} e
             ORDER BY e.event_date DESC, e.event_time DESC"
        )->getAll();
    }

    public function find(int $id): array|false
    {
        $event = $this->db->query(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS booked
             FROM {$this->table} e
             WHERE e.id = :id
             LIMIT 1",
            ['id' => $id]
        )->find();

        if ($event) {
            $event['attendees'] = $this->db->query(
                "SELECT u.id, u.name, u.email, a.created_at
                 FROM event_attendees a
                 JOIN users u ON u.id = a.user_id
                 WHERE a.event_id = :id",
                ['id' => $id]
            )->getAll();
        }
        return $event;
    }

    public function create(array $data): int
    {
        $payload = [
            'title'       => trim($data['title']),
            'description' => htmlspecialchars($data['description']),
            'event_date'  => $data['event_date'],
            'event_time'  => $data['event_time'],
            'venue'       => trim($data['venue']),
            'capacity'    => (int)$data['capacity'],
            'category'    => $data['category'],
            'price'       => trim($data['price']),
            'image'       => trim($data['image'] ?? ''),
        ];
        $this->db->query(
            "INSERT INTO {$this->table}
             (title, description, event_date, event_time, venue, capacity, category, price, image)
             VALUES
             (:title, :description, :event_date, :event_time, :venue, :capacity, :category, :price, :image)",
            $payload
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'title',
            'description',
            'event_date',
            'event_time',
            'venue',
            'capacity',
            'category',
            'price',
            'image'
        ];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "$col = :$col";
                $params[$col] = $col === 'description'
                    ? htmlspecialchars((string)$data[$col])
                    : trim((string)$data[$col]);
            }
        }
        if (!$sets) return false;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        return (bool) $this->db->query($sql, $params)->statement->rowCount();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        )->statement->rowCount();
    }

    /* ---------- attendee helpers ---------- */
    public function book(int $eventId, int $userId): bool
    {
        // ensure room
        $row = $this->db->query(
            "SELECT capacity,
                    (SELECT COUNT(*) FROM event_attendees WHERE event_id = :eid) AS booked
             FROM {$this->table} WHERE id = :eid",
            ['eid' => $eventId]
        )->find();
        if (!$row || $row['booked'] >= $row['capacity']) {
            return false;
        }
        try {
            $this->db->query(
                "INSERT INTO event_attendees (event_id, user_id) VALUES (:eid, :uid)",
                ['eid' => $eventId, 'uid' => $userId]
            );
            return true;
        } catch (\PDOException $e) {
            // duplicate = already booked
            return false;
        }
    }

    public function cancelBooking(int $eventId, int $userId): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM event_attendees WHERE event_id = :eid AND user_id = :uid",
            ['eid' => $eventId, 'uid' => $userId]
        )->statement->rowCount();
    }

    public function removeAttendee(int $eventId, int $userId): bool
    {
        // admin only – same sql as cancel
        return $this->cancelBooking($eventId, $userId);
    }
}
