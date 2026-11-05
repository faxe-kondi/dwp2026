<?php
require __DIR__ . '/includes/profile-bootstrap.php';
$blocked_users = User::findBlocked((int) $viewer['id']);

$active = 'profile';
$page_title = 'Blocked Users';
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'blocked'; include __DIR__ . '/includes/profile-header.php'; ?>

    <p class="proto-note" style="margin-top:-18px; margin-bottom:34px;">Blocked users stay here until you unblock them &mdash; blocking never deletes anyone for good.</p>

    <?php if (empty($blocked_users)): ?>
      <div class="empty-state">You haven't blocked anyone.</div>
    <?php else: ?>
      <div class="user-grid">
        <?php foreach ($blocked_users as $i => $u) ds_user_card($u, $i, 'blocked'); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
