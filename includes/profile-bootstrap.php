<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
Auth::requireLogin();

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/helpers.php';

$viewer = User::find(Auth::user()['id']);
if (!$viewer) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

$demo_matches = array_values(array_filter($demo_posts, fn($p) => $p['username'] === $viewer['username']));
$real_matches = array_map('ds_post_view', Post::findByUser($viewer['id']));
$user_posts = array_merge($real_matches, $demo_matches);
usort($user_posts, fn($a, $b) => ($b['pinned'] <=> $a['pinned']));

$profile = ds_profile_view($viewer, count($user_posts));