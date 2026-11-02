<?php
require_once __DIR__ . '/../db.php';

class Comment
{
    public static function search(
        string $query,
        ?int $viewerId = null,
        int $limit = 12,
        int $offset = 0
    ): array
    {
        if ($limit < 1 || $limit > 50 || $offset < 0) {
            throw new InvalidArgumentException('Comment search pagination is out of range.');
        }

        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT comments.id, comments.post_id, comments.content, comments.created_at,
                       posts.title, post_users.username AS post_username,
                       comment_users.id AS user_id, comment_users.username, comment_users.avatar
                FROM comments
                JOIN posts ON posts.id = comments.post_id
                JOIN users AS post_users ON post_users.id = posts.user_id
                JOIN users AS comment_users ON comment_users.id = comments.user_id
                WHERE comments.content LIKE ? ESCAPE \'!\'
                  AND post_users.is_banned = 0
                  AND comment_users.is_banned = 0';
        $params = [$searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id IN (posts.user_id, comments.user_id))
                       OR (blocks.blocked_id = ? AND blocks.blocker_id IN (posts.user_id, comments.user_id))
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $sql .= ' ORDER BY comments.created_at DESC, comments.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countSearchResults(string $query, ?int $viewerId = null): int
    {
        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT COUNT(*)
                FROM comments
                JOIN posts ON posts.id = comments.post_id
                JOIN users AS post_users ON post_users.id = posts.user_id
                JOIN users AS comment_users ON comment_users.id = comments.user_id
                WHERE comments.content LIKE ? ESCAPE \'!\'
                  AND post_users.is_banned = 0
                  AND comment_users.is_banned = 0';
        $params = [$searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id IN (posts.user_id, comments.user_id))
                       OR (blocks.blocked_id = ? AND blocks.blocker_id IN (posts.user_id, comments.user_id))
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

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
