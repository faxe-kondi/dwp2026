<?php
require_once __DIR__ . '/../db.php';

class RateLimitException extends RuntimeException
{
    private int $retryAfter;

    public function __construct(int $retryAfter, string $message)
    {
        parent::__construct($message);
        $this->retryAfter = $retryAfter;
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}

class RateLimiter
{
    private static function bucketKey(string $action, string $subject): string
    {
        return hash('sha256', $action . "\0" . $subject);
    }

    private static function recordAttempt(
        string $action,
        string $subject,
        int $limit,
        int $windowSeconds
    ): array {
        if ($limit < 1 || $windowSeconds < 1) {
            throw new InvalidArgumentException('Rate limit settings must be positive.');
        }

        $pdo = Database::connect();
        $pdo->exec(
            'DELETE FROM action_rate_limits
             WHERE window_started_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)'
        );
        $stmt = $pdo->prepare(
            'INSERT INTO action_rate_limits (bucket_key, action, window_started_at, attempt_count)
             VALUES (?, ?, CURRENT_TIMESTAMP, 1)
             ON DUPLICATE KEY UPDATE
                attempt_count = IF(
                    window_started_at <= TIMESTAMPADD(SECOND, -?, CURRENT_TIMESTAMP),
                    1,
                    LEAST(attempt_count + 1, ?)
                ),
                window_started_at = IF(
                    window_started_at <= TIMESTAMPADD(SECOND, -?, CURRENT_TIMESTAMP),
                    CURRENT_TIMESTAMP,
                    window_started_at
                )'
        );
        $stmt->execute([
            self::bucketKey($action, $subject),
            $action,
            $windowSeconds,
            $limit + 1,
            $windowSeconds,
        ]);

        $stmt = $pdo->prepare(
            'SELECT attempt_count,
                    TIMESTAMPDIFF(SECOND, window_started_at, CURRENT_TIMESTAMP) AS elapsed_seconds
             FROM action_rate_limits
             WHERE bucket_key = ? AND action = ?'
        );
        $stmt->execute([self::bucketKey($action, $subject), $action]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Could not read the rate limit state.');
        }

        return [
            'attempt_count' => (int) $row['attempt_count'],
            'elapsed_seconds' => (int) $row['elapsed_seconds'],
        ];
    }

    public static function consume(string $action, string $subject, int $limit, int $windowSeconds): int
    {
        $state = self::recordAttempt($action, $subject, $limit, $windowSeconds);
        return $state['attempt_count'] > $limit
            ? max(1, $windowSeconds - $state['elapsed_seconds'])
            : 0;
    }

    public static function check(string $action, string $subject, int $limit, int $windowSeconds): int
    {
        if ($limit < 1 || $windowSeconds < 1) {
            throw new InvalidArgumentException('Rate limit settings must be positive.');
        }

        $stmt = Database::connect()->prepare(
            'SELECT attempt_count,
                    TIMESTAMPDIFF(SECOND, window_started_at, CURRENT_TIMESTAMP) AS elapsed_seconds
             FROM action_rate_limits
             WHERE bucket_key = ? AND action = ?'
        );
        $stmt->execute([self::bucketKey($action, $subject), $action]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['elapsed_seconds'] >= $windowSeconds || (int) $row['attempt_count'] < $limit) {
            return 0;
        }

        return max(1, $windowSeconds - (int) $row['elapsed_seconds']);
    }

    public static function recordFailure(string $action, string $subject, int $limit, int $windowSeconds): int
    {
        $state = self::recordAttempt($action, $subject, $limit, $windowSeconds);
        return $state['attempt_count'] >= $limit
            ? max(1, $windowSeconds - $state['elapsed_seconds'])
            : 0;
    }

    public static function clear(string $action, string $subject): void
    {
        $stmt = Database::connect()->prepare(
            'DELETE FROM action_rate_limits WHERE bucket_key = ? AND action = ?'
        );
        $stmt->execute([self::bucketKey($action, $subject), $action]);
    }

    public static function formatWait(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));
        return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
    }
}
