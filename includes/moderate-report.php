<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Report.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (!Auth::check() || !Auth::canModerate()) {
    http_response_code(403);
    exit('You do not have permission to review reports.');
}

Csrf::verifyOrFail();

$category = $_POST['type'] ?? null;
$subjectId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? null;
$reason = $_POST['reason'] ?? '';
if (
    !is_string($category)
    || $subjectId === false
    || $subjectId === null
    || $subjectId < 1
    || !is_string($action)
    || !is_string($reason)
) {
    http_response_code(400);
    exit('Invalid report action.');
}

try {
    $filename = Report::moderateSubject(
        (int) Auth::user()['id'],
        $category,
        $subjectId,
        $action,
        trim($reason)
    );
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
    error_log('Report moderation failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Something went wrong resolving these reports. Please try again.');
} catch (RuntimeException $e) {
    error_log('Report moderation failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Something went wrong resolving these reports. Please try again.');
}

$cleanupFailed = false;
if ($category === 'posts' && $action === 'remove' && $filename !== null) {
    $imagePath = dirname(__DIR__) . '/uploads/posts/' . basename($filename);
    if (
        basename($filename) !== $filename
        || (is_file($imagePath) && !unlink($imagePath))
    ) {
        error_log('Post image cleanup failed after report moderation for post #' . $subjectId . '.');
        $cleanupFailed = true;
    }
}

if ($action === 'remove') {
    $query = ['category' => $category, 'moderation' => 'removed'];
    if ($cleanupFailed) {
        $query['cleanup_failed'] = '1';
    }
    header('Location: ../reports.php?' . http_build_query($query));
} else {
    header(
        'Location: ../report-details.php?' . http_build_query([
            'type' => $category,
            'id' => $subjectId,
            'moderation' => $action === 'ban' ? 'banned' : 'dismissed',
        ])
    );
}
exit;
