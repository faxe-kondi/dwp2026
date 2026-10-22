<?php
/**
 * Shared profile header — avatar, bio, stats, tab bar.
 * Used by profile.php, liked.php, following.php, blocked.php so all
 * four stay visually identical instead of drifting apart over time.
 *
 * Expects $demo_profile (from data.php) and $profile_tab set to one of
 * 'posts' | 'likes' | 'following' | 'blocked' before this is included.
 *
 * There is deliberately no separate "Pinned" tab — pinned posts are
 * sorted to the top of the regular Posts grid instead (see profile.php),
 * so pinning something doesn't split your profile into two places to
 * check.
 */
if (!isset($profile_tab)) { $profile_tab = 'posts'; }
?>
<div class="profile-header">
  <div class="profile-avatar">
    <img src="<?= ds_avatar_url($demo_profile['avatar_seed'], 256); ?>" alt="<?= htmlspecialchars($demo_profile['display_name']); ?>">
  </div>
  <div class="profile-id">
    <h1><?= htmlspecialchars($demo_profile['display_name']); ?></h1>
    <div class="handle">@<?= htmlspecialchars($demo_profile['username']); ?></div>
  </div>
  <div class="profile-actions">
    <a href="#" class="btn btn-solid">Follow</a>
    <a href="upload.php" class="btn">+ New post</a>
  </div>
</div>

<p class="profile-bio"><?= htmlspecialchars($demo_profile['bio']); ?></p>
<div class="profile-meta">
  <span>&#128205; <?= htmlspecialchars($demo_profile['location']); ?></span>
  <span><?= htmlspecialchars($demo_profile['joined']); ?></span>
</div>

<div class="profile-stats">
  <div><b><?= $demo_profile['stats']['posts']; ?></b><span>POSTS</span></div>
  <div><b><?= $demo_profile['stats']['followers']; ?></b><span>FOLLOWERS</span></div>
  <div><b><?= $demo_profile['stats']['following']; ?></b><span>FOLLOWING</span></div>
</div>

<div class="profile-tabs">
  <a href="profile.php" class="<?= $profile_tab === 'posts' ? 'active' : ''; ?>">Posts</a>
  <a href="liked.php" class="<?= $profile_tab === 'likes' ? 'active' : ''; ?>">Likes</a>
  <a href="following.php" class="<?= $profile_tab === 'following' ? 'active' : ''; ?>">Following</a>
  <a href="blocked.php" class="<?= $profile_tab === 'blocked' ? 'active' : ''; ?>">Blocked</a>
</div>
