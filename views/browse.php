<?php
$active = 'browse';
$page_title = 'Browse';
View::render('layout/header', get_defined_vars());
?>
<header class="page-head"><div class="wrap">
  <span class="ghost-num" aria-hidden="true">ALL</span>
  <div class="crumb"><a href="index.php">Home</a> / Browse</div>
  <h1>Browse all pictures</h1>
</div></header>
<section class="page-body"><div class="wrap">
  <form class="browse-controls" method="get" action="browse.php">
    <label for="browseSort">Sort by</label>
    <select id="browseSort" name="sort">
      <option value="newest"<?= $sort === 'newest' ? ' selected' : ''; ?>>Newest</option>
      <option value="likes"<?= $sort === 'likes' ? ' selected' : ''; ?>>Most liked</option>
      <option value="comments"<?= $sort === 'comments' ? ' selected' : ''; ?>>Most commented</option>
      <option value="random"<?= $sort === 'random' ? ' selected' : ''; ?>>Random</option>
    </select>
    <button class="btn" type="submit">Apply</button>
  </form>
  <?php if ($posts): ?>
    <p class="pagination-status"><?= number_format($totalPosts); ?> pictures</p>
    <div class="photo-grid browse-grid"><?php foreach ($posts as $post) View::render('components/post-card', ['p' => $post, 'pinScope' => null]); ?></div>
    <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Browse pages">
        <?php if ($page > 1): ?><a class="btn" href="browse.php?page=<?= $page - 1; ?>&amp;<?= $sortQuery; ?>">Previous</a><?php endif; ?>
        <?php $firstPage = max(1, $page - 2); $lastPage = min($totalPages, $page + 2); ?>
        <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
          <a class="btn<?= $pageNumber === $page ? ' btn-solid' : ''; ?>" href="browse.php?page=<?= $pageNumber; ?>&amp;<?= $sortQuery; ?>" <?= $pageNumber === $page ? 'aria-current="page"' : ''; ?>><?= $pageNumber; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a class="btn" href="browse.php?page=<?= $page + 1; ?>&amp;<?= $sortQuery; ?>">Next</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state">No pictures yet. Be the first to post one.</div>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
