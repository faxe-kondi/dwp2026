<?php
$active = 'about';
$page_title = 'About';
View::render('layout/header', get_defined_vars());
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
      <img src="uploads/posts/placeholder2.webp" alt="DEADSTOCK post">
      <div class="stat-row">
        <div><b><?= number_format($aboutStats['posts']); ?></b><span>POSTS</span></div>
        <div><b><?= number_format($aboutStats['members']); ?></b><span>MEMBERS</span></div>
        <div><b><?= number_format($aboutStats['comments']); ?></b><span>COMMENTS</span></div>
      </div>
    </div>
    <div class="about-copy">
      <section class="about-section">
        <h2>About DEADSTOCK</h2>
        <?php if ($description !== ''): ?>
          <p class="about-description"><?= nl2br(htmlspecialchars($description)); ?></p>
        <?php else: ?>
          <p class="about-empty">A description has not been added yet.</p>
        <?php endif; ?>
      </section>
      <section class="about-section about-rules">
        <h2>Community rules</h2>
        <?php if ($rules): ?>
          <ol><?php foreach ($rules as $rule): ?><li><?= htmlspecialchars($rule); ?></li><?php endforeach; ?></ol>
        <?php else: ?>
          <p class="about-empty">Community rules have not been added yet.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>
</section>
<?php View::render('layout/footer', get_defined_vars()); ?>
