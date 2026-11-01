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

    public static function findFollowing(int $followerId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT users.id, users.username, users.bio, users.avatar,
                    (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS posts,
                    (SELECT COUNT(*) FROM follows AS followers WHERE followers.following_id = users.id) AS followers
             FROM follows
             JOIN users ON users.id = follows.following_id
             WHERE follows.follower_id = ?
             ORDER BY follows.created_at DESC, users.username ASC'
        );
        $stmt->execute([$followerId]);
        return $stmt->fetchAll();
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
