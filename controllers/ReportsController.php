<?php
class ReportsController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();

        if (!Auth::check() || !Auth::canModerate()) {
            http_response_code(403);
            exit('Only moderators and the site owner can view reported content.');
        }

        require_once dirname(__DIR__) . '/models/Report.php';
        require_once dirname(__DIR__) . '/helpers/bootstrap.php';
        require_once dirname(__DIR__) . '/support/View.php';

        $threshold = 3;
        $itemsPerPage = 12;
        $categories = ['users' => 'Users', 'posts' => 'Posts', 'comments' => 'Comments'];
        $requestedCategory = $_GET['category'] ?? '';
        $category = is_string($requestedCategory) && isset($categories[$requestedCategory])
            ? $requestedCategory
            : '';
        $requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
            ? $requestedPage
            : 1;

        if ($category !== '') {
            Report::markCategoryChecked((int) Auth::user()['id'], $category);
        }
        $newReports = Report::getUnreadSubjectCounts((int) Auth::user()['id'], $threshold);
        $results = $category !== ''
            ? Report::getFlaggedSubjects(
                $category,
                $threshold,
                $itemsPerPage,
                ($page - 1) * $itemsPerPage
            )
            : ['items' => [], 'total' => 0];
        $totalPages = max(1, (int) ceil($results['total'] / $itemsPerPage));
        if ($category !== '' && $page > $totalPages) {
            $page = $totalPages;
            $results = Report::getFlaggedSubjects(
                $category,
                $threshold,
                $itemsPerPage,
                ($page - 1) * $itemsPerPage
            );
        }

        $pageUrl = static function (int $pageNumber) use ($category): string {
            return 'reports.php?' . http_build_query([
                'category' => $category,
                'page' => $pageNumber,
            ]);
        };
        $moderationStatus = $_GET['moderation'] ?? '';
        $cleanupFailed = ($_GET['cleanup_failed'] ?? '') === '1';

        View::renderPage('reports/index', compact(
            'threshold',
            'categories',
            'category',
            'page',
            'results',
            'totalPages',
            'pageUrl',
            'newReports',
            'moderationStatus',
            'cleanupFailed'
        ));
    }
}
