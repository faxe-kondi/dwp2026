<?php
class SearchController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/models/Post.php';
        require_once dirname(__DIR__) . '/models/User.php';
        require_once dirname(__DIR__) . '/models/Comment.php';
        require dirname(__DIR__) . '/helpers/bootstrap.php';

        $requestedQuery = $_GET['q'] ?? '';
        $searchQuery = is_string($requestedQuery) ? trim($requestedQuery) : '';
        $searchTypes = ['users', 'posts', 'comments'];
        $requestedType = $_GET['type'] ?? '';
        $searchType = is_string($requestedType) && in_array($requestedType, $searchTypes, true)
            ? $requestedType
            : '';
        $hasValidQuery = $searchQuery !== '' && mb_strlen($searchQuery, 'UTF-8') <= 100;
        $requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
            ? $requestedPage
            : 1;
        $resultsPerPage = 12;
        $totalResults = 0;
        $totalPages = 1;
        $currentUser = Auth::user();
        $viewerId = $currentUser ? (int) $currentUser['id'] : null;
        $users = [];
        $posts = [];
        $comments = [];

        if ($hasValidQuery && $searchType !== '') {
            switch ($searchType) {
                case 'users':
                    $totalResults = User::countSearchResults($searchQuery, $viewerId);
                    $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
                    $page = min($page, $totalPages);
                    $users = User::search(
                        $searchQuery,
                        $viewerId,
                        $resultsPerPage,
                        ($page - 1) * $resultsPerPage
                    );
                    break;
                case 'posts':
                    $totalResults = Post::countSearchResults($searchQuery, $viewerId);
                    $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
                    $page = min($page, $totalPages);
                    $posts = array_map(
                        'ds_post_view',
                        Post::search($searchQuery, $viewerId, $resultsPerPage, ($page - 1) * $resultsPerPage)
                    );
                    break;
                case 'comments':
                    $totalResults = Comment::countSearchResults($searchQuery, $viewerId);
                    $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
                    $page = min($page, $totalPages);
                    $comments = Comment::search(
                        $searchQuery,
                        $viewerId,
                        $resultsPerPage,
                        ($page - 1) * $resultsPerPage
                    );
                    break;
            }
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('search', compact(
            'searchQuery',
            'searchTypes',
            'searchType',
            'hasValidQuery',
            'page',
            'totalResults',
            'totalPages',
            'users',
            'posts',
            'comments'
        ));
    }
}
