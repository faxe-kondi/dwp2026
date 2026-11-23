<?php
class ReportDetailsController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();

        if (!Auth::check() || !Auth::canModerate()) {
            http_response_code(403);
            exit('Only moderators and the site owner can view report details.');
        }

        require_once dirname(__DIR__) . '/models/Report.php';
        require_once dirname(__DIR__) . '/helpers/bootstrap.php';
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/support/View.php';

        $type = $_GET['type'] ?? '';
        $subjectId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (
            !is_string($type)
            || !in_array($type, ['users', 'posts', 'comments'], true)
            || $subjectId === false
            || $subjectId === null
            || $subjectId < 1
        ) {
            http_response_code(400);
            exit('Invalid reported subject.');
        }

        try {
            $result = Report::getSubjectReports($type, $subjectId);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            exit('Invalid reported subject.');
        } catch (OutOfBoundsException $e) {
            http_response_code(404);
            exit('The reported subject could not be found.');
        } catch (PDOException $e) {
            error_log('Report details lookup failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Something went wrong loading the reports. Please try again.');
        }

        $subject = $result['subject'];
        $reports = $result['reports'];
        $hasPendingReports = $reports !== [];
        $canBanSubject = $type === 'users'
            && (int) $subject['id'] !== (int) Auth::user()['id']
            && $subject['role'] !== 'owner'
            && (Auth::role() === 'owner' || $subject['role'] === 'user');
        $pageTitle = match ($type) {
            'users' => 'Reports for @' . $subject['username'],
            'posts' => 'Reports for ' . $subject['title'],
            'comments' => 'Reports for comment by @' . $subject['username'],
        };
        $moderationStatus = $_GET['moderation'] ?? '';

        View::renderPage('reports/details', compact(
            'type',
            'subject',
            'reports',
            'hasPendingReports',
            'canBanSubject',
            'pageTitle',
            'moderationStatus'
        ));
    }
}
