<?php
class BrowseController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/models/Post.php';
        require dirname(__DIR__) . '/helpers/bootstrap.php';

        $postsPerPage = 24;
        $requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
            ? $requestedPage
            : 1;
        $sortOptions = ['newest', 'likes', 'comments', 'random'];
        $requestedSort = $_GET['sort'] ?? 'newest';
        $sort = is_string($requestedSort) && in_array($requestedSort, $sortOptions, true)
            ? $requestedSort
            : 'newest';
        $requestedSeed = filter_input(INPUT_GET, 'seed', FILTER_VALIDATE_INT);
        $randomSeed = $requestedSeed !== false
            && $requestedSeed !== null
            && $requestedSeed > 0
            && $requestedSeed <= 2147483647
                ? $requestedSeed
                : random_int(1, 2147483647);
        if ($sort !== 'random') {
            $randomSeed = 1;
        }
        $currentUser = Auth::user();
        $viewerId = $currentUser ? (int) $currentUser['id'] : null;
        $totalPosts = Post::countAll($viewerId);
        $totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
        $page = min($page, $totalPages);
        $posts = array_map(
            'ds_post_view',
            Post::findBrowsePage($postsPerPage, ($page - 1) * $postsPerPage, $viewerId, $sort, $randomSeed)
        );
        $sortQuery = 'sort=' . rawurlencode($sort);
        if ($sort === 'random') {
            $sortQuery .= '&seed=' . $randomSeed;
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('browse', compact(
            'sort',
            'posts',
            'totalPosts',
            'totalPages',
            'page',
            'sortQuery'
        ));
    }
}
