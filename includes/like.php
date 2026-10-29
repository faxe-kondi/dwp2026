<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Post.php';

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

$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
if (!$postId || $postId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_post']);
    exit;
}

try {
    $result = Post::toggleLike((int) $postId, (int) Auth::user()['id']);
    if ($result === null) {
        http_response_code(404);
        echo json_encode(['error' => 'post_not_found']);
        exit;
    }

    echo json_encode($result);
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'own_post']);
} catch (PDOException $e) {
    error_log('Post like update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
