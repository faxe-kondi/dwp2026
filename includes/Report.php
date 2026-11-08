<?php
require_once __DIR__ . '/../db.php';

class Report
{
    private const MODERATION_CATEGORIES = [
        'users' => 'target_user_id',
        'posts' => 'target_post_id',
        'comments' => 'target_comment_id',
    ];

    private static bool $checksTableReady = false;

    private static function ensureChecksTable(): void
    {
        if (self::$checksTableReady) {
            return;
        }

        Database::connect()->exec(
            'CREATE TABLE IF NOT EXISTS moderation_report_checks (
                user_id INT NOT NULL,
                category VARCHAR(20) NOT NULL,
                last_checked_at DATETIME NOT NULL,
                PRIMARY KEY (user_id, category),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        self::$checksTableReady = true;
    }

    public static function getUnreadSubjectCounts(int $userId, int $threshold = 3): array
    {
        self::ensureChecksTable();
        $pdo = Database::connect();
        $counts = array_fill_keys(array_keys(self::MODERATION_CATEGORIES), 0);

        foreach (self::MODERATION_CATEGORIES as $category => $targetColumn) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM (
                    SELECT reports.' . $targetColumn . '
                    FROM reports
                    LEFT JOIN moderation_report_checks AS checks
                      ON checks.user_id = ? AND checks.category = ?
                    WHERE reports.' . $targetColumn . ' IS NOT NULL
                    GROUP BY reports.' . $targetColumn . '
                    HAVING COUNT(reports.id) >= ?
                       AND (
                            MAX(checks.last_checked_at) IS NULL
                            OR SUM(
                                CASE WHEN reports.created_at <= checks.last_checked_at THEN 1 ELSE 0 END
                            ) < ?
                       )
                 ) AS unread_subjects'
            );
            $stmt->execute([$userId, $category, $threshold, $threshold]);
            $counts[$category] = (int) $stmt->fetchColumn();
        }

