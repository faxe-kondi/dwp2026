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
    'display_name' => ucwords(str_replace(['.', '_'], ' ', $user['username'])),
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

function ds_time_ago(string $datetime): string {
  $diff = time() - strtotime($datetime);
  if ($diff < 60) { return 'just now'; }
  if ($diff < 3600) { return floor($diff / 60) . 'm ago'; }
  if ($diff < 86400) { return floor($diff / 3600) . 'h ago'; }
  if ($diff < 604800) { return floor($diff / 86400) . 'd ago'; }
  return date('M j', strtotime($datetime));
}

function ds_post_view(array $row): array {
  return [
    'id'            => (int) $row['id'],
    'username'      => $row['username'],
    'avatar_file'   => $row['avatar'] ?? 'default.png',
    'image_file'    => $row['filename'],
    'caption'       => $row['caption'] ?? '',
    'tags'          => [],
    'likes'         => 0,
    'comment_count' => 0,
    'time'          => ds_time_ago($row['created_at']),
    'hot'           => false,
    'pinned'        => !empty($row['profile_pinned_at']),
    'is_real'       => true,
  ];
}

function ds_post_avatar_url(array $p, int $size = 96): string {
  if (!empty($p['is_real'])) {
    return ds_user_avatar_url(['username' => $p['username'], 'avatar' => $p['avatar_file']], $size);
  }
  return ds_avatar_url($p['avatar_seed'], $size);
}

function ds_post_image_url(array $p, int $w = 800, int $h = 1000): string {
  if (!empty($p['is_real'])) {
    return 'uploads/posts/' . $p['image_file'];
  }
  return ds_photo_url($p['img_seed'], $w, $h);
}

function ds_photo_card($p, $liked = false) {
  $img = ds_post_image_url($p);
  $avatar = ds_post_avatar_url($p, 60);
  $likeClass = $liked ? 'like-btn liked' : 'like-btn';
  $reportTarget = '@' . $p['username'] . "'s post";
  ?>
  <article class="photo-card reveal">
    <div class="photo-card-inner">
      <a href="post.php?id=<?= $p['id']; ?>" class="frame" style="display:block;">
        <?php if (!empty($p['hot'])): ?><span class="flame-badge">&#128293; HOT</span><?php endif; ?>
        <?php if (!empty($p['pinned'])): ?><span class="pin-badge">&#128204; Pinned</span><?php endif; ?>
        <img src="<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($p['caption']); ?>" loading="lazy">
      </a>
      <div class="photo-meta">
        <a href="profile.php" class="who">
          <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($p['username']); ?>">
          <span>@<?= htmlspecialchars($p['username']); ?></span>
        </a>
        <a href="post.php?id=<?= $p['id']; ?>" style="color:inherit;">
          <p class="caption"><?= htmlspecialchars($p['caption']); ?></p>
        </a>
        <div class="stats">
          <button class="<?= $likeClass; ?>" data-likes="<?= $p['likes']; ?>">&#9825; <span class="like-count"><?= $p['likes']; ?></span></button>
          <span>&#128172; <?= $p['comment_count']; ?></span>
          <span><?= htmlspecialchars($p['time']); ?></span>
          <button class="icon-flag" data-report-type="post" data-report-target="<?= htmlspecialchars($reportTarget); ?>" title="Report post" aria-label="Report post">&#128681;</button>
        </div>
      </div>
    </div>
  </article>
  <?php
}

function ds_user_card($u, $index = 0, $context = 'following') {
  $avatar = ds_avatar_url($u['avatar_seed'], 140);
  $menuId = 'menu-' . preg_replace('/[^a-z0-9]+/i', '-', $u['username']) . '-' . $index;
  ?>
  <article class="user-card reveal">
    <div class="user-card-inner">
      <?php if ($context === 'following'): ?>
        <div class="action-menu">
          <button class="action-menu-trigger" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="<?= $menuId; ?>" aria-label="More options for @<?= htmlspecialchars($u['username']); ?>">&#8942;</button>
          <div class="action-menu-list" id="<?= $menuId; ?>">
            <button type="button" class="action-menu-item" data-report-type="user" data-report-target="@<?= htmlspecialchars($u['username']); ?>">Report user</button>
            <button type="button" class="action-menu-item action-menu-item--danger" data-block-target="<?= htmlspecialchars($u['username']); ?>" data-block-label="@<?= htmlspecialchars($u['username']); ?>">Block user</button>
          </div>
        </div>
      <?php endif; ?>

      <a href="profile.php" class="user-card-avatar">
        <img src="<?= $avatar; ?>" alt="<?= htmlspecialchars($u['username']); ?>">
      </a>
      <a href="profile.php" class="user-card-name">@<?= htmlspecialchars($u['username']); ?></a>
      <p class="user-card-bio"><?= htmlspecialchars($u['bio']); ?></p>
      <div class="user-card-stats">
        <span><b><?= htmlspecialchars((string) $u['posts']); ?></b> posts</span>
        <span><b><?= htmlspecialchars((string) $u['followers']); ?></b> followers</span>
      </div>

      <?php if ($context === 'blocked'): ?>
        <?php if (!empty($u['blocked_reason'])): ?>
          <p class="user-card-blocked-reason">Blocked &mdash; <?= htmlspecialchars($u['blocked_reason']); ?></p>
        <?php endif; ?>
        <button type="button" class="btn unblock-btn" data-unblock-target="<?= htmlspecialchars($u['username']); ?>" data-unblock-label="@<?= htmlspecialchars($u['username']); ?>" style="width:100%; justify-content:center; padding:10px;">Unblock</button>
      <?php else: ?>
        <button type="button" class="btn follow-btn following" data-following-label="Following" data-follow-label="+ Follow" style="width:100%; justify-content:center; padding:10px;">Following</button>
      <?php endif; ?>
    </div>
  </article>
  <?php
}