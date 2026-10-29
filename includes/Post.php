<?php
require_once __DIR__ . '/../db.php';

class Post
{
    public static function create(int $userId, string $filename, ?string $caption): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, filename, caption) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $filename, $caption]);
        return (int) $pdo->lastInsertId();
    }


    public static function findById(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar
             FROM posts
             JOIN users ON users.id = posts.user_id
             WHERE posts.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByUser(int $userId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar
             FROM posts
             JOIN users ON users.id = posts.user_id
             WHERE posts.user_id = ?
             ORDER BY (posts.profile_pinned_at IS NULL) ASC, posts.profile_pinned_at DESC, posts.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function updateOwned(int $id, int $userId, ?string $filename, ?string $caption): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM posts WHERE id = ? AND user_id = ? FOR UPDATE');
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return false;
            }

            $stmt = $pdo->prepare(
                'UPDATE posts SET filename = COALESCE(?, filename), caption = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$filename, $caption, $id, $userId]);
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function deleteOwned(int $id, int $userId): ?string
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT filename FROM posts WHERE id = ? AND user_id = ? FOR UPDATE');
            $stmt->execute([$id, $userId]);
            $filename = $stmt->fetchColumn();
            if ($filename === false) {
                $pdo->rollBack();
                return null;
            }

            $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            $pdo->commit();
            return $filename;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}