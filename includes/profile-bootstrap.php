<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
Auth::requireLogin();
$isOwnProfile = true;

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/helpers.php';

$viewer = User::find(Auth::user()['id']);
if (!$viewer) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

$user_posts = array_map('ds_post_view', Post::findByUser((int) $viewer['id']));
usort($user_posts, fn($a, $b) => ($b['profile_pinned'] <=> $a['profile_pinned']));

$profile = ds_profile_view($viewer, count($user_posts));