<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/User.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'login_required']);
    exit;
}

if (!Csrf::check()) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_request']);
    exit;
}

$blockedId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$blocked = $_POST['blocked'] ?? null;
if (
    !$blockedId
    || $blockedId < 1
    || !is_string($blocked)
    || !in_array($blocked, ['0', '1'], true)
) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request']);
    exit;
}

try {
    $result = User::setBlocked(
        (int) Auth::user()['id'],
        $blockedId,
        $blocked === '1'
    );
    if ($result === null) {
        http_response_code(404);
        echo json_encode(['error' => 'user_not_found']);
        exit;
    }

    echo json_encode(['blocked' => $result]);
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'cannot_block_self']);
} catch (PDOException $e) {
    error_log('User block update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
