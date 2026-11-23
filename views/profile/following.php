<?php
$active = 'profile';
$page_title = 'Following';
View::render('layout/header', get_defined_vars());
?>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap">
  <?php $profile_tab = 'following'; View::render('components/profile-header', get_defined_vars()); ?>
  <?php if (empty($following_users)): ?>
    <div class="empty-state">Not following anyone yet.</div>
  <?php else: ?>
    <div class="user-grid"><?php foreach ($following_users as $i => $u) View::render('components/user-card', ['u' => $u, 'context' => 'following']); ?></div>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
