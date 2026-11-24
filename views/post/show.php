<?php
$active = '';
$page_title = $post['title'] . ' - @' . $post['username'];
View::render('layout/header', get_defined_vars());
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
        <button type="button" class="btn unblock-btn" data-block-url="controllers/BlockController.php" data-user-id="<?= (int) $realRow['user_id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-blocked="true" data-unblock-label="@<?= htmlspecialchars($post['username']); ?>">Unblock user</button>
      </div>
    <?php else: ?>
      <div class="post-image">
        <img src="<?= htmlspecialchars(ds_post_image_url($post)); ?>" alt="<?= htmlspecialchars($post['title']); ?>">
      </div>

      <div class="post-details">
        <?php if ($pinNotice !== null): ?>
          <div class="form-success" role="status"><?= htmlspecialchars($pinNotice); ?></div>
        <?php endif; ?>

        <div class="uploader-card">
          <a class="uploader-profile" href="<?= htmlspecialchars(ds_user_profile_url($post['username'])); ?>">
            <img src="<?= htmlspecialchars(ds_post_avatar_url($post, 104)); ?>" alt="">
            <span class="name"><?= htmlspecialchars($post['username']); ?></span>
          </a>
          <?php if (!$isOwner): ?>
            <?php if ($currentUser): ?>
              <button type="button" class="btn follow-btn<?= $isFollowingPoster ? ' following' : ''; ?>" data-follow-url="controllers/FollowController.php" data-user-id="<?= (int) $realRow['user_id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-following="<?= $isFollowingPoster ? 'true' : 'false'; ?>" data-following-label="Following" data-follow-label="+ Follow" aria-pressed="<?= $isFollowingPoster ? 'true' : 'false'; ?>"><?= $isFollowingPoster ? 'Following' : '+ Follow'; ?></button>
            <?php else: ?>
              <a class="btn" href="login.php">Log in to follow</a>
            <?php endif; ?>
          <?php endif; ?>

          <div class="action-menu">
            <button class="action-menu-trigger" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="uploaderMenu" aria-label="<?= $isOwner ? 'Post options' : 'More options for @' . htmlspecialchars($post['username']); ?>">&#8942;</button>
            <div class="action-menu-list" id="uploaderMenu">
              <?php if ($canDeletePost): ?>
                <?php if ($isOwner): ?>
                  <a class="action-menu-item" href="edit-post.php?id=<?= $post['id']; ?>">Edit post</a>
                  <form method="post" action="post.php?id=<?= $post['id']; ?>">
                    <?= Csrf::field(); ?>
                    <input type="hidden" name="action" value="pin_profile">
                    <button type="submit" class="action-menu-item"><?= $realRow['profile_pinned_at'] !== null ? 'Unpin from profile' : 'Pin to profile'; ?></button>
                  </form>
                <?php endif; ?>
                <?php if (Auth::canModerate()): ?>
                  <form method="post" action="post.php?id=<?= $post['id']; ?>">
                    <?= Csrf::field(); ?>
                    <input type="hidden" name="action" value="pin_front">
                    <button type="submit" class="action-menu-item"><?= $realRow['front_pinned_at'] !== null ? 'Unpin from homepage' : 'Pin to homepage'; ?></button>
                  </form>
                <?php endif; ?>
                <form method="post" action="post.php?id=<?= $post['id']; ?>" onsubmit="return confirm('Delete this post? This cannot be undone.');">
                  <?= Csrf::field(); ?>
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="action-menu-item action-menu-item--danger"><?= $isOwner ? 'Delete post' : 'Remove post'; ?></button>
                </form>
              <?php else: ?>
                <button type="button" class="action-menu-item" data-report-type="user" data-report-id="<?= (int) $realRow['user_id']; ?>" data-report-target="@<?= htmlspecialchars($post['username']); ?>">Report user</button>
                <?php if ($currentUser): ?>
                  <button type="button" class="action-menu-item action-menu-item--danger" data-block-url="controllers/BlockController.php" data-user-id="<?= (int) $realRow['user_id']; ?>" data-post-id="<?= (int) $post['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-blocked="false" data-block-label="@<?= htmlspecialchars($post['username']); ?>">Block user</button>
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
          <div class="post-tags"><?php foreach ($post['tags'] as $tag): ?><span class="chip">#<?= htmlspecialchars($tag); ?></span><?php endforeach; ?></div>
        <?php endif; ?>

        <div class="post-stats-row">
          <?php if ($isOwner): ?>
            <span class="like-count-display" aria-label="<?= $post['likes']; ?> likes">
              <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
              <span class="like-count"><?= $post['likes']; ?></span>
            </span>
          <?php else: ?>
            <?php if (Auth::check()): ?>
              <button type="button" class="like-btn<?= !empty($post['liked_by_viewer']) ? ' liked' : ''; ?>" data-like-url="controllers/LikeController.php" data-post-id="<?= $post['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-likes="<?= $post['likes']; ?>" data-liked="<?= !empty($post['liked_by_viewer']) ? 'true' : 'false'; ?>" aria-pressed="<?= !empty($post['liked_by_viewer']) ? 'true' : 'false'; ?>" aria-label="<?= !empty($post['liked_by_viewer']) ? 'Unlike post' : 'Like post'; ?>">
            <?php else: ?>
              <a class="like-btn" href="login.php" aria-label="Log in to like this post">
            <?php endif; ?>
              <svg class="like-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
              <span class="like-count"><?= $post['likes']; ?></span>
              <span class="like-label"><?= !empty($post['liked_by_viewer']) ? 'Liked' : 'Like'; ?></span>
            <?php if (Auth::check()): ?></button><?php else: ?></a><?php endif; ?>
          <?php endif; ?>
          <span>&#128172; <?= count($post_comments); ?> comments</span>
          <span><?= $post['hot'] ? '&#128293; trending' : 'in the feed'; ?></span>
          <?php if (!$isOwner): ?>
            <button type="button" class="icon-flag" data-report-type="post" data-report-id="<?= (int) $post['id']; ?>" data-report-target="@<?= htmlspecialchars($post['username']); ?>'s post" title="Report post" aria-label="Report post" style="margin-left:auto;">&#128681; Report</button>
          <?php endif; ?>
        </div>

        <div class="comment-thread">
          <h3 id="comments">Comments</h3>
          <?php if ($errors): ?>
            <div class="form-errors"><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
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
                <div class="thread-item" id="comment-<?= (int) $c['id']; ?>">
                  <a class="comment-author-avatar" href="<?= htmlspecialchars(ds_user_profile_url($c['username'])); ?>" aria-label="View @<?= htmlspecialchars($c['username']); ?>'s profile"><img src="<?= htmlspecialchars($c['avatar_url']); ?>" alt=""></a>
                  <div class="thread-item-content">
                    <a class="who comment-author-name" href="<?= htmlspecialchars(ds_user_profile_url($c['username'])); ?>">@<?= htmlspecialchars($c['username']); ?></a>
                    <time><?= $c['time']; ?></time>
                    <?php if (!Auth::check() || (int) $c['user_id'] !== (int) Auth::user()['id']): ?>
                      <button type="button" class="icon-flag icon-flag--inline" data-report-type="comment" data-report-id="<?= (int) $c['id']; ?>" data-report-target="@<?= htmlspecialchars($c['username']); ?>'s comment" title="Report comment" aria-label="Report comment">&#128681;</button>
                    <?php endif; ?>
                    <?php if (Auth::check() && (int) $c['user_id'] === (int) Auth::user()['id'] && $editingCommentId === (int) $c['id']): ?>
                      <form class="comment-edit-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments">
                        <?= Csrf::field(); ?>
                        <input type="hidden" name="action" value="edit_comment">
                        <input type="hidden" name="comment_id" value="<?= (int) $c['id']; ?>">
                        <label class="sr-only" for="editComment<?= $commentIndex; ?>">Edit your comment</label>
                        <textarea id="editComment<?= $commentIndex; ?>" name="content" rows="3" maxlength="500" required><?= htmlspecialchars($commentText !== '' || $errors ? $commentText : $c['content']); ?></textarea>
                        <div class="comment-edit-actions"><button class="btn btn-solid" type="submit">Save</button><a class="btn" href="post.php?id=<?= $post['id']; ?>#comments">Cancel</a></div>
                      </form>
                    <?php else: ?>
                      <p class="comment-text" id="commentText<?= $commentIndex; ?>"><?= htmlspecialchars($c['text']); ?></p>
                      <button class="comment-toggle" type="button" aria-controls="commentText<?= $commentIndex; ?>" aria-expanded="false" hidden>Show more</button>
                    <?php endif; ?>
                    <?php
                      $isOwnComment = Auth::check() && (int) $c['user_id'] === (int) Auth::user()['id'];
                      $canDeleteComment = $isOwnComment || Auth::canModerate();
                    ?>
                    <?php if ($canDeleteComment && $editingCommentId !== (int) $c['id']): ?>
                      <div class="comment-actions">
                        <?php if ($isOwnComment): ?><a class="comment-action" href="post.php?id=<?= $post['id']; ?>&amp;edit_comment=<?= (int) $c['id']; ?>#comments">Edit</a><?php endif; ?>
                        <form class="comment-delete-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments" onsubmit="return confirm('Delete this comment?');">
                          <?= Csrf::field(); ?>
                          <input type="hidden" name="action" value="delete_comment">
                          <input type="hidden" name="comment_id" value="<?= (int) $c['id']; ?>">
                          <button class="comment-action comment-delete" type="submit"><?= $isOwnComment ? 'Delete' : 'Remove'; ?></button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php if (Auth::check()): ?>
            <form class="comment-form" method="post" action="post.php?id=<?= $post['id']; ?>#comments">
              <?= Csrf::field(); ?>
              <input type="hidden" name="action" value="comment">
              <label class="sr-only" for="commentContent">Add a comment</label>
              <textarea id="commentContent" name="content" rows="3" maxlength="500" required placeholder="Add a comment..."><?= htmlspecialchars($commentText); ?></textarea>
              <button type="submit" class="btn btn-solid">Post</button>
            </form>
            <p class="field-hint" style="margin-top:8px;">Up to 500 characters.</p>
          <?php else: ?>
            <p class="field-hint"><a href="login.php">Log in</a> to leave a comment.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php View::render('layout/footer', get_defined_vars()); ?>
