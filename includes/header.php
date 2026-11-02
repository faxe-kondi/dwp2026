<?php
if (!isset($active)) { $active = ''; }
require_once __DIR__ . '/Auth.php';
Auth::boot();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — DEADSTOCK' : 'DEADSTOCK — Fits worth camping for'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Work+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css'); ?>">
<script>document.documentElement.classList.add('js-ready');</script>
</head>
<body>

<div class="grain-overlay" aria-hidden="true"></div>

<nav class="site-nav">
  <div class="wrap">
    <a href="index.php" class="brand">DEAD<span>STOCK</span></a>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" class="<?= $active === 'home' ? 'active' : ''; ?>">Home</a></li>
      <li><a href="browse.php" class="<?= $active === 'browse' ? 'active' : ''; ?>">Browse</a></li>
      <li><a href="search.php" class="<?= $active === 'search' ? 'active' : ''; ?>">Search</a></li>
      <li><a href="about.php" class="<?= $active === 'about' ? 'active' : ''; ?>">About</a></li>
      <?php if (Auth::check()): ?>
        <li><a href="profile.php" class="<?= $active === 'profile' ? 'active' : ''; ?>">Profile</a></li>
        <li><a href="upload.php" class="btn btn-solid <?= $active === 'upload' ? 'active' : ''; ?>" style="padding:9px 16px; font-size:13px;">+ Upload</a></li>
        <li><a href="logout.php" class="<?= $active === 'logout' ? 'active' : ''; ?>">Log Out</a></li>
      <?php else: ?>
        <li><a href="login.php" class="<?= $active === 'login' ? 'active' : ''; ?>">Log In</a></li>
        <li><a href="signup.php" class="btn <?= $active === 'signup' ? 'active' : ''; ?>" style="padding:9px 16px; font-size:13px;">Sign Up</a></li>
      <?php endif; ?>
    </ul>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">&#9776;</button>
  </div>
</nav>

<?php
$ticker_items = ['NEW DROPS DAILY', 'POST YOUR FIT', '<b>DEADSTOCK</b> PROTOTYPE BUILD', 'THRIFTED &gt; RETAIL'];
$ticker_repeats = 8;
?>
<div class="ticker" aria-hidden="true">
  <div class="ticker-row">
    <div class="ticker-track">
      <?php for ($r = 0; $r < $ticker_repeats; $r++): ?>
        <?php foreach ($ticker_items as $item): ?><span><?= $item; ?></span><?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>
</div>