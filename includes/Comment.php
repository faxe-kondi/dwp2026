<?php
require_once __DIR__ . '/../db.php';

class Comment
{
    private static function validateContent(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            throw new InvalidArgumentException('Write a comment before posting.');
        }
        if (mb_strlen($content, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Comments must be 500 characters or fewer.');
        }
        return $content;
    }

    public static function create(int $postId, int $userId, string $content): int
    {
        $content = self::validateContent($content);

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$postId, $userId, $content]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateOwned(int $commentId, int $postId, int $userId, string $content): bool
    {
        $content = self::validateContent($content);

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'UPDATE comments SET content = ?
             WHERE id = ? AND post_id = ? AND user_id = ?'
        );
        $stmt->execute([$content, $commentId, $postId, $userId]);
        if ($stmt->rowCount() === 1) {
            return true;
        }

        $stmt = $pdo->prepare(
            'SELECT 1 FROM comments
             WHERE id = ? AND post_id = ? AND user_id = ?'
        );
        $stmt->execute([$commentId, $postId, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public static function findByPost(int $postId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT comments.id, comments.user_id, comments.content, comments.created_at,
                    users.username, users.avatar
             FROM comments
             JOIN users ON users.id = comments.user_id
             WHERE comments.post_id = ?
             ORDER BY comments.created_at ASC, comments.id ASC'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public static function deleteOwned(int $commentId, int $postId, int $userId): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'DELETE FROM comments
             WHERE id = ? AND post_id = ? AND user_id = ?'
        );
        $stmt->execute([$commentId, $postId, $userId]);
        return $stmt->rowCount() === 1;
    }
}
