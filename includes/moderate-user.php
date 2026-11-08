<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Moderation.php';
require_once __DIR__ . '/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!Auth::check() || !Auth::canModerate()) {
    http_response_code(403);
    exit('You do not have permission to moderate users.');
}

Csrf::verifyOrFail();

$targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? null;
$reason = $_POST['reason'] ?? '';
if (
    $targetId === false
    || $targetId === null
    || $targetId < 1
    || !is_string($action)
    || !in_array($action, ['ban', 'unban'], true)
    || !is_string($reason)
) {
    http_response_code(400);
    exit('Invalid moderation request.');
}

try {
    $target = User::find($targetId);
    if ($target === null) {
        throw new OutOfBoundsException('User not found.');
    }

    Moderation::setUserBanned(
        (int) Auth::user()['id'],
        $targetId,
        $action === 'ban',
        trim($reason)
    );
    header('Location: ../@' . rawurlencode($target['username']) . '?moderation=' . ($action === 'ban' ? 'banned' : 'unbanned'));
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(htmlspecialchars($e->getMessage()));
} catch (DomainException $e) {
    http_response_code(403);
    exit(htmlspecialchars($e->getMessage()));
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    exit(htmlspecialchars($e->getMessage()));
} catch (PDOException $e) {
    error_log('User moderation failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Something went wrong updating this account. Please try again.');
}
