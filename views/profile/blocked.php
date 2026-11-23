<?php
$active = 'profile';
$page_title = 'Blocked Users';
View::render('layout/header', get_defined_vars());
?>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap">
  <?php $profile_tab = 'blocked'; View::render('components/profile-header', get_defined_vars()); ?>
  <p class="proto-note" style="margin-top:-18px; margin-bottom:34px;">Blocked users stay here until you unblock them &mdash; blocking never deletes anyone for good.</p>
  <?php if (empty($blocked_users)): ?>
    <div class="empty-state">You haven't blocked anyone.</div>
  <?php else: ?>
    <div class="user-grid"><?php foreach ($blocked_users as $i => $u) View::render('components/user-card', ['u' => $u, 'context' => 'blocked']); ?></div>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
