<?php
require_once __DIR__ . '/../db.php';

class Report
{
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
