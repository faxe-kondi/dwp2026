<?php
class HomeController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/models/Post.php';
        require dirname(__DIR__) . '/helpers/bootstrap.php';
        $currentUser = Auth::user();
        $viewerId = $currentUser ? (int) $currentUser['id'] : null;
        $hot_posts = array_map('ds_post_view', Post::findHot($viewerId));
        $newest_posts = array_map('ds_post_view', Post::findNewest($viewerId));
        $pinned_posts = array_map('ds_post_view', Post::findFrontPinned($viewerId));
        $heroPost = $newest_posts[0] ?? null;
        $heroImage = $heroPost ? ds_post_image_url($heroPost) : 'uploads/posts/placeholder1.webp';
        $heroAlt = $heroPost ? $heroPost['title'] : 'DEADSTOCK post';
        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('home', compact('heroImage', 'heroAlt', 'hot_posts', 'newest_posts', 'pinned_posts'));
    }
}
