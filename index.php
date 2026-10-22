<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/helpers.php';
$active = 'home';
$page_title = 'Home';
include __DIR__ . '/includes/header.php';

$hot_posts    = array_values(array_filter($demo_posts, fn($p) => $p['hot']));
$newest_posts = $demo_posts;
$pinned_posts = array_values(array_filter($demo_posts, fn($p) => $p['pinned']));
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-copy">
      <span class="kicker-tag">LIVE FEED &mdash; UPDATED HOURLY</span>
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
      <img src="<?= ds_photo_url('ds-hero-1', 700, 875); ?>" alt="Featured streetwear fit">
      <div class="hang-tag">
        <span class="tag-hole"></span>
        <dl>
          <dt>DROP</dt><dd>#118</dd>
          <dt>POSTS</dt><dd><?= count($demo_posts); ?> today</dd>
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
      <span class="count-flag"><?= count($hot_posts); ?> posts climbing</span>
    </div>
    <div class="photo-grid">
      <?php foreach ($hot_posts as $p) ds_photo_card($p); ?>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="section" id="new">
  <div class="wrap">
    <div class="section-head">
      <span class="ghost-num" aria-hidden="true">02</span>
      <h2>New pictures</h2>
      <a href="upload.php" class="section-link">Post yours &rarr;</a>
    </div>
    <div class="photo-grid">
      <?php foreach (array_slice($newest_posts, 0, 8) as $p) ds_photo_card($p); ?>
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
      <span class="count-flag">Picked by the mods this week</span>
    </div>
    <div class="pin-grid">
      <?php
      $tilts = [-2.4, 1.6, -1.1, 2.1];
      foreach ($pinned_posts as $i => $p):
        $tilt = $tilts[$i % count($tilts)];
      ?>
        <a href="post.php?id=<?= $p['id']; ?>" class="pin-card reveal">
          <div class="pin-card-inner" style="--tilt: <?= $tilt; ?>deg;">
            <span class="pin-dot"></span>
            <div class="frame">
              <img src="<?= ds_photo_url($p['img_seed']); ?>" alt="<?= htmlspecialchars($p['caption']); ?>">
            </div>
            <p class="cap"><b>@<?= htmlspecialchars($p['username']); ?></b> &mdash; <?= htmlspecialchars(mb_strimwidth($p['caption'], 0, 60, '…')); ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="section section--dark">
  <div class="wrap">
    <div class="section-head">
      <span class="ghost-num" aria-hidden="true">04</span>
      <h2>New comments</h2>
      <span class="count-flag">across every post</span>
    </div>
    <div class="comment-feed">
      <?php foreach ($demo_comments as $c):
        $post = current(array_filter($demo_posts, fn($p) => $p['id'] === $c['post_id']));
      ?>
        <div class="comment-row reveal">
          <img src="<?= ds_avatar_url($c['avatar_seed'], 44); ?>" alt="<?= htmlspecialchars($c['username']); ?>">
          <div>
            <span class="who">@<?= htmlspecialchars($c['username']); ?>
              <?php if ($post): ?><span class="on">on <a href="post.php?id=<?= $post['id']; ?>" style="color:inherit;text-decoration:underline;">@<?= htmlspecialchars($post['username']); ?>'s post</a></span><?php endif; ?>
            </span>
            <p>&ldquo;<?= htmlspecialchars($c['text']); ?>&rdquo;</p>
          </div>
          <div class="comment-row-meta">
            <time><?= $c['time']; ?></time>
            <button class="icon-flag" data-report-type="comment" data-report-target="@<?= htmlspecialchars($c['username']); ?>'s comment" title="Report comment" aria-label="Report comment">&#128681;</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
