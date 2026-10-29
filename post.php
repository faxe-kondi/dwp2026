<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Csrf.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Post.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
$realRow = Post::findById($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    Csrf::verifyOrFail();
    $currentUser = Auth::user();

    if (!$realRow || !$currentUser || (int) $realRow['user_id'] !== (int) $currentUser['id']) {
        http_response_code(404);
        exit('Post not found.');
    }

    try {
        $deletedFilename = Post::deleteOwned($id, (int) $currentUser['id']);
    } catch (PDOException $e) {
        error_log('Post deletion failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Something went wrong deleting your post. Please try again.');
    }
    if ($deletedFilename === null) {
        http_response_code(404);
        exit('Post not found.');
    }

    $imagePath = __DIR__ . '/uploads/posts/' . basename($deletedFilename);
    if (basename($deletedFilename) !== $deletedFilename || (is_file($imagePath) && !unlink($imagePath))) {
        error_log('Post image cleanup failed after deleting post #' . $id . '.');
        header('Location: profile.php?post_deleted=1&cleanup_failed=1');
        exit;
    }

    header('Location: profile.php?post_deleted=1');
    exit;
}

if ($realRow) {
    $post = ds_post_view($realRow);
} else {
    $post = null;
    foreach ($demo_posts as $p) {
        if ($p['id'] === $id) { $post = $p; break; }
    }
    if (!$post) { $post = $demo_posts[0]; }
}

$currentUser = Auth::user();
$isOwner = !empty($post['is_real'])
    && $currentUser
    && (int) $realRow['user_id'] === (int) $currentUser['id'];

$post_comments = !empty($post['is_real'])
    ? []
    : array_values(array_filter($demo_comments, fn($c) => $c['post_id'] === $post['id']));

$active = '';
$page_title = '@' . $post['username'] . "'s post";
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <span class="ghost-num" aria-hidden="true">#<?= $post['id']; ?></span>
    <div class="crumb"><a href="index.php">Home</a> / Post #<?= $post['id']; ?></div>
    <h1>@<?= htmlspecialchars($post['username']); ?></h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap post-layout">

    <div class="post-image">
      <img src="<?= htmlspecialchars(ds_post_image_url($post, 900, 1125)); ?>" alt="<?= htmlspecialchars($post['caption']); ?>">
    </div>

    <div class="post-details">
      <div class="uploader-card">
        <img src="<?= htmlspecialchars(ds_post_avatar_url($post, 104)); ?>" alt="<?= htmlspecialchars($post['username']); ?>">
        <div>
          <div class="name"><?= htmlspecialchars(ucwords(str_replace(['.', '_'], ' ', $post['username']))); ?></div>
          <div class="handle">@<?= htmlspecialchars($post['username']); ?> &middot; posted <?= $post['time']; ?></div>
        </div>
        <a href="profile.php" class="btn"><?= $isOwner ? 'My profile' : 'View profile'; ?></a>
        <div class="action-menu">
          <button class="action-menu-trigger" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="uploaderMenu" aria-label="<?= $isOwner ? 'Post options' : 'More options for @' . htmlspecialchars($post['username']); ?>">&#8942;</button>
          <div class="action-menu-list" id="uploaderMenu">
            <?php if ($isOwner): ?>
              <a class="action-menu-item" href="edit-post.php?id=<?= $post['id']; ?>">Edit post</a>
              <form method="post" action="post.php?id=<?= $post['id']; ?>" onsubmit="return confirm('Delete this post? This cannot be undone.');">
                <?= Csrf::field(); ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="action-menu-item action-menu-item--danger">Delete post</button>
              </form>
            <?php else: ?>
              <button type="button" class="action-menu-item" data-report-type="user" data-report-target="@<?= htmlspecialchars($post['username']); ?>">Report user</button>
              <button type="button" class="action-menu-item action-menu-item--danger" data-block-target="<?= htmlspecialchars($post['username']); ?>" data-block-label="@<?= htmlspecialchars($post['username']); ?>">Block user</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <p class="post-caption"><?= htmlspecialchars($post['caption']); ?></p>

      <?php if (!empty($post['tags'])): ?>
        <div class="post-tags">
          <?php foreach ($post['tags'] as $tag): ?>
            <span class="chip">#<?= htmlspecialchars($tag); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="post-stats-row">
        <?php if (!$isOwner): ?>
          <button class="like-btn" data-likes="<?= $post['likes']; ?>">&#9825; <span class="like-count"><?= $post['likes']; ?></span> likes</button>
        <?php endif; ?>
        <span>&#128172; <?= count($post_comments); ?> comments</span>
        <span><?= $post['hot'] ? '&#128293; trending' : 'in the feed'; ?></span>
        <?php if (!$isOwner): ?>
          <button class="icon-flag" data-report-type="post" data-report-target="@<?= htmlspecialchars($post['username']); ?>'s post" title="Report post" aria-label="Report post" style="margin-left:auto;">&#128681; Report</button>
        <?php endif; ?>
      </div>

      <div class="comment-thread">
        <h3>Comments</h3>

        <?php if (empty($post_comments)): ?>
          <p style="color:var(--steel); font-size:14px;">No comments yet on this post &mdash; be the first.</p>
        <?php else: ?>
          <?php foreach ($post_comments as $c): ?>
            <div class="thread-item">
              <img src="<?= ds_avatar_url($c['avatar_seed'], 38); ?>" alt="<?= htmlspecialchars($c['username']); ?>">
              <div>
                <span class="who">@<?= htmlspecialchars($c['username']); ?></span><time><?= $c['time']; ?></time>
                <button class="icon-flag icon-flag--inline" data-report-type="comment" data-report-target="@<?= htmlspecialchars($c['username']); ?>'s comment" title="Report comment" aria-label="Report comment">&#128681;</button>
                <p><?= htmlspecialchars($c['text']); ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <form class="comment-form" data-proto>
          <input type="text" placeholder="Add a comment...">
          <button type="submit" class="btn btn-solid">Post</button>
        </form>
        <p class="field-hint proto-submit-note" style="margin-top:8px;"></p>
      </div>
    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>