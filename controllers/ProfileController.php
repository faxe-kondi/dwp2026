<?php
class ProfileController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/models/User.php';
        require_once dirname(__DIR__) . '/models/Post.php';
        require_once dirname(__DIR__) . '/helpers/bootstrap.php';

        $isPrettyProfileRoute = defined('DS_PRETTY_PROFILE_ROUTE') && DS_PRETTY_PROFILE_ROUTE;
        $requestedUsername = $_GET['username'] ?? null;
        $requestedUserId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $hasUsernameParameter = $isPrettyProfileRoute || array_key_exists('username', $_GET);
        $hasLegacyId = array_key_exists('id', $_GET);

        if ($hasUsernameParameter) {
            $viewer = is_string($requestedUsername) && $requestedUsername !== ''
                ? User::findByUsername($requestedUsername)
                : null;
            if ($viewer !== null && !$isPrettyProfileRoute) {
                $query = $_GET;
                unset($query['username'], $query['id']);
                $profileUrl = '@' . rawurlencode($viewer['username']);
                header('Location: ' . $profileUrl . ($query ? '?' . http_build_query($query) : ''), true, 301);
                exit;
            }
            $isOwnProfile = $viewer !== null
                && Auth::check()
                && (int) $viewer['id'] === (int) Auth::user()['id'];
        } elseif ($hasLegacyId) {
            $viewer = $requestedUserId !== false && $requestedUserId !== null && $requestedUserId > 0
                ? User::find($requestedUserId)
                : null;
            if ($viewer !== null) {
                $query = $_GET;
                unset($query['id']);
                $profileUrl = '@' . rawurlencode($viewer['username']);
                header('Location: ' . $profileUrl . ($query ? '?' . http_build_query($query) : ''), true, 301);
                exit;
            }
            $isOwnProfile = $viewer !== null
                && Auth::check()
                && (int) $viewer['id'] === (int) Auth::user()['id'];
        } else {
            Auth::requireLogin();
            $viewer = User::find((int) Auth::user()['id']);
            $isOwnProfile = true;
        }

        if (!$viewer) {
            if ($isOwnProfile) {
                Auth::logout();
                header('Location: index.php?route=login');
                exit;
            }

            http_response_code(404);
            exit('Profile not found.');
        }

        $blockState = Auth::check() && !$isOwnProfile
            ? User::getBlockState((int) Auth::user()['id'], (int) $viewer['id'])
            : ['blocked_by_viewer' => false, 'blocked_viewer' => false];
        $profileBlocked = $blockState['blocked_by_viewer'] || $blockState['blocked_viewer'];
        $user_posts = $profileBlocked
            ? []
            : array_map('ds_post_view', Post::findByUser((int) $viewer['id']));
        usort($user_posts, fn($a, $b) => ($b['profile_pinned'] <=> $a['profile_pinned']));
        $profile = ds_profile_view($viewer, count($user_posts));
        $isFollowingProfile = Auth::check()
            && !$isOwnProfile
            && !$profileBlocked
            && User::isFollowing((int) Auth::user()['id'], (int) $viewer['id']);
        $canManageProfile = Auth::check()
            && Auth::canModerate()
            && (int) Auth::user()['id'] !== (int) $viewer['id']
            && $viewer['role'] !== 'owner'
            && (Auth::role() === 'owner' || $viewer['role'] === 'user');

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('profile/show', compact(
            'profile',
            'viewer',
            'isOwnProfile',
            'blockState',
            'user_posts',
            'isFollowingProfile',
            'canManageProfile'
        ));
    }
}
