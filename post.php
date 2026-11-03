<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Csrf.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/Comment.php';
require_once __DIR__ . '/includes/User.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
$currentUser = Auth::user();
$realRow = Post::findById($id, $currentUser ? (int) $currentUser['id'] : null);
$blockedPostState = $realRow && $currentUser
    ? User::getBlockState((int) $currentUser['id'], (int) $realRow['user_id'])
    : ['blocked_by_viewer' => false, 'blocked_viewer' => false];
$postBlockedForViewer = $blockedPostState['blocked_by_viewer'] || $blockedPostState['blocked_viewer'];
if (
    $blockedPostState['blocked_viewer']
    || ($blockedPostState['blocked_by_viewer'] && $_SERVER['REQUEST_METHOD'] === 'POST')
) {
    http_response_code(404);
    $active = '';
    $page_title = 'Post unavailable';
    include __DIR__ . '/includes/header.php';
    ?>
    <section class="page-body blocked-post-page">
      <div class="wrap">
        <div class="blocked-post-notice" role="status">
          <h1>Post unavailable</h1>
          <p>This post is unavailable.</p>
          <a class="btn btn-solid" href="index.php">Return to home</a>
        </div>
      </div>
    </section>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [];
$commentText = '';
$editingCommentId = null;

$requestedEditCommentId = filter_var($_GET['edit_comment'] ?? null, FILTER_VALIDATE_INT);
if ($requestedEditCommentId !== false && $requestedEditCommentId !== null && $requestedEditCommentId > 0) {
    $editingCommentId = $requestedEditCommentId;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
    Auth::requireLogin();
    Csrf::verifyOrFail();

    if (!$realRow) {
        http_response_code(404);
        exit('Post not found.');
    }

    $submittedComment = $_POST['content'] ?? '';
    if (!is_string($submittedComment)) {
        $errors[] = 'Enter a valid comment.';
    } else {
        $commentText = trim($submittedComment);
        try {
            Comment::create((int) $realRow['id'], (int) Auth::user()['id'], $commentText);
            header('Location: post.php?id=' . (int) $realRow['id'] . '&comment=added#comments');
            exit;
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Comment creation failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong posting your comment. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_comment') {
    Auth::requireLogin();
    Csrf::verifyOrFail();

    if (!$realRow) {
        http_response_code(404);
        exit('Post not found.');
    }

    $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($commentId === false || $commentId === null || $commentId < 1) {
        http_response_code(404);
        exit('Comment not found.');
    }

    $editingCommentId = $commentId;
    $submittedComment = $_POST['content'] ?? '';
    if (!is_string($submittedComment)) {
        $errors[] = 'Enter a valid comment.';
    } else {
        $commentText = trim($submittedComment);
        try {
            if (!Comment::updateOwned(
                $commentId,
                (int) $realRow['id'],
                (int) Auth::user()['id'],
                $commentText
            )) {
                http_response_code(404);
                exit('Comment not found.');
            }
            header('Location: post.php?id=' . (int) $realRow['id'] . '&comment=updated#comments');
            exit;
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Comment update failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong updating your comment. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_comment') {
    Auth::requireLogin();
    Csrf::verifyOrFail();

    if (!$realRow) {
        http_response_code(404);
        exit('Post not found.');
    }

    $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($commentId === false || $commentId === null || $commentId < 1) {
        http_response_code(404);
        exit('Comment not found.');
    }

    try {
        if (!Comment::deleteOwned($commentId, (int) $realRow['id'], (int) Auth::user()['id'])) {
            http_response_code(404);
            exit('Comment not found.');
        }
    } catch (PDOException $e) {
        error_log('Comment deletion failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Something went wrong deleting your comment. Please try again.');
    }

    header('Location: post.php?id=' . (int) $realRow['id'] . '&comment=deleted#comments');
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

$isOwner = !empty($post['is_real'])
    && $currentUser
    && (int) $realRow['user_id'] === (int) $currentUser['id'];
$isFollowingPoster = !empty($post['is_real'])
    && $currentUser
    && !$isOwner
    && !$postBlockedForViewer
    && User::isFollowing((int) $currentUser['id'], (int) $realRow['user_id']);

if (!empty($post['is_real'])) {
    $post_comments = $blockedPostState['blocked_by_viewer'] ? [] : array_map(
        static function (array $comment): array {
            return [
                'id' => (int) $comment['id'],
                'username' => $comment['username'],
                'user_id' => (int) $comment['user_id'],
                'avatar_url' => ds_user_avatar_url([
                    'username' => $comment['username'],
                    'avatar' => $comment['avatar'],
                ], 38),
                'time' => ds_time_ago($comment['created_at']),
                'content' => $comment['content'],
                'text' => $comment['content'],
            ];
        },
        Comment::findByPost((int) $post['id'])
    );
} else {
    $post_comments = array_map(
        static function (array $comment): array {
            $comment['avatar_url'] = ds_avatar_url($comment['avatar_seed'], 38);
            return $comment;
        },
        array_values(array_filter($demo_comments, fn($comment) => $comment['post_id'] === $post['id']))
    );
}

$active = '';
$page_title = $post['title'] . ' - @' . $post['username'];
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

    <?php if ($blockedPostState['blocked_by_viewer']): ?>
      <div class="blocked-post-notice profile-blocked-state" role="status">
        <h1>User blocked</h1>
        <p>Posts from this user will no longer be shown on your feed.</p>
        <p><a class="btn btn-solid" href="blocked.php">Manage blocked users</a></p>
        <button
          type="button"
          class="btn unblock-btn"
          data-block-url="includes/block.php"
          data-user-id="<?= (int) $realRow['user_id']; ?>"
          data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
          data-blocked="true"
          data-unblock-label="@<?= htmlspecialchars($post['username']); ?>"
        >Unblock user</button>
      </div>
    <?php else: ?>
    <div class="post-image">
      <img src="<?= htmlspecialchars(ds_post_image_url($post, 900, 1125)); ?>" alt="<?= htmlspecialchars($post['title']); ?>">
    </div>

    <div class="post-details">
      <div class="uploader-card">
        <a class="uploader-profile" href="<?= !empty($post['is_real']) ? 'profile.php?id=' . (int) $post['user_id'] : 'profile.php'; ?>">
          <img src="<?= htmlspecialchars(ds_post_avatar_url($post, 104)); ?>" alt="">
          <span class="name"><?= htmlspecialchars($post['username']); ?></span>
        </a>
        <?php if (!empty($post['is_real']) && !$isOwner): ?>
          <?php if ($currentUser): ?>
            <button
              type="button"
              class="btn follow-btn<?= $isFollowingPoster ? ' following' : ''; ?>"
              data-follow-url="includes/follow.php"
              data-user-id="<?= (int) $realRow['user_id']; ?>"
              data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
              data-following="<?= $isFollowingPoster ? 'true' : 'false'; ?>"
              data-following-label="Following"
              data-follow-label="+ Follow"
              aria-pressed="<?= $isFollowingPoster ? 'true' : 'false'; ?>"
            ><?= $isFollowingPoster ? 'Following' : '+ Follow'; ?></button>
          <?php else: ?>
            <a class="btn" href="login.php">Log in to follow</a>
          <?php endif; ?>
        <?php endif; ?>
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
              <?php if (!empty($post['is_real'])): ?>
                <button type="button" class="action-menu-item" data-report-type="user" data-report-id="<?= (int) $realRow['user_id']; ?>" data-report-target="@<?= htmlspecialchars($post['username']); ?>">Report user</button>
              <?php endif; ?>
              <?php if (!empty($post['is_real']) && $currentUser): ?>
                <button type="button" class="action-menu-item action-menu-item--danger" data-block-url="includes/block.php" data-user-id="<?= (int) $realRow['user_id']; ?>" data-post-id="<?= (int) $post['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-blocked="false" data-block-label="@<?= htmlspecialchars($post['username']); ?>">Block user</button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <h2 class="post-title"><?= htmlspecialchars($post['title']); ?></h2>
      <?php if ($post['caption'] !== ''): ?>
        <p class="post-caption" id="postCaption"><?= htmlspecialchars($post['caption']); ?></p>
        <button class="caption-toggle" type="button" aria-controls="postCaption" aria-expanded="false" hidden>Show more</button>
      <?php endif; ?>

      <?php if (!empty($post['tags'])): ?>
        <div class="post-tags">
          <?php foreach ($post['tags'] as $tag): ?>
            <span class="chip">#<?= htmlspecialchars($tag); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="post-stats-row">
        <?php if ($isOwner): ?>
          <span class="like-count-display" aria-label="<?= $post['likes']; ?> likes">
            <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
            <span class="like-count"><?= $post['likes']; ?></span>
          </span>
        <?php else: ?>
          <?php if (!empty($post['is_real']) && Auth::check()): ?>
            <button type="button" class="like-btn<?= !empty($post['liked_by_viewer']) ? ' liked' : ''; ?>" data-like-url="includes/like.php" data-post-id="<?= $post['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-likes="<?= $post['likes']; ?>" data-liked="<?= !empty($post['liked_by_viewer']) ? 'true' : 'false'; ?>" aria-pressed="<?= !empty($post['liked_by_viewer']) ? 'true' : 'false'; ?>" aria-label="<?= !empty($post['liked_by_viewer']) ? 'Unlike post' : 'Like post'; ?>">
          <?php elseif (!empty($post['is_real'])): ?>
            <a class="like-btn" href="login.php" aria-label="Log in to like this post">
          <?php else: ?>
            <button type="button" class="like-btn" data-likes="<?= $post['likes']; ?>">
          <?php endif; ?>
              <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
              <span class="like-count"><?= $post['likes']; ?></span>
              <span class="like-label"><?= !empty($post['liked_by_viewer']) ? 'Liked' : 'Like'; ?></span>
          <?php if (!empty($post['is_real']) && !Auth::check()): ?>
            </a>
          <?php else: ?>
            </button>
          <?php endif; ?>
        <?php endif; ?>
        <span>&#128172; <?= count($post_comments); ?> comments</span>
        <span><?= $post['hot'] ? '&#128293; trending' : 'in the feed'; ?></span>
        <?php if (!empty($post['is_real']) && !$isOwner): ?>
          <button type="button" class="icon-flag" data-report-type="post" data-report-id="<?= (int) $post['id']; ?>" data-report-target="@<?= htmlspecialchars($post['username']); ?>'s post" title="Report post" aria-label="Report post" style="margin-left:auto;">&#128681; Report</button>
        <?php endif; ?>
      </div>

      <div class="comment-thread">
        <h3 id="comments">Comments</h3>

        <?php if ($errors): ?>
          <div class="form-errors">
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif (($_GET['comment'] ?? '') === 'added'): ?>
          <div class="form-success">Comment posted.</div>
        <?php elseif (($_GET['comment'] ?? '') === 'deleted'): ?>
          <div class="form-success">Comment deleted.</div>
        <?php elseif (($_GET['comment'] ?? '') === 'updated'): ?>
          <div class="form-success">Comment updated.</div>
        <?php endif; ?>

        <div class="comments-list" role="region" aria-label="Post comments" tabindex="0">
          <?php if (empty($post_comments)): ?>
            <p class="comments-empty">No comments yet on this post &mdash; be the first.</p>
          <?php else: ?>
            <?php foreach ($post_comments as $commentIndex => $c): ?>
              <div class="thread-item">
                <?php if (!empty($c['user_id'])): ?>
                  <a class="comment-author-avatar" href="profile.php?id=<?= (int) $c['user_id']; ?>" aria-label="View @<?= htmlspecialchars($c['username']); ?>'s profile">
                    <img src="<?= htmlspecialchars($c['avatar_url']); ?>" alt="">
                  </a>
                <?php else: ?>
                  <img src="<?= htmlspecialchars($c['avatar_url']); ?>" alt="<?= htmlspecialchars($c['username']); ?>">
                <?php endif; ?>
                <div class="thread-item-content">
                  <?php if (!empty($c['user_id'])): ?>
                    <a class="who comment-author-name" href="profile.php?id=<?= (int) $c['user_id']; ?>">@<?= htmlspecialchars($c['username']); ?></a>
                  <?php else: ?>
                    <span class="who">@<?= htmlspecialchars($c['username']); ?></span>
                  <?php endif; ?>
                  <time><?= $c['time']; ?></time>
                  <?php if (
                      !empty($post['is_real'])
                      && (!Auth::check() || (int) $c['user_id'] !== (int) Auth::user()['id'])
                  ): ?>
                    <button type="button" class="icon-flag icon-flag--inline" data-report-type="comment" data-report-id="<?= (int) $c['id']; ?>" data-report-target="@<?= htmlspecialchars($c['username']); ?>'s comment" title="Report comment" aria-label="Report comment">&#128681;</button>
                  <?php endif; ?>
                  <?php if (
                      !empty($post['is_real'])
                      && Auth::check()
                      && (int) $c['user_id'] === (int) Auth::user()['id']
                      && $editingCommentId === (int) $c['id']
                  ): ?>
                    <form class="comment-edit-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments">
                      <?= Csrf::field(); ?>
                      <input type="hidden" name="action" value="edit_comment">
                      <input type="hidden" name="comment_id" value="<?= (int) $c['id']; ?>">
                      <label class="sr-only" for="editComment<?= $commentIndex; ?>">Edit your comment</label>
                      <textarea id="editComment<?= $commentIndex; ?>" name="content" rows="3" maxlength="500" required><?= htmlspecialchars($commentText !== '' || $errors ? $commentText : $c['content']); ?></textarea>
                      <div class="comment-edit-actions">
                        <button class="btn btn-solid" type="submit">Save</button>
                        <a class="btn" href="post.php?id=<?= $post['id']; ?>#comments">Cancel</a>
                      </div>
                    </form>
                  <?php else: ?>
                    <p class="comment-text" id="commentText<?= $commentIndex; ?>"><?= htmlspecialchars($c['text']); ?></p>
                    <button class="comment-toggle" type="button" aria-controls="commentText<?= $commentIndex; ?>" aria-expanded="false" hidden>Show more</button>
                  <?php endif; ?>
                  <?php if (!empty($post['is_real']) && Auth::check() && (int) $c['user_id'] === (int) Auth::user()['id']): ?>
                    <?php if ($editingCommentId !== (int) $c['id']): ?>
                      <div class="comment-actions">
                        <a class="comment-action" href="post.php?id=<?= $post['id']; ?>&amp;edit_comment=<?= (int) $c['id']; ?>#comments">Edit</a>
                        <form class="comment-delete-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments" onsubmit="return confirm('Delete your comment?');">
                          <?= Csrf::field(); ?>
                          <input type="hidden" name="action" value="delete_comment">
                          <input type="hidden" name="comment_id" value="<?= (int) $c['id']; ?>">
                          <button class="comment-action comment-delete" type="submit">Delete</button>
                        </form>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if (!empty($post['is_real']) && Auth::check()): ?>
          <form class="comment-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments">
            <?= Csrf::field(); ?>
            <input type="hidden" name="action" value="comment">
            <label class="sr-only" for="commentContent">Add a comment</label>
            <textarea id="commentContent" name="content" rows="3" maxlength="500" required placeholder="Add a comment..."><?= htmlspecialchars($commentText); ?></textarea>
            <button type="submit" class="btn btn-solid">Post</button>
          </form>
          <p class="field-hint" style="margin-top:8px;">Up to 500 characters.</p>
        <?php elseif (!empty($post['is_real'])): ?>
          <p class="field-hint"><a href="login.php">Log in</a> to leave a comment.</p>
        <?php else: ?>
          <p class="field-hint">Comments can be added to live posts.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>