<?php
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/user-helpers.php';

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
    'user_id'       => (int) $row['user_id'],
    'username'      => $row['username'],
    'avatar_file'   => $row['avatar'] ?? 'default.png',
    'image_file'    => $row['filename'],
    'title'         => $row['title'] ?? ($row['caption'] ?? ''),
    'caption'       => $row['caption'] ?? '',
    'tags'          => [],
    'likes'         => (int) ($row['likes'] ?? 0),
    'liked_by_viewer' => !empty($row['liked_by_viewer']),
    'comment_count' => (int) ($row['comment_count'] ?? 0),
    'time'          => ds_time_ago($row['created_at']),
    'hot'           => !empty($row['hot_likes']),
    'front_pinned'  => !empty($row['front_pinned_at']),
    'profile_pinned' => !empty($row['profile_pinned_at']),
  ];
}

function ds_post_avatar_url(array $p, int $size = 96): string {
  return ds_user_avatar_url(['username' => $p['username'], 'avatar' => $p['avatar_file']], $size);
}

function ds_post_image_url(array $p): string {
  return 'uploads/posts/' . $p['image_file'];
}

function ds_photo_card($p, ?string $pinScope = null) {
  $img = ds_post_image_url($p);
  $avatar = ds_post_avatar_url($p, 60);
  $isOwnPost = Auth::check()
      && (int) $p['user_id'] === (int) Auth::user()['id'];
  $isLiked = !empty($p['liked_by_viewer']);
  $likeClass = $isLiked ? 'like-btn liked' : 'like-btn';
  $reportTarget = '@' . $p['username'] . "'s post";
  ?>
  <article class="photo-card reveal">
    <div class="photo-card-inner">
      <a href="post.php?id=<?= $p['id']; ?>" class="frame" style="display:block;">
        <?php if (!empty($p['hot'])): ?><span class="flame-badge">&#128293; HOT</span><?php endif; ?>
        <?php if ($pinScope === 'homepage' && !empty($p['front_pinned'])): ?><span class="pin-badge">&#128204; Pinned</span><?php endif; ?>
        <?php if ($pinScope === 'profile' && !empty($p['profile_pinned'])): ?><span class="pin-badge">&#128204; Pinned</span><?php endif; ?>
        <img src="<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($p['title']); ?>" loading="lazy">
      </a>
      <div class="photo-meta">
        <a href="<?= htmlspecialchars(ds_user_profile_url($p['username'])); ?>" class="who">
          <img src="<?= htmlspecialchars($avatar); ?>" alt="<?= htmlspecialchars($p['username']); ?>">
          <span>@<?= htmlspecialchars($p['username']); ?></span>
        </a>
        <a href="post.php?id=<?= $p['id']; ?>" style="color:inherit;">
          <p class="photo-title"><?= htmlspecialchars($p['title']); ?></p>
          <?php if ($p['caption'] !== ''): ?><p class="caption"><?= htmlspecialchars($p['caption']); ?></p><?php endif; ?>
        </a>
        <div class="stats">
          <?php if ($isOwnPost): ?>
            <span class="like-count-display" aria-label="<?= $p['likes']; ?> likes">
              <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
              <span class="like-count"><?= $p['likes']; ?></span>
            </span>
          <?php else: ?>
            <?php if (Auth::check()): ?>
              <button type="button" class="<?= $likeClass; ?>" data-like-url="includes/like.php" data-post-id="<?= $p['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-likes="<?= $p['likes']; ?>" data-liked="<?= $isLiked ? 'true' : 'false'; ?>" aria-pressed="<?= $isLiked ? 'true' : 'false'; ?>" aria-label="<?= $isLiked ? 'Unlike post' : 'Like post'; ?>">
            <?php else: ?>
              <button type="button" class="<?= $likeClass; ?>" data-likes="<?= $p['likes']; ?>">
            <?php endif; ?>
                <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
                <span class="like-count"><?= $p['likes']; ?></span>
              </button>
          <?php endif; ?>
          <span>&#128172; <?= $p['comment_count']; ?></span>
          <span><?= htmlspecialchars($p['time']); ?></span>
          <?php if (!$isOwnPost): ?>
            <button type="button" class="icon-flag" data-report-type="post" data-report-id="<?= (int) $p['id']; ?>" data-report-target="<?= htmlspecialchars($reportTarget); ?>" title="Report post" aria-label="Report post">&#128681;</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </article>
  <?php
}
