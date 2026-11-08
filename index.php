<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (
    is_string($requestPath)
    && preg_match('~(?:^|/)@([A-Za-z0-9_]{3,30})$~D', $requestPath, $routeMatch) === 1
) {
    define('DS_PRETTY_PROFILE_ROUTE', true);
    $_GET['username'] = $routeMatch[1];
    require __DIR__ . '/profile.php';
    exit;
}

require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Post.php';
require __DIR__ . '/includes/helpers.php';
$currentUser = Auth::user();
$viewerId = $currentUser ? (int) $currentUser['id'] : null;
$hot_posts = array_map('ds_post_view', Post::findHot($viewerId));
$newest_posts = array_map('ds_post_view', Post::findNewest($viewerId));
$pinned_posts = array_map('ds_post_view', Post::findFrontPinned($viewerId));
$heroPost = $newest_posts[0] ?? null;
$heroImage = $heroPost ? ds_post_image_url($heroPost) : 'uploads/posts/placeholder1.webp';
$heroAlt = $heroPost ? $heroPost['title'] : 'DEADSTOCK post';
$active = 'home';
$page_title = 'Home';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <span class="kicker-tag">LIVE FEED &mdash; UPDATED NOW</span>
      <h1>
        <span class="stack-ghost" aria-hidden="true">Fits worth<br>camping for.</span>
        <span class="stack-main">Fits worth<br><em>camping for.</em></span>
      </h1>
      <p>Post the pickup, the thrift win, the 5am line. DEADSTOCK is where the racks nobody else checked end up on somebody's feed.</p>
      <div class="hero-actions">
        <a href="upload.php" class="btn btn-solid">Post a fit</a>
        <a href="#hot" class="btn btn-ghost-light">See what's hot</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="cert-badge" aria-hidden="true">
        <span class="small">100%</span>
        <span class="big">DEAD<br>STOCK</span>
        <span class="small">CERTIFIED</span>
      </div>
      <img src="<?= htmlspecialchars($heroImage); ?>" alt="<?= htmlspecialchars($heroAlt); ?>">
      <div class="hang-tag">
        <span class="tag-hole"></span>
        <dl>
          <dt>DROP</dt><dd>#118</dd>
          <dt>FEED</dt><dd>LIVE</dd>
          <dt>STATUS</dt><dd>LIVE</dd>
        </dl>
      </div>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="section section--dark" id="hot">
  <div class="wrap">
    <div class="section-head">
      <span class="ghost-num" aria-hidden="true">01</span>
      <h2>Hot right now</h2>
      <span class="count-flag">Most likes in the last 48 hours</span>
    </div>
    <div class="photo-grid">
      <?php if ($hot_posts): ?>
        <?php foreach ($hot_posts as $p) ds_photo_card($p); ?>
      <?php else: ?>
        <p class="empty-state">No pictures have received likes in the last 48 hours.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="section" id="new">
  <div class="wrap">
    <div class="section-head">
      <span class="ghost-num" aria-hidden="true">02</span>
      <h2>New pictures</h2>
      <div class="section-head-actions">
        <a href="browse.php" class="section-link">Browse all &rarr;</a>
        <a href="upload.php" class="section-link">Post yours &rarr;</a>
      </div>
    </div>
    <div class="photo-grid">
      <?php if ($newest_posts): ?>
        <?php foreach ($newest_posts as $p) ds_photo_card($p); ?>
      <?php else: ?>
        <p class="empty-state">No pictures yet. Be the first to post one.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="section section--pinned" id="pinned">
  <div class="brand-wash" aria-hidden="true">
    <?php for ($i = 0; $i < 24; $i++): ?><span>DEADSTOCK</span><?php endfor; ?>
  </div>
  <div class="wrap">
    <div class="section-head">
      <span class="ghost-num" aria-hidden="true">03</span>
      <h2>Pinned</h2>
      <span class="count-flag">Front-page picks</span>
    </div>
    <div class="pin-grid">
      <?php if ($pinned_posts):
        $tilts = [-2.4, 1.6, -1.1, 2.1];
        foreach ($pinned_posts as $i => $p):
          $tilt = $tilts[$i % count($tilts)];
      ?>
          <a href="post.php?id=<?= $p['id']; ?>" class="pin-card reveal">
            <div class="pin-card-inner" style="--tilt: <?= $tilt; ?>deg;">
              <span class="pin-dot"></span>
              <div class="frame">
                <img src="<?= htmlspecialchars(ds_post_image_url($p)); ?>" alt="<?= htmlspecialchars($p['caption']); ?>" loading="lazy">
              </div>
              <p class="cap"><b>@<?= htmlspecialchars($p['username']); ?></b> &mdash; <?= htmlspecialchars($p['title']); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="empty-state">No pictures have been pinned yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<?php include __DIR__ . '/includes/footer.php'; ?>
