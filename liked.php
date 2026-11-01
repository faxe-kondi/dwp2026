<?php
require __DIR__ . '/includes/profile-bootstrap.php';

$real_liked_posts = array_map('ds_post_view', Post::findLikedByUser((int) Auth::user()['id']));
$liked_posts = $real_liked_posts;

$active = 'profile';
$page_title = 'Your Likes';
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'likes'; include __DIR__ . '/includes/profile-header.php'; ?>

    <?php if (empty($liked_posts)): ?>
      <div class="empty-state">No likes yet &mdash; go heart a few fits.</div>
    <?php else: ?>
      <div class="photo-grid">
        <?php foreach ($liked_posts as $p) ds_photo_card($p, true); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
