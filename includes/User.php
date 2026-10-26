<?php
require_once __DIR__ . '/../db.php';

class User
{
    public static function find(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT id, username, bio, avatar, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
