<?php
require __DIR__ . '/includes/profile-bootstrap.php';
$active = 'profile';
$page_title = $profile['display_name'];
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
