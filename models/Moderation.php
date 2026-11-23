<?php
require_once __DIR__ . '/../db.php';

class Moderation
{
    public static function setUserBanned(int $actorId, int $targetId, bool $banned, string $reason = ''): void
    {
        if ($targetId === $actorId) {
            throw new DomainException('You cannot ban or unban your own account.');
        }
        if (mb_strlen($reason, 'UTF-8') > 255) {
            throw new InvalidArgumentException('Ban reason must be 255 characters or fewer.');
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT users.id, users.is_banned, roles.name AS role
                 FROM users
                 JOIN roles ON roles.id = users.role_id
                 WHERE users.id IN (?, ?)
                 ORDER BY users.id
                 FOR UPDATE'
            );
            $stmt->execute([$actorId, $targetId]);
            $accounts = [];
            foreach ($stmt->fetchAll() as $account) {
                $accounts[(int) $account['id']] = $account;
            }

            if (!isset($accounts[$actorId]) || !isset($accounts[$targetId])) {
                throw new OutOfBoundsException('The account could not be found.');
            }

            $actorRole = $accounts[$actorId]['role'];
            $targetRole = $accounts[$targetId]['role'];
            if (!in_array($actorRole, ['owner', 'moderator'], true)) {
                throw new DomainException('You do not have permission to moderate users.');
            }
            if (
                $targetRole === 'owner'
                || ($actorRole === 'moderator' && $targetRole !== 'user')
            ) {
                throw new DomainException('You do not have permission to moderate this account.');
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET is_banned = ?, ban_reason = ?,
                     banned_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE NULL END
                 WHERE id = ?'
            );
            $stmt->execute([
                $banned ? 1 : 0,
                $banned && $reason !== '' ? $reason : null,
                $banned ? 1 : 0,
                $targetId,
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
