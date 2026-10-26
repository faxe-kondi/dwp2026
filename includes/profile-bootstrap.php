<?php
require_once __DIR__ . '/Auth.php';
Auth::boot();
Auth::requireLogin();

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/helpers.php';

$viewer = User::find(Auth::user()['id']);
if (!$viewer) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

$user_posts = array_values(array_filter($demo_posts, fn($p) => $p['username'] === $viewer['username']));
usort($user_posts, fn($a, $b) => ($b['pinned'] <=> $a['pinned']));

$profile = ds_profile_view($viewer, count($user_posts));
