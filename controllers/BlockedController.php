<?php
class BlockedController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/controllers/ProfilePageContext.php';
        $profileContext = ProfilePageContext::loadOwnProfile();
        $viewer = $profileContext['viewer'];
        $blocked_users = User::findBlocked((int) $viewer['id']);

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('profile/blocked', array_merge($profileContext, compact('blocked_users')));
    }
}
