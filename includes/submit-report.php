<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Report.php';

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

$targetType = $_POST['target_type'] ?? null;
$targetId = filter_var($_POST['target_id'] ?? null, FILTER_VALIDATE_INT);
$reasonId = filter_var($_POST['reason_id'] ?? null, FILTER_VALIDATE_INT);
$details = $_POST['details'] ?? '';
if (
    !is_string($targetType)
    || $targetId === false
    || $targetId === null
    || $reasonId === false
    || $reasonId === null
    || !is_string($details)
) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request']);
    exit;
}

try {
    Report::create(
        (int) Auth::user()['id'],
        $targetType,
        (int) $targetId,
        (int) $reasonId,
        $details
    );
    echo json_encode(['success' => true]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request', 'message' => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(['error' => 'target_not_found']);
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'cannot_report_own_content']);
} catch (PDOException $e) {
    error_log('Report submission failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
