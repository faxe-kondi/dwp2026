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
