<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ClassbookEntry
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT ce.*, t.firstname as teacher_firstname, t.lastname as teacher_lastname, t.abbreviation,
                    c.name as class_name
             FROM classbook_entries ce
             JOIN teachers t ON t.id = ce.teacher_id
             JOIN classes c ON c.id = ce.class_id
             WHERE ce.id = ?',
            [$id]
        );
    }

    public static function findByClass(int $classId, array $filters = []): array
    {
        $sql = 'SELECT ce.*, t.firstname as teacher_firstname, t.lastname as teacher_lastname, t.abbreviation
                FROM classbook_entries ce
                JOIN teachers t ON t.id = ce.teacher_id
                WHERE ce.class_id = ?';
        $params = [$classId];

        if (!empty($filters['date_from'])) {
            $sql .= ' AND ce.entry_date >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND ce.entry_date <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['teacher_id'])) {
            $sql .= ' AND ce.teacher_id = ?';
            $params[] = $filters['teacher_id'];
        }

        $sql .= ' ORDER BY ce.entry_date DESC, ce.lesson ASC';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO classbook_entries (class_id, teacher_id, entry_date, lesson, topic, notes) VALUES (?, ?, ?, ?, ?, ?)',
            [$data['class_id'], $data['teacher_id'], $data['entry_date'], $data['lesson'], $data['topic'], $data['notes'] ?? null]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE classbook_entries SET entry_date = ?, lesson = ?, topic = ?, notes = ? WHERE id = ?',
            [$data['entry_date'], $data['lesson'], $data['topic'], $data['notes'] ?? null, $id]
        );
    }

    public static function canEdit(array $entry, int $userId, string $role): bool
    {
        // Admin kann immer bearbeiten
        if ($role === 'admin') {
            return true;
        }

        // Lehrer nur eigene Eintraege innerhalb 24h
        if ($role === 'lehrer') {
            $teacher = Teacher::findByUserId($userId);
            if (!$teacher || $teacher['id'] !== $entry['teacher_id']) {
                return false;
            }
            $createdAt = strtotime($entry['created_at']);
            return (time() - $createdAt) < 86400; // 24 Stunden
        }

        return false;
    }
}
