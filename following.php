<?php
require __DIR__ . '/includes/profile-bootstrap.php';
$following_users = User::findFollowing((int) $viewer['id']);
$active = 'profile';
$page_title = 'Following';
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'following'; include __DIR__ . '/includes/profile-header.php'; ?>

    <?php if (empty($following_users)): ?>
      <div class="empty-state">Not following anyone yet.</div>
    <?php else: ?>
      <div class="user-grid">
        <?php foreach ($following_users as $i => $u) ds_user_card($u, $i, 'following'); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
