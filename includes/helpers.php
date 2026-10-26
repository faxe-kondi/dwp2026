<?php
function ds_profile_view(array $user, int $postCount): array {
  return [
    'display_name' => ucwords(str_replace(['.', '_'], ' ', $user['username'])),
    'username'      => $user['username'],
    'avatar_seed'   => $user['username'],
    'bio'           => $user['bio'],
    'joined'        => 'Member since ' . date('M Y', strtotime($user['created_at'])),
    'stats' => [
      'posts'      => $postCount,
      'followers'  => 0,
      'following'  => 0,
    ],
  ];
}

function ds_photo_card($p, $liked = false) {
  $img = ds_photo_url($p['img_seed']);
  $avatar = ds_avatar_url($p['avatar_seed'], 60);
  $likeClass = $liked ? 'like-btn liked' : 'like-btn';
  $reportTarget = '@' . $p['username'] . "'s post";
  ?>
  <article class="photo-card reveal">
    <div class="photo-card-inner">
      <a href="post.php?id=<?= $p['id']; ?>" class="frame" style="display:block;">
        <?php if ($p['hot']): ?><span class="flame-badge">&#128293; HOT</span><?php endif; ?>
        <?php if (!empty($p['pinned'])): ?><span class="pin-badge">&#128204; Pinned</span><?php endif; ?>
        <img src="<?= $img; ?>" alt="<?= htmlspecialchars($p['caption']); ?>" loading="lazy">
      </a>
      <div class="photo-meta">
        <a href="profile.php" class="who">
          <img src="<?= $avatar; ?>" alt="<?= htmlspecialchars($p['username']); ?>">
          <span>@<?= htmlspecialchars($p['username']); ?></span>
        </a>
        <a href="post.php?id=<?= $p['id']; ?>" style="color:inherit;">
          <p class="caption"><?= htmlspecialchars($p['caption']); ?></p>
        </a>
        <div class="stats">
          <button class="<?= $likeClass; ?>" data-likes="<?= $p['likes']; ?>">&#9825; <span class="like-count"><?= $p['likes']; ?></span></button>
          <span>&#128172; <?= $p['comment_count']; ?></span>
          <span><?= $p['time']; ?></span>
          <button class="icon-flag" data-report-type="post" data-report-target="<?= htmlspecialchars($reportTarget); ?>" title="Report post" aria-label="Report post">&#128681;</button>
        </div>
      </div>
    </div>
  </article>
  <?php
}

/**
 * $context: 'following' (default) shows Follow/Following + a Report/Block
 * menu. 'blocked' shows an Unblock button and the stored block reason
 * instead — used on blocked.php so a block is always easy to undo.
 */
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
