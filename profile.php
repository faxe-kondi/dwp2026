<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/helpers.php';

$requestedUserId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$isOwnProfile = $requestedUserId === false
    || $requestedUserId === null
    || $requestedUserId < 1
    || (Auth::check() && $requestedUserId === (int) Auth::user()['id']);

if ($isOwnProfile) {
    Auth::requireLogin();
    $viewer = User::find((int) Auth::user()['id']);
} else {
    $viewer = User::find($requestedUserId);
}

if (!$viewer) {
    if ($isOwnProfile) {
        Auth::logout();
        header('Location: login.php');
        exit;
    }

    http_response_code(404);
    exit('Profile not found.');
}

$demo_matches = $isOwnProfile
    ? array_values(array_filter($demo_posts, fn($p) => $p['username'] === $viewer['username']))
    : [];
$real_matches = array_map('ds_post_view', Post::findByUser((int) $viewer['id']));
$user_posts = array_merge($real_matches, $demo_matches);
usort($user_posts, fn($a, $b) => ($b['pinned'] <=> $a['pinned']));
$profile = ds_profile_view($viewer, count($user_posts));

$active = 'profile';
$page_title = $profile['display_name'];
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'posts'; include __DIR__ . '/includes/profile-header.php'; ?>

    <?php if (isset($_GET['post_deleted']) && $_GET['post_deleted'] === '1'): ?>
      <?php if (isset($_GET['cleanup_failed']) && $_GET['cleanup_failed'] === '1'): ?>
        <div class="form-errors"><ul><li>The post was deleted, but its uploaded image could not be removed.</li></ul></div>
      <?php else: ?>
        <div class="form-success">Post deleted.</div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($user_posts)): ?>
      <div class="empty-state">No posts yet.</div>
    <?php else: ?>
      <div class="photo-grid">
        <?php foreach ($user_posts as $p) ds_photo_card($p); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
