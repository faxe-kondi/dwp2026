<?php
class FollowingController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/controllers/ProfilePageContext.php';
        $profileContext = ProfilePageContext::loadOwnProfile();
        $viewer = $profileContext['viewer'];
        $following_users = User::findFollowing((int) $viewer['id']);
        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('profile/following', array_merge($profileContext, compact('following_users')));
    }
}
