<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';

$user_posts = array_values(array_filter($demo_posts, fn($p) => $p['username'] === $demo_profile['username']));

// Pinned posts float to the top of the regular grid rather than living
// in a separate tab — usort is stable in PHP 8+, so posts that are
// equally pinned (or equally not) keep their original relative order.
usort($user_posts, fn($a, $b) => ($b['pinned'] <=> $a['pinned']));

$active = 'profile';
$page_title = $demo_profile['display_name'];
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'posts'; include __DIR__ . '/includes/profile-header.php'; ?>

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
