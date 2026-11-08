<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/helpers.php';

$isPrettyProfileRoute = defined('DS_PRETTY_PROFILE_ROUTE') && DS_PRETTY_PROFILE_ROUTE;
$requestedUsername = $_GET['username'] ?? null;
$requestedUserId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$hasUsernameParameter = $isPrettyProfileRoute || array_key_exists('username', $_GET);
$hasLegacyId = array_key_exists('id', $_GET);

if ($hasUsernameParameter) {
    $viewer = is_string($requestedUsername) && $requestedUsername !== ''
        ? User::findByUsername($requestedUsername)
        : null;
    if ($viewer !== null && !$isPrettyProfileRoute) {
        $query = $_GET;
        unset($query['username'], $query['id']);
        $profileUrl = '@' . rawurlencode($viewer['username']);
        header('Location: ' . $profileUrl . ($query ? '?' . http_build_query($query) : ''), true, 301);
        exit;
    }
    $isOwnProfile = $viewer !== null
        && Auth::check()
        && (int) $viewer['id'] === (int) Auth::user()['id'];
} elseif ($hasLegacyId) {
    $viewer = $requestedUserId !== false && $requestedUserId !== null && $requestedUserId > 0
        ? User::find($requestedUserId)
        : null;
    if ($viewer !== null) {
        $query = $_GET;
        unset($query['id']);
        $profileUrl = '@' . rawurlencode($viewer['username']);
        header('Location: ' . $profileUrl . ($query ? '?' . http_build_query($query) : ''), true, 301);
        exit;
    }
    $isOwnProfile = $viewer !== null
        && Auth::check()
        && (int) $viewer['id'] === (int) Auth::user()['id'];
} else {
    Auth::requireLogin();
    $viewer = User::find((int) Auth::user()['id']);
    $isOwnProfile = true;
}

if (!$viewer) {
    if ($isOwnProfile) {
        Auth::logout();
        header('Location: login.php');
        exit;
    }

    http_response_code(404);
    exit('Profile not found.');
}

$blockState = Auth::check() && !$isOwnProfile
    ? User::getBlockState((int) Auth::user()['id'], (int) $viewer['id'])
    : ['blocked_by_viewer' => false, 'blocked_viewer' => false];
$profileBlocked = $blockState['blocked_by_viewer'] || $blockState['blocked_viewer'];
$user_posts = $profileBlocked
    ? []
    : array_map('ds_post_view', Post::findByUser((int) $viewer['id']));
usort($user_posts, fn($a, $b) => ($b['pinned'] <=> $a['pinned']));
$profile = ds_profile_view($viewer, count($user_posts));
$isFollowingProfile = Auth::check()
    && !$isOwnProfile
    && !$profileBlocked
    && User::isFollowing((int) Auth::user()['id'], (int) $viewer['id']);
$canManageProfile = Auth::check()
    && Auth::canModerate()
    && (int) Auth::user()['id'] !== (int) $viewer['id']
    && $viewer['role'] !== 'owner'
    && (Auth::role() === 'owner' || $viewer['role'] === 'user');

$active = 'profile';
$page_title = $profile['display_name'];
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'posts'; include __DIR__ . '/includes/profile-header.php'; ?>

    <?php if (($_GET['moderation'] ?? '') === 'banned'): ?>
      <div class="form-success" role="status">User banned.</div>
    <?php elseif (($_GET['moderation'] ?? '') === 'unbanned'): ?>
      <div class="form-success" role="status">User unbanned.</div>
    <?php endif; ?>

    <?php if (isset($_GET['post_deleted']) && $_GET['post_deleted'] === '1'): ?>
      <?php if (isset($_GET['cleanup_failed']) && $_GET['cleanup_failed'] === '1'): ?>
        <div class="form-errors"><ul><li>The post was deleted, but its uploaded image could not be removed.</li></ul></div>
      <?php else: ?>
        <div class="form-success">Post deleted.</div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!$isOwnProfile && $blockState['blocked_by_viewer']): ?>
      <div class="empty-state profile-blocked-state">
        <p>You blocked this user. Unblock them to see their content.</p>
        <button
          type="button"
          class="btn unblock-btn"
          data-block-url="includes/block.php"
          data-user-id="<?= (int) $viewer['id']; ?>"
          data-csrf-token="<?= htmlspecialchars(Csrf::token()); ?>"
          data-blocked="true"
          data-unblock-label="@<?= htmlspecialchars($profile['username']); ?>"
        >Unblock user</button>
      </div>
    <?php elseif (!$isOwnProfile && $blockState['blocked_viewer']): ?>
      <div class="empty-state profile-blocked-state">You can't see this user's content.</div>
    <?php elseif (empty($user_posts)): ?>
      <div class="empty-state">No posts yet.</div>
    <?php else: ?>
      <div class="photo-grid">
        <?php foreach ($user_posts as $p) ds_photo_card($p); ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
