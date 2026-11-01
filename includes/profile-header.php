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

<div class="profile-stats">
  <div><b><?= $profile['stats']['posts']; ?></b><span>POSTS</span></div>
  <div><b><?= $profile['stats']['followers']; ?></b><span>FOLLOWERS</span></div>
  <div><b><?= $profile['stats']['following']; ?></b><span>FOLLOWING</span></div>
</div>

<div class="profile-tabs">
  <?php if ($isOwnProfile): ?>
    <a href="profile.php" class="<?= $profile_tab === 'posts' ? 'active' : ''; ?>">Posts</a>
    <a href="liked.php" class="<?= $profile_tab === 'likes' ? 'active' : ''; ?>">Likes</a>
    <a href="following.php" class="<?= $profile_tab === 'following' ? 'active' : ''; ?>">Following</a>
    <a href="blocked.php" class="<?= $profile_tab === 'blocked' ? 'active' : ''; ?>">Blocked</a>
    <a href="settings.php" class="<?= $profile_tab === 'settings' ? 'active' : ''; ?>">Settings</a>
  <?php else: ?>
    <a href="profile.php?id=<?= (int) $viewer['id']; ?>" class="active">Posts</a>
  <?php endif; ?>
</div>
