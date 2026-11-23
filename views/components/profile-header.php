<?php
if (!isset($profile_tab)) { $profile_tab = 'posts'; }
?>
<div class="profile-header">
  <div class="profile-avatar">
    <img src="<?= htmlspecialchars($profile['avatar_url']); ?>" alt="<?= htmlspecialchars($profile['display_name']); ?>">
  </div>
  <div class="profile-id">
    <h1><?= htmlspecialchars($profile['display_name']); ?></h1>
  </div>
  <?php if ($isOwnProfile): ?>
    <div class="profile-actions">
      <a href="settings.php" class="btn">Edit profile</a>
      <a href="upload.php" class="btn btn-solid">+ New post</a>
    </div>
  <?php else: ?>
    <div class="profile-actions">
      <?php if (Auth::check() && $blockState['blocked_by_viewer']): ?>
        <button
          type="button"
          class="btn unblock-btn"
          data-block-url="includes/block.php"
          data-user-id="<?= (int) $viewer['id']; ?>"
          data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
          data-blocked="true"
          data-unblock-label="@<?= htmlspecialchars($profile['username']); ?>"
        >Unblock</button>
      <?php elseif (Auth::check() && !$blockState['blocked_viewer']): ?>
        <button
          type="button"
          class="btn follow-btn<?= $isFollowingProfile ? ' following' : ''; ?>"
          data-follow-url="includes/follow.php"
          data-user-id="<?= (int) $viewer['id']; ?>"
          data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
          data-following="<?= $isFollowingProfile ? 'true' : 'false'; ?>"
          data-following-label="Following"
          data-follow-label="+ Follow"
          aria-pressed="<?= $isFollowingProfile ? 'true' : 'false'; ?>"
        ><?= $isFollowingProfile ? 'Following' : '+ Follow'; ?></button>
      <?php else: ?>
        <?php if (!Auth::check()): ?>
          <a href="login.php" class="btn">Log in to follow</a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!$blockState['blocked_by_viewer'] && !$blockState['blocked_viewer']): ?>
        <div class="action-menu">
        <button
          class="action-menu-trigger"
          type="button"
          aria-haspopup="true"
          aria-expanded="false"
          aria-controls="profileUserMenu"
          aria-label="More options for @<?= htmlspecialchars($profile['username']); ?>"
        >&#8942;</button>
        <div class="action-menu-list" id="profileUserMenu">
          <button type="button" class="action-menu-item" data-report-type="user" data-report-id="<?= (int) $viewer['id']; ?>" data-report-target="@<?= htmlspecialchars($profile['username']); ?>">Report user</button>
          <?php if (Auth::check()): ?>
            <button type="button" class="action-menu-item action-menu-item--danger" data-block-url="includes/block.php" data-user-id="<?= (int) $viewer['id']; ?>" data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>" data-blocked="false" data-block-label="@<?= htmlspecialchars($profile['username']); ?>">Block user</button>
          <?php endif; ?>
          <?php if (!empty($canManageProfile)): ?>
            <?php if ($viewer['is_banned']): ?>
              <form method="post" action="includes/moderate-user.php" onsubmit="return confirm('Unban @<?= htmlspecialchars($profile['username'], ENT_QUOTES); ?>?');">
                <?= Csrf::field(); ?>
                <input type="hidden" name="user_id" value="<?= (int) $viewer['id']; ?>">
                <input type="hidden" name="action" value="unban">
                <button type="submit" class="action-menu-item">Unban user</button>
              </form>
            <?php else: ?>
              <button
                type="button"
                class="action-menu-item action-menu-item--danger"
                data-ban-user
                data-user-id="<?= (int) $viewer['id']; ?>"
                data-username="<?= htmlspecialchars($profile['username']); ?>"
              >Ban user</button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<p class="profile-bio">
  <?php if ($profile['bio']): ?>
    <?= htmlspecialchars($profile['bio']); ?>
  <?php else: ?>
    <span style="color:var(--steel);">No bio yet.</span>
  <?php endif; ?>
</p>
<div class="profile-meta">
  <span><?= htmlspecialchars($profile['joined']); ?></span>
</div>

<div
  class="profile-stats"
  data-profile-user-id="<?= (int) $viewer['id']; ?>"
  data-viewer-user-id="<?= Auth::check() ? (int) Auth::user()['id'] : ''; ?>"
>
  <div><b><?= $profile['stats']['posts']; ?></b><span>POSTS</span></div>
  <div><b data-profile-stat="followers"><?= $profile['stats']['followers']; ?></b><span>FOLLOWERS</span></div>
  <div><b data-profile-stat="following"><?= $profile['stats']['following']; ?></b><span>FOLLOWING</span></div>
</div>

<div class="profile-tabs">
  <?php if ($isOwnProfile): ?>
    <a href="<?= htmlspecialchars(ds_user_profile_url($profile['username'])); ?>" class="<?= $profile_tab === 'posts' ? 'active' : ''; ?>">Posts</a>
    <a href="liked.php" class="<?= $profile_tab === 'likes' ? 'active' : ''; ?>">Likes</a>
    <a href="following.php" class="<?= $profile_tab === 'following' ? 'active' : ''; ?>">Following</a>
    <a href="blocked.php" class="<?= $profile_tab === 'blocked' ? 'active' : ''; ?>">Blocked</a>
    <a href="settings.php" class="<?= $profile_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
  <?php else: ?>
    <a href="<?= htmlspecialchars(ds_user_profile_url($profile['username'])); ?>" class="active">Posts</a>
  <?php endif; ?>
</div>
