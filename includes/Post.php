<?php
require_once __DIR__ . '/../db.php';

class Post
{
    public static function create(int $userId, string $filename, string $title, ?string $caption): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, filename, title, caption) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $filename, $title, $caption]);
        return (int) $pdo->lastInsertId();
    }


    public static function findById(int $id, ?int $viewerId = null): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.title, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                    EXISTS (
                        SELECT 1 FROM likes
                        WHERE likes.post_id = posts.id AND likes.user_id = ?
                    ) AS liked_by_viewer
             FROM posts
             JOIN users ON users.id = posts.user_id
             WHERE posts.id = ?
             LIMIT 1'
        );
        $stmt->execute([$viewerId ?? 0, $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByUser(int $userId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.title, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                    0 AS liked_by_viewer
             FROM posts
             JOIN users ON users.id = posts.user_id
             WHERE posts.user_id = ?
             ORDER BY (posts.profile_pinned_at IS NULL) ASC, posts.profile_pinned_at DESC, posts.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function search(
        string $query,
        ?int $viewerId = null,
        int $limit = 12,
        int $offset = 0
    ): array
    {
        if ($limit < 1 || $limit > 50 || $offset < 0) {
            throw new InvalidArgumentException('Post search pagination is out of range.');
        }

        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT posts.id, posts.user_id, posts.filename, posts.title, posts.caption, posts.created_at,
                       posts.front_pinned_at, posts.profile_pinned_at,
                       users.username, users.avatar,
                       (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                       (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                       EXISTS (
                           SELECT 1 FROM likes
                           WHERE likes.post_id = posts.id AND likes.user_id = ?
                       ) AS liked_by_viewer
                FROM posts
                JOIN users ON users.id = posts.user_id
                WHERE (posts.title LIKE ? ESCAPE \'!\' OR posts.caption LIKE ? ESCAPE \'!\')
                  AND users.is_banned = 0';
        $params = [$viewerId ?? 0, $searchTerm, $searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = posts.user_id)
                       OR (blocks.blocker_id = posts.user_id AND blocks.blocked_id = ?)
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $sql .= ' ORDER BY posts.created_at DESC, posts.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countSearchResults(string $query, ?int $viewerId = null): int
    {
        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT COUNT(*)
                FROM posts
                JOIN users ON users.id = posts.user_id
                WHERE (posts.title LIKE ? ESCAPE \'!\' OR posts.caption LIKE ? ESCAPE \'!\')
                  AND users.is_banned = 0';
        $params = [$searchTerm, $searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = posts.user_id)
                       OR (blocks.blocker_id = posts.user_id AND blocks.blocked_id = ?)
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function findLikedByUser(int $userId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.title, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar,
                    (SELECT COUNT(*) FROM likes AS all_likes WHERE all_likes.post_id = posts.id) AS likes,
                    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                    1 AS liked_by_viewer
             FROM likes
             JOIN posts ON posts.id = likes.post_id
             JOIN users ON users.id = posts.user_id
             WHERE likes.user_id = ?
               AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = posts.user_id)
                       OR (blocks.blocker_id = posts.user_id AND blocks.blocked_id = ?)
               )
             ORDER BY likes.created_at DESC'
        );
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public static function findHot(?int $viewerId = null): array
    {
        return self::findFeed(
            'JOIN (
                SELECT post_id, COUNT(*) AS hot_likes
                FROM likes
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                GROUP BY post_id
             ) AS recent_likes ON recent_likes.post_id = posts.id
             ORDER BY recent_likes.hot_likes DESC, posts.created_at DESC',
            $viewerId,
            true
        );
    }

    public static function findNewest(?int $viewerId = null): array
    {
        return self::findFeed(
            'ORDER BY posts.created_at DESC',
            $viewerId
        );
    }

    public static function countAll(?int $viewerId = null): int
    {
        $pdo = Database::connect();
        $sql = 'SELECT COUNT(*) FROM posts';
        if ($viewerId !== null) {
            $sql .= '
                WHERE NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = posts.user_id)
                       OR (blocks.blocker_id = posts.user_id AND blocks.blocked_id = ?)
                )';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$viewerId, $viewerId]);
            return (int) $stmt->fetchColumn();
        }

        return (int) $pdo->query($sql)->fetchColumn();
    }

    public static function findBrowsePage(
        int $limit,
        int $offset,
        ?int $viewerId = null,
        string $sort = 'newest',
        int $randomSeed = 1
    ): array
    {
        switch ($sort) {
            case 'likes':
                $orderBy = 'ORDER BY likes DESC, posts.created_at DESC, posts.id DESC';
                break;
            case 'comments':
                $orderBy = 'ORDER BY comment_count DESC, posts.created_at DESC, posts.id DESC';
                break;
            case 'random':
                if ($randomSeed < 1 || $randomSeed > 2147483647) {
                    throw new InvalidArgumentException('Random sort seed is out of range.');
                }
                $orderBy = 'ORDER BY RAND(' . $randomSeed . '), posts.id';
                break;
            case 'newest':
                $orderBy = 'ORDER BY posts.created_at DESC, posts.id DESC';
                break;
            default:
                throw new InvalidArgumentException('Unsupported browse sort order.');
        }

        return self::findFeed(
            $orderBy,
            $viewerId,
            false,
            $limit,
            $offset
        );
    }

    public static function findFrontPinned(?int $viewerId = null): array
    {
        return self::findFeed(
            'WHERE posts.front_pinned_at IS NOT NULL
             ORDER BY posts.front_pinned_at DESC, posts.created_at DESC',
            $viewerId,
            false,
            null
        );
    }

    private static function findFeed(
        string $feedSql,
        ?int $viewerId,
        bool $includeHotLikes = false,
        ?int $limit = 8,
        int $offset = 0
    ): array
    {
        if (($limit !== null && $limit < 1) || $offset < 0) {
            throw new InvalidArgumentException('Feed limits must be positive and offsets must be non-negative.');
        }

        $params = [$viewerId ?? 0];
        if ($viewerId !== null) {
            $filter = 'NOT EXISTS (
                SELECT 1 FROM blocks
                WHERE (blocks.blocker_id = ? AND blocks.blocked_id = posts.user_id)
                   OR (blocks.blocker_id = posts.user_id AND blocks.blocked_id = ?)
            )';
            $orderPosition = stripos($feedSql, 'ORDER BY');
            if ($orderPosition === false) {
                throw new LogicException('Feed SQL must include an ORDER BY clause.');
            }

            $beforeOrder = substr($feedSql, 0, $orderPosition);
            $separator = preg_match('/^\s*WHERE\b/i', $beforeOrder) ? ' AND ' : ' WHERE ';
            $feedSql = $beforeOrder . $separator . $filter . ' ' . substr($feedSql, $orderPosition);
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT posts.id, posts.user_id, posts.filename, posts.title, posts.caption, posts.created_at,
                    posts.front_pinned_at, posts.profile_pinned_at,
                    users.username, users.avatar,
                    ' . ($includeHotLikes ? 'recent_likes.hot_likes' : '0') . ' AS hot_likes,
                    (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                    EXISTS (
                        SELECT 1 FROM likes
                        WHERE likes.post_id = posts.id AND likes.user_id = ?
                    ) AS liked_by_viewer
             FROM posts
             JOIN users ON users.id = posts.user_id
             ' . $feedSql
            . ($limit === null ? '' : ' LIMIT ' . $limit . ' OFFSET ' . $offset)
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateOwned(
        int $id,
        int $userId,
        ?string $filename,
        string $title,
        ?string $caption
    ): bool
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
                'UPDATE posts SET filename = COALESCE(?, filename), title = ?, caption = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$filename, $title, $caption, $id, $userId]);
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

    public static function deleteByModerator(int $id): ?string
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT filename FROM posts WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $filename = $stmt->fetchColumn();
            if ($filename === false) {
                $pdo->rollBack();
                return null;
            }

            $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $pdo->commit();
            return $filename;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function toggleLike(int $postId, int $userId): ?array
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ? FOR UPDATE');
            $stmt->execute([$postId]);
            $ownerId = $stmt->fetchColumn();

            if ($ownerId === false) {
                $pdo->rollBack();
                return null;
            }
            if ((int) $ownerId === $userId) {
                $pdo->rollBack();
                throw new DomainException('You cannot like your own post.');
            }

            $stmt = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
            $stmt->execute([$userId, $postId]);
            $liked = $stmt->fetchColumn() !== false;

            if ($liked) {
                $stmt = $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
                $stmt->execute([$userId, $postId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
                $stmt->execute([$userId, $postId]);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
            $stmt->execute([$postId]);
            $likeCount = (int) $stmt->fetchColumn();
            $pdo->commit();

            return ['liked' => !$liked, 'likes' => $likeCount];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}