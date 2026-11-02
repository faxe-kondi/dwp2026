<?php
function ds_user_avatar_url(array $user, int $size = 256): string {
  $avatar = $user['avatar'] ?? 'default.png';
  if ($avatar && $avatar !== 'default.png') {
    return 'uploads/avatars/' . $avatar;
  }
  $defaultAvatarPath = __DIR__ . '/../uploads/avatars/default.png';
  $version = is_file($defaultAvatarPath) ? filemtime($defaultAvatarPath) : false;
  return 'uploads/avatars/default.png' . ($version !== false ? '?v=' . $version : '');
}

function ds_profile_view(array $user, int $postCount): array {
  return [
    'display_name' => $user['username'],
    'username'      => $user['username'],
    'avatar_url'    => ds_user_avatar_url($user, 256),
    'bio'           => $user['bio'],
    'joined'        => 'Member since ' . date('M Y', strtotime($user['created_at'])),
    'stats' => [
      'posts'      => $postCount,
      'followers'  => 0,
      'following'  => 0,
    ],
  ];
}

function ds_user_card($u, $index = 0, $context = 'following') {
  $avatar = isset($u['avatar'])
    ? ds_user_avatar_url($u, 140)
    : ds_avatar_url($u['avatar_seed'], 140);
  ?>
  <article class="user-card reveal"<?= $context === 'following' ? ' data-following-card' : ($context === 'blocked' ? ' data-blocked-card' : ''); ?>>
    <div class="user-card-inner">
      <a href="profile.php?id=<?= (int) ($u['id'] ?? 0); ?>" class="user-card-avatar">
        <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($u['username']); ?>">
      </a>
      <a href="profile.php?id=<?= (int) ($u['id'] ?? 0); ?>" class="user-card-name">@<?= htmlspecialchars($u['username']); ?></a>
      <p class="user-card-bio"><?= htmlspecialchars($u['bio']); ?></p>
      <div class="user-card-stats">
        <span><b><?= htmlspecialchars((string) $u['posts']); ?></b> posts</span>
        <span><b><?= htmlspecialchars((string) $u['followers']); ?></b> followers</span>
      </div>

      <?php if ($context === 'blocked'): ?>
        <button
          type="button"
          class="btn unblock-btn"
          data-block-url="includes/block.php"
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
          data-follow-url="includes/follow.php"
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
  <?php
}
