<?php
class ProfilePageContext
{
    public static function loadOwnProfile(): array
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        Auth::requireLogin();

        require_once dirname(__DIR__) . '/models/User.php';
        require_once dirname(__DIR__) . '/models/Post.php';
        require_once dirname(__DIR__) . '/helpers/bootstrap.php';

        $viewer = User::find((int) Auth::user()['id']);
        if (!$viewer) {
            Auth::logout();
            header('Location: index.php?route=login');
            exit;
        }

        $user_posts = array_map('ds_post_view', Post::findByUser((int) $viewer['id']));
        usort($user_posts, fn($a, $b) => ($b['profile_pinned'] <=> $a['profile_pinned']));
        $profile = ds_profile_view($viewer, count($user_posts));
        $isOwnProfile = true;
        $blockState = ['blocked_by_viewer' => false, 'blocked_viewer' => false];
        $isFollowingProfile = false;
        $canManageProfile = false;

        return compact(
            'viewer',
            'user_posts',
            'profile',
            'isOwnProfile',
            'blockState',
            'isFollowingProfile',
            'canManageProfile'
        );
    }
}
