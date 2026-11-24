<?php
$avatar = ds_user_avatar_url($u, 140);
?>
<article class="user-card reveal"<?= $context === 'following' ? ' data-following-card' : ($context === 'blocked' ? ' data-blocked-card' : ''); ?>>
  <div class="user-card-inner">
    <a href="<?= htmlspecialchars(ds_user_profile_url($u['username'])); ?>" class="user-card-avatar">
      <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($u['username']); ?>">
    </a>
    <a href="<?= htmlspecialchars(ds_user_profile_url($u['username'])); ?>" class="user-card-name">@<?= htmlspecialchars($u['username']); ?></a>
    <p class="user-card-bio"><?= htmlspecialchars($u['bio'] ?? ''); ?></p>
    <div class="user-card-stats">
      <span><b><?= htmlspecialchars((string) ($u['posts'] ?? 0)); ?></b> posts</span>
      <span><b><?= htmlspecialchars((string) ($u['followers'] ?? 0)); ?></b> followers</span>
    </div>

    <?php if ($context === 'blocked'): ?>
      <button
        type="button"
        class="btn unblock-btn"
        data-block-url="controllers/BlockController.php"
        data-user-id="<?= (int) ($u['id'] ?? 0); ?>"
        data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
        data-blocked="true"
        data-block-label="@<?= htmlspecialchars($u['username']); ?>"
        data-unblock-label="@<?= htmlspecialchars($u['username']); ?>"
        style="width:100%; justify-content:center; padding:10px;"
      >Unblock</button>
    <?php elseif ($context !== 'search'): ?>
      <button
        type="button"
        class="btn follow-btn following"
        data-follow-url="controllers/FollowController.php"
        data-user-id="<?= (int) ($u['id'] ?? 0); ?>"
        data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
        data-following="true"
        data-following-label="Following"
        data-follow-label="+ Follow"
        data-following-hover-label="Unfollow"
        aria-pressed="true"
        style="width:100%; justify-content:center; padding:10px;"
      >Following</button>
    <?php endif; ?>
  </div>
</article>
