<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';

$active = 'profile';
$page_title = 'Following';
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'following'; include __DIR__ . '/includes/profile-header.php'; ?>

    <?php if (empty($demo_following)): ?>
      <div class="empty-state">Not following anyone yet.</div>
    <?php else: ?>
      <div class="user-grid">
        <?php foreach ($demo_following as $i => $u) ds_user_card($u, $i, 'following'); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
