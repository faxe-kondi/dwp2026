<?php
require_once __DIR__ . '/../db.php';

class User
{
    public static function findForLogin(string $identifier): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT users.id, users.username, users.password_hash, roles.name AS role,
                    users.is_banned, users.ban_reason
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE users.username = ? OR users.email = ?
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function usernameExists(string $username, ?int $exceptUserId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = ?';
        $params = [$username];
        if ($exceptUserId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptUserId;
        }

        $stmt = Database::connect()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connect()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    public static function createAccount(string $username, string $email, string $passwordHash): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash]);
        return (int) Database::connect()->lastInsertId();
    }

    public static function updateProfile(
        int $userId,
        string $username,
        ?string $bio,
        ?string $avatarFilename = null
    ): void {
        if ($avatarFilename === null) {
            $stmt = Database::connect()->prepare(
                'UPDATE users SET username = ?, bio = ? WHERE id = ?'
            );
            $stmt->execute([$username, $bio, $userId]);
            return;
        }

        $stmt = Database::connect()->prepare(
            'UPDATE users SET username = ?, bio = ?, avatar = ? WHERE id = ?'
        );
        $stmt->execute([$username, $bio, $avatarFilename, $userId]);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT users.id, users.username, users.bio, users.avatar, users.created_at,
                    users.is_banned, roles.name AS role,
                    (SELECT COUNT(*) FROM follows WHERE follows.following_id = users.id) AS follower_count,
                    (SELECT COUNT(*) FROM follows WHERE follows.follower_id = users.id) AS following_count
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE users.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT users.id, users.username, users.bio, users.avatar, users.created_at,
                    users.is_banned, roles.name AS role,
                    (SELECT COUNT(*) FROM follows WHERE follows.following_id = users.id) AS follower_count,
                    (SELECT COUNT(*) FROM follows WHERE follows.follower_id = users.id) AS following_count
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE users.username = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getFollowCounts(int $userId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM follows WHERE following_id = ?) AS follower_count,
                (SELECT COUNT(*) FROM follows WHERE follower_id = ?) AS following_count'
        );
        $stmt->execute([$userId, $userId]);
        $counts = $stmt->fetch();

        return [
            'followers' => (int) $counts['follower_count'],
            'following' => (int) $counts['following_count'],
        ];
    }

    public static function search(
        string $query,
        ?int $viewerId = null,
        int $limit = 12,
        int $offset = 0
    ): array
    {
        if ($limit < 1 || $limit > 50 || $offset < 0) {
            throw new InvalidArgumentException('User search pagination is out of range.');
        }

        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT users.id, users.username, users.bio, users.avatar,
                       (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS posts,
                       (SELECT COUNT(*) FROM follows AS followers WHERE followers.following_id = users.id) AS followers
                FROM users
                WHERE users.is_banned = 0 AND users.username LIKE ? ESCAPE \'!\'';
        $params = [$searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = users.id)
                       OR (blocks.blocker_id = users.id AND blocks.blocked_id = ?)
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $sql .= ' ORDER BY users.username ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countSearchResults(string $query, ?int $viewerId = null): int
    {
        $pdo = Database::connect();
        $searchTerm = '%' . strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $sql = 'SELECT COUNT(*)
                FROM users
                WHERE users.is_banned = 0 AND users.username LIKE ? ESCAPE \'!\'';
        $params = [$searchTerm];

        if ($viewerId !== null) {
            $sql .= '
                AND NOT EXISTS (
                    SELECT 1 FROM blocks
                    WHERE (blocks.blocker_id = ? AND blocks.blocked_id = users.id)
                       OR (blocks.blocker_id = users.id AND blocks.blocked_id = ?)
                )';
            $params[] = $viewerId;
            $params[] = $viewerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
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

    public static function findBlocked(int $blockerId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT users.id, users.username, users.bio, users.avatar,
                    (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS posts,
                    (SELECT COUNT(*) FROM follows AS followers WHERE followers.following_id = users.id) AS followers
             FROM blocks
             JOIN users ON users.id = blocks.blocked_id
             WHERE blocks.blocker_id = ?
             ORDER BY blocks.created_at DESC, users.username ASC'
        );
        $stmt->execute([$blockerId]);
        return $stmt->fetchAll();
    }

    public static function getBlockState(int $viewerId, int $profileId): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT
                EXISTS (
                    SELECT 1 FROM blocks
                    WHERE blocker_id = ? AND blocked_id = ?
                ) AS blocked_by_viewer,
                EXISTS (
                    SELECT 1 FROM blocks
                    WHERE blocker_id = ? AND blocked_id = ?
                ) AS blocked_viewer'
        );
        $stmt->execute([$viewerId, $profileId, $profileId, $viewerId]);
        $state = $stmt->fetch();
        return [
            'blocked_by_viewer' => !empty($state['blocked_by_viewer']),
            'blocked_viewer' => !empty($state['blocked_viewer']),
        ];
    }

    public static function setBlocked(int $blockerId, int $blockedId, bool $blocked): ?bool
    {
        if ($blockerId === $blockedId) {
            throw new DomainException('You cannot block yourself.');
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$blockedId]);
        if (!$stmt->fetchColumn()) {
            return null;
        }

        if ($blocked) {
            $stmt = $pdo->prepare(
                'INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE created_at = created_at'
            );
            $stmt->execute([$blockerId, $blockedId]);
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?'
            );
            $stmt->execute([$blockerId, $blockedId]);
        }

        return $blocked;
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
            try {
                $stmt->execute([$followerId, $followingId]);
            } catch (PDOException $e) {
                if (
                    ($e->errorInfo[0] ?? null) === '45000'
                    && ($e->errorInfo[2] ?? null) === 'A block prevents this follow.'
                ) {
                    throw new DomainException('A block prevents this follow.', 0, $e);
                }
                throw $e;
            }
        } else {
            $stmt = $pdo->prepare(
                'DELETE FROM follows WHERE follower_id = ? AND following_id = ?'
            );
            $stmt->execute([$followerId, $followingId]);
        }

        return $following;
    }
}
