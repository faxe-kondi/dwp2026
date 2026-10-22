<?php
require __DIR__ . '/includes/data.php';
$active = 'about';
$page_title = 'About';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / About</div>
    <h1>What is DEADSTOCK?</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap about-grid">
    <div>
      <img src="<?= ds_photo_url('ds-about-1', 700, 620); ?>" alt="Streetwear rack">
      <div class="stat-row">
        <div><b><?= count($demo_posts); ?>+</b><span>POSTS TODAY</span></div>
        <div><b>2.1K</b><span>MEMBERS</span></div>
        <div><b>118</b><span>DROPS TRACKED</span></div>
      </div>
    </div>
    <div class="about-copy">
      <p>DEADSTOCK started as a school project: an image board built around one idea &mdash; the fit is only half the story. The other half is where you found it, how long you waited, and who else was in line with you.</p>
      <p>Right now this is a prototype. Every photo, comment, and profile you see is hardcoded so we can nail the layout before wiring up a real database. Once the backend lands, this page's numbers, the feed, and the profile system will run on MySQL through phpMyAdmin instead of static arrays.</p>
      <p>The site is built with plain PHP, no framework, so the structure stays easy to follow as we add features: accounts, real uploads, likes that persist, and comment threads that save.</p>

      <div class="value-list">
        <div class="row">
          <span class="num">01</span>
          <div><h3>Post the find</h3><p>Thrifted, restocked, hand-painted &mdash; if it's a fit, it belongs on the feed.</p></div>
        </div>
        <div class="row">
          <span class="num">02</span>
          <div><h3>Tag the story</h3><p>Where it came from and what it took to get it matters as much as the piece itself.</p></div>
        </div>
        <div class="row">
          <span class="num">03</span>
          <div><h3>Keep it hardcoded, for now</h3><p>This build is a front-end prototype. The database, accounts, and real uploads come next.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