        return $counts;
    }

    public static function markCategoryChecked(int $userId, string $category): void
    {
        if (!isset(self::MODERATION_CATEGORIES[$category])) {
            throw new InvalidArgumentException('Choose a valid report category.');
        }

        self::ensureChecksTable();
        $stmt = Database::connect()->prepare(
            'INSERT INTO moderation_report_checks (user_id, category, last_checked_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE last_checked_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$userId, $category]);
    }

    public static function getFlaggedSubjects(
        string $category,
        int $threshold = 3,
        int $limit = 12,
        int $offset = 0
    ): array {
        if (!isset(self::MODERATION_CATEGORIES[$category])) {
            throw new InvalidArgumentException('Choose a valid report category.');
        }
        if ($threshold < 1 || $limit < 1 || $limit > 50 || $offset < 0) {
            throw new InvalidArgumentException('Report pagination is out of range.');
        }

        $targetColumn = self::MODERATION_CATEGORIES[$category];
        $pdo = Database::connect();
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM (
                SELECT reports.' . $targetColumn . '
                FROM reports
                WHERE reports.' . $targetColumn . ' IS NOT NULL
                GROUP BY reports.' . $targetColumn . '
                HAVING COUNT(reports.id) >= ?
            ) AS flagged_subjects'
        );
        $countStmt->execute([$threshold]);
        $total = (int) $countStmt->fetchColumn();

        $selectSql = match ($category) {
            'users' => 'SELECT users.id, users.username, users.avatar, users.bio, users.is_banned, roles.name AS role,
                               (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS posts,
                               (SELECT COUNT(*) FROM follows WHERE follows.following_id = users.id) AS followers,
                               COUNT(reports.id) AS report_count,
                               GROUP_CONCAT(DISTINCT report_reasons.name ORDER BY report_reasons.name SEPARATOR \', \') AS reasons,
                               MAX(reports.created_at) AS latest_report
                        FROM reports
                        JOIN report_reasons ON report_reasons.id = reports.reason_id
                        JOIN users ON users.id = reports.target_user_id
                        JOIN roles ON roles.id = users.role_id
                        WHERE reports.target_user_id IS NOT NULL
                        GROUP BY users.id, users.username, users.is_banned, roles.name
                        HAVING COUNT(reports.id) >= ?
                        ORDER BY report_count DESC, latest_report DESC',
            'posts' => 'SELECT posts.id, posts.filename, posts.title, posts.caption, posts.created_at,
                               posts.front_pinned_at, posts.profile_pinned_at,
                               users.id AS user_id, users.username, users.avatar,
                               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                               COUNT(reports.id) AS report_count,
                               GROUP_CONCAT(DISTINCT report_reasons.name ORDER BY report_reasons.name SEPARATOR \', \') AS reasons,
                               MAX(reports.created_at) AS latest_report
                        FROM reports
                        JOIN report_reasons ON report_reasons.id = reports.reason_id
                        JOIN posts ON posts.id = reports.target_post_id
                        JOIN users ON users.id = posts.user_id
                        WHERE reports.target_post_id IS NOT NULL
                        GROUP BY posts.id, posts.title, users.id, users.username
                        HAVING COUNT(reports.id) >= ?
                        ORDER BY report_count DESC, latest_report DESC',
            'comments' => 'SELECT comments.id, comments.content, posts.id AS post_id, posts.title AS post_title,
                                  users.id AS user_id, users.username,
                                  COUNT(reports.id) AS report_count,
                                  GROUP_CONCAT(DISTINCT report_reasons.name ORDER BY report_reasons.name SEPARATOR \', \') AS reasons,
                                  MAX(reports.created_at) AS latest_report
                           FROM reports
                           JOIN report_reasons ON report_reasons.id = reports.reason_id
                           JOIN comments ON comments.id = reports.target_comment_id
                           JOIN posts ON posts.id = comments.post_id
                           JOIN users ON users.id = comments.user_id
                           WHERE reports.target_comment_id IS NOT NULL
                           GROUP BY comments.id, comments.content, posts.id, posts.title, users.id, users.username
                           HAVING COUNT(reports.id) >= ?
                           ORDER BY report_count DESC, latest_report DESC',
        };

        $stmt = $pdo->prepare($selectSql . ' LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $threshold, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function getSubjectReports(string $category, int $subjectId): array
    {
        if (!isset(self::MODERATION_CATEGORIES[$category]) || $subjectId < 1) {
            throw new InvalidArgumentException('Choose a valid reported subject.');
        }

        $targetColumn = self::MODERATION_CATEGORIES[$category];
        $pdo = Database::connect();
        $subjectSql = match ($category) {
            'users' => 'SELECT users.id, users.username, users.avatar, users.bio, users.is_banned, roles.name AS role
                        FROM users JOIN roles ON roles.id = users.role_id WHERE users.id = ?',
            'posts' => 'SELECT posts.id, posts.filename, posts.title, posts.caption, posts.created_at,
                               posts.front_pinned_at, posts.profile_pinned_at, users.id AS user_id,
                               users.username, users.avatar,
                               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS likes,
                               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                               0 AS liked_by_viewer
                        FROM posts JOIN users ON users.id = posts.user_id WHERE posts.id = ?',
            'comments' => 'SELECT comments.id, comments.content, comments.post_id,
                                  posts.title AS post_title, users.id AS user_id, users.username, users.avatar
                           FROM comments
                           JOIN posts ON posts.id = comments.post_id
                           JOIN users ON users.id = comments.user_id
                           WHERE comments.id = ?',
        };
        $stmt = $pdo->prepare($subjectSql);
        $stmt->execute([$subjectId]);
        $subject = $stmt->fetch();
        if (!$subject) {
            throw new OutOfBoundsException('The reported subject could not be found.');
        }

        $stmt = $pdo->prepare(
            'SELECT reports.id, reports.details, reports.created_at,
                    report_reasons.name AS reason, reporters.username AS reporter_username
             FROM reports
             JOIN report_reasons ON report_reasons.id = reports.reason_id
             LEFT JOIN users AS reporters ON reporters.id = reports.reporter_id
             WHERE reports.' . $targetColumn . ' = ?
             ORDER BY
                CASE WHEN reports.details IS NOT NULL AND TRIM(reports.details) <> \'\' THEN 0 ELSE 1 END,
                reports.created_at DESC, reports.id DESC'
        );
        $stmt->execute([$subjectId]);

        return ['subject' => $subject, 'reports' => $stmt->fetchAll()];
    }

    public static function moderateSubject(
        int $actorId,
        string $category,
        int $subjectId,
        string $action,
        string $reason = ''
    ): ?string {
        if (!isset(self::MODERATION_CATEGORIES[$category]) || $subjectId < 1) {
            throw new InvalidArgumentException('Choose a valid reported subject.');
        }

        $validActions = [
            'users' => ['dismiss', 'ban'],
            'posts' => ['dismiss', 'remove'],
            'comments' => ['dismiss', 'remove'],
        ];
        if (!in_array($action, $validActions[$category], true)) {
            throw new InvalidArgumentException('Choose a valid report action.');
        }
        if (mb_strlen($reason, 'UTF-8') > 255) {
            throw new InvalidArgumentException('Ban reason must be 255 characters or fewer.');
        }

        $targetColumn = self::MODERATION_CATEGORIES[$category];
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT roles.name
                 FROM users
                 JOIN roles ON roles.id = users.role_id
                 WHERE users.id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$actorId]);
            $actorRole = $stmt->fetchColumn();
            if (!in_array($actorRole, ['owner', 'moderator'], true)) {
                throw new DomainException('You do not have permission to review reports.');
            }

            $filename = null;
            if ($category === 'users') {
                $stmt = $pdo->prepare(
                    'SELECT users.id, users.is_banned, roles.name AS role
                     FROM users
                     JOIN roles ON roles.id = users.role_id
                     WHERE users.id = ?
                     FOR UPDATE'
                );
                $stmt->execute([$subjectId]);
                $subject = $stmt->fetch();
            } elseif ($category === 'posts') {
                $stmt = $pdo->prepare('SELECT id, filename FROM posts WHERE id = ? FOR UPDATE');
                $stmt->execute([$subjectId]);
                $subject = $stmt->fetch();
                $filename = $subject ? $subject['filename'] : null;
            } else {
                $stmt = $pdo->prepare('SELECT id FROM comments WHERE id = ? FOR UPDATE');
                $stmt->execute([$subjectId]);
                $subject = $stmt->fetch();
            }
            if (!$subject) {
                throw new OutOfBoundsException('The reported subject could not be found.');
            }

            if ($category === 'users' && $action === 'ban') {
                if (
                    $subjectId === $actorId
                    || $subject['role'] === 'owner'
                    || ($actorRole === 'moderator' && $subject['role'] !== 'user')
                ) {
                    throw new DomainException('You do not have permission to ban this account.');
                }
            }

            $stmt = $pdo->prepare(
                'SELECT reports.id
                 FROM reports
                 WHERE reports.' . $targetColumn . ' = ?
                 FOR UPDATE'
            );
            $stmt->execute([$subjectId]);
            if (!$stmt->fetchAll()) {
                throw new DomainException('This subject has no pending reports to resolve.');
            }

            if ($category === 'users' && $action === 'ban') {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET is_banned = 1,
                         ban_reason = COALESCE(?, ban_reason),
                         banned_at = COALESCE(banned_at, CURRENT_TIMESTAMP)
                     WHERE id = ?'
                );
                $stmt->execute([$reason !== '' ? $reason : null, $subjectId]);
            }

            $stmt = $pdo->prepare(
                'DELETE FROM reports WHERE ' . $targetColumn . ' = ?'
            );
            $stmt->execute([$subjectId]);

            if ($category === 'posts' && $action === 'remove') {
                $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
                $stmt->execute([$subjectId]);
            } elseif ($category === 'comments' && $action === 'remove') {
                $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
                $stmt->execute([$subjectId]);
            }

            $pdo->commit();
            return $filename;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function create(
        int $reporterId,
        string $targetType,
        int $targetId,
        int $reasonId,
        string $details
    ): int {
        $targetColumns = [
            'user' => ['users', 'id', 'target_user_id'],
            'post' => ['posts', 'user_id', 'target_post_id'],
            'comment' => ['comments', 'user_id', 'target_comment_id'],
        ];
        if ($targetId < 1 || !isset($targetColumns[$targetType])) {
            throw new InvalidArgumentException('Choose a valid item to report.');
        }
        if ($reasonId < 1) {
            throw new InvalidArgumentException('Choose a valid report reason.');
        }

        $details = trim($details);
        if (mb_strlen($details, 'UTF-8') > 1000) {
            throw new InvalidArgumentException('Additional details must be 1000 characters or fewer.');
        }

        [$table, $ownerColumn, $targetColumn] = $targetColumns[$targetType];
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT 1 FROM report_reasons WHERE id = ? LIMIT 1');
        $stmt->execute([$reasonId]);
        if (!$stmt->fetchColumn()) {
            throw new InvalidArgumentException('Choose a valid report reason.');
        }

        $stmt = $pdo->prepare("SELECT id, {$ownerColumn} AS owner_id FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();
        if (!$target) {
            throw new OutOfBoundsException('The item you tried to report could not be found.');
        }
        if ((int) $target['owner_id'] === $reporterId) {
            throw new DomainException('You cannot report your own content.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO reports (reporter_id, {$targetColumn}, reason_id, details)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$reporterId, $targetId, $reasonId, $details !== '' ? $details : null]);
        return (int) $pdo->lastInsertId();
    }
}
