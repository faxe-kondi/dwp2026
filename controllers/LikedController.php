<?php
class LikedController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/controllers/ProfilePageContext.php';
        $profileContext = ProfilePageContext::loadOwnProfile();
        $viewer = $profileContext['viewer'];

        $real_liked_posts = array_map('ds_post_view', Post::findLikedByUser((int) $viewer['id']));
        $liked_posts = $real_liked_posts;

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('profile/liked', array_merge($profileContext, compact('liked_posts')));
    }
}
