<?php
$active = 'profile';
$page_title = 'Your Likes';
View::render('layout/header', get_defined_vars());
?>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap">
  <?php $profile_tab = 'likes'; View::render('components/profile-header', get_defined_vars()); ?>
  <?php if (empty($liked_posts)): ?>
    <div class="empty-state">No likes yet &mdash; go heart a few fits.</div>
  <?php else: ?>
    <div class="photo-grid"><?php foreach ($liked_posts as $p) View::render('components/post-card', ['p' => $p, 'pinScope' => null]); ?></div>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
