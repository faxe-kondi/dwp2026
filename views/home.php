<?php
$active = 'home';
$page_title = 'Home';
View::render('layout/header', get_defined_vars());
?>
<section class="hero"><div class="wrap">
  <div class="hero-copy">
    <span class="kicker-tag">LIVE FEED &mdash; UPDATED NOW</span>
    <h1><span class="stack-ghost" aria-hidden="true">Fits worth<br>camping for.</span><span class="stack-main">Fits worth<br><em>camping for.</em></span></h1>
    <p>Post the pickup, the thrift win, the 5am line. DEADSTOCK is where the racks nobody else checked end up on somebody's feed.</p>
    <div class="hero-actions"><a href="upload.php" class="btn btn-solid">Post a fit</a><a href="#hot" class="btn btn-ghost-light">See what's hot</a></div>
  </div>
  <div class="hero-visual">
    <div class="cert-badge" aria-hidden="true"><span class="small">100%</span><span class="big">DEAD<br>STOCK</span><span class="small">CERTIFIED</span></div>
    <img src="<?= htmlspecialchars($heroImage); ?>" alt="<?= htmlspecialchars($heroAlt); ?>">
  </div>
</div></section>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="section section--dark" id="hot"><div class="wrap">
  <div class="section-head"><span class="ghost-num" aria-hidden="true">01</span><h2>Hot right now</h2><span class="count-flag">Most likes in the last 48 hours</span></div>
  <div class="photo-grid"><?php if ($hot_posts): ?><?php foreach ($hot_posts as $p) View::render('components/post-card', ['p' => $p, 'pinScope' => 'homepage']); ?><?php else: ?><p class="empty-state">No pictures have received likes in the last 48 hours.</p><?php endif; ?></div>
</div></section>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="section" id="new"><div class="wrap">
  <div class="section-head"><span class="ghost-num" aria-hidden="true">02</span><h2>New pictures</h2><div class="section-head-actions"><a href="browse.php" class="section-link">Browse all &rarr;</a><a href="upload.php" class="section-link">Post yours &rarr;</a></div></div>
  <div class="photo-grid"><?php if ($newest_posts): ?><?php foreach ($newest_posts as $p) View::render('components/post-card', ['p' => $p, 'pinScope' => 'homepage']); ?><?php else: ?><p class="empty-state">No pictures yet. Be the first to post one.</p><?php endif; ?></div>
</div></section>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="section section--pinned" id="pinned"><div class="wrap">
  <div class="section-head"><span class="ghost-num" aria-hidden="true">03</span><h2>Pinned</h2><span class="count-flag">Front-page picks</span></div>
  <div class="pin-grid">
    <?php if ($pinned_posts):
      $tilts = [-2.4, 1.6, -1.1, 2.1];
      foreach ($pinned_posts as $i => $p): $tilt = $tilts[$i % count($tilts)]; ?>
        <a href="post.php?id=<?= $p['id']; ?>" class="pin-card reveal"><div class="pin-card-inner" style="--tilt: <?= $tilt; ?>deg;"><span class="pin-dot"></span><div class="frame"><img src="<?= htmlspecialchars(ds_post_image_url($p)); ?>" alt="<?= htmlspecialchars($p['caption']); ?>" loading="lazy"></div><p class="cap"><b>@<?= htmlspecialchars($p['username']); ?></b> &mdash; <?= htmlspecialchars($p['title']); ?></p></div></a>
      <?php endforeach; ?>
    <?php else: ?><p class="empty-state">No pictures have been pinned yet.</p><?php endif; ?>
  </div>
</div></section>
<div class="stripe-bar" aria-hidden="true"></div>
<?php View::render('layout/footer', get_defined_vars()); ?>
