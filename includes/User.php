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

    public static function isFollowing(int $followerId, int $followingId): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1'
        );
        $stmt->execute([$followerId, $followingId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function setFollowing(int $followerId, int $followingId, bool $following): ?bool
    {
        if ($followerId === $followingId) {
            throw new DomainException('You cannot follow yourself.');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$followingId]);
        if (!$stmt->fetchColumn()) {
            return null;
        }

        if ($following) {
            $stmt = $pdo->prepare(
                'INSERT INTO follows (follower_id, following_id) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE created_at = created_at'
            );
            $stmt->execute([$followerId, $followingId]);
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM follows WHERE follower_id = ? AND following_id = ?'
            );
            $stmt->execute([$followerId, $followingId]);
        }

        return $following;
    }
}
