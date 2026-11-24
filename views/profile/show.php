<?php
$active = 'profile';
$page_title = $profile['display_name'];
View::render('layout/header', get_defined_vars());
?>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap">
  <?php $profile_tab = 'posts'; View::render('components/profile-header', get_defined_vars()); ?>
  <?php if (($_GET['moderation'] ?? '') === 'banned'): ?><div class="form-success" role="status">User banned.</div><?php elseif (($_GET['moderation'] ?? '') === 'unbanned'): ?><div class="form-success" role="status">User unbanned.</div><?php endif; ?>
  <?php if (isset($_GET['post_deleted']) && $_GET['post_deleted'] === '1'): ?>
    <?php if (isset($_GET['cleanup_failed']) && $_GET['cleanup_failed'] === '1'): ?><div class="form-errors"><ul><li>The post was deleted, but its uploaded image could not be removed.</li></ul></div><?php else: ?><div class="form-success">Post deleted.</div><?php endif; ?>
  <?php endif; ?>
  <?php if (!$isOwnProfile && $blockState['blocked_by_viewer']): ?>
    <div class="empty-state profile-blocked-state"><p>You blocked this user. Unblock them to see their content.</p><button type="button" class="btn unblock-btn" data-block-url="controllers/BlockController.php" data-user-id="<?= (int) $viewer['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-blocked="true" data-unblock-label="@<?= htmlspecialchars($profile['username']); ?>">Unblock user</button></div>
  <?php elseif (!$isOwnProfile && $blockState['blocked_viewer']): ?>
    <div class="empty-state profile-blocked-state">You can't see this user's content.</div>
  <?php elseif (empty($user_posts)): ?>
    <div class="empty-state">No posts yet.</div>
  <?php else: ?>
    <div class="photo-grid"><?php foreach ($user_posts as $p) View::render('components/post-card', ['p' => $p, 'pinScope' => 'profile']); ?></div>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
