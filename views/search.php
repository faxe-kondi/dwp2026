<?php
$active = 'search';
$page_title = 'Search';
View::render('layout/header', get_defined_vars());
?>
<header class="page-head"><div class="wrap"><span class="ghost-num" aria-hidden="true">FIND</span><div class="crumb"><a href="index.php">Home</a> / Search</div><h1>Find your next fit</h1></div></header>
<section class="page-body"><div class="wrap">
  <form class="search-page-form" method="get" action="search.php" role="search">
    <label class="sr-only" for="searchQuery">Search users, post titles, or comments</label>
    <input id="searchQuery" type="search" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="What are you looking for?" maxlength="100" required>
    <?php if ($searchType !== ''): ?><input type="hidden" name="type" value="<?= htmlspecialchars($searchType); ?>"><?php endif; ?>
  </form>
  <div class="search-category-picker" aria-label="Choose what to search">
    <p class="search-category-prompt">Search for <strong><?= htmlspecialchars($searchQuery !== '' ? $searchQuery : '...'); ?></strong> in</p>
    <nav class="search-category-options" aria-label="Search category">
      <?php foreach ($searchTypes as $type): ?>
        <a class="search-category-option<?= $searchType === $type ? ' active' : ''; ?>" data-search-category="<?= htmlspecialchars($type); ?>" href="search.php?q=<?= rawurlencode($searchQuery); ?>&amp;type=<?= rawurlencode($type); ?>" <?= $searchType === $type ? 'aria-current="page"' : ''; ?>><?= ucfirst($type); ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <p class="search-status" role="status" aria-live="polite">
    <?php if ($searchQuery === ''): ?>Enter a search term, then choose a category.
    <?php elseif (!$hasValidQuery): ?>Searches are limited to 100 characters.
    <?php elseif ($searchType === ''): ?>Choose a category to see matching results.
    <?php else: ?>Results for "<?= htmlspecialchars($searchQuery); ?>" in <?= htmlspecialchars($searchType); ?>.<?php endif; ?>
  </p>
  <?php if ($hasValidQuery && $searchType !== ''): ?>
    <section class="search-results-section" aria-labelledby="searchResultsHeading">
      <div class="section-head"><h2 id="searchResultsHeading"><?= ucfirst($searchType); ?></h2><span class="count-flag"><?= number_format($totalResults); ?> found</span></div>
      <?php if ($searchType === 'users' && $users): ?>
        <div class="user-grid"><?php foreach ($users as $user) View::render('components/user-card', ['u' => $user, 'context' => 'search']); ?></div>
      <?php elseif ($searchType === 'posts' && $posts): ?>
        <div class="photo-grid"><?php foreach ($posts as $post) View::render('components/post-card', ['p' => $post, 'pinScope' => null]); ?></div>
      <?php elseif ($searchType === 'comments' && $comments): ?>
        <div class="search-comment-list"><?php foreach ($comments as $comment): ?><article class="search-comment-result"><a class="search-comment-post-link" href="post.php?id=<?= (int) $comment['post_id']; ?>#comments" aria-label="View comment on <?= htmlspecialchars($comment['title']); ?>"></a><a class="search-comment-author" href="<?= htmlspecialchars(ds_user_profile_url($comment['username'])); ?>"><img src="<?= htmlspecialchars(ds_user_avatar_url($comment, 48)); ?>" alt=""><span><?= htmlspecialchars($comment['username']); ?></span></a><span class="search-comment-copy"><span>Commented on <b><?= htmlspecialchars($comment['title']); ?></b></span><span class="search-comment-text"><?= htmlspecialchars($comment['content']); ?></span><span class="search-comment-post-author">Post by <?= htmlspecialchars($comment['post_username']); ?></span></span></article><?php endforeach; ?></div>
      <?php else: ?>
        <p class="search-empty">No <?= htmlspecialchars($searchType); ?> found for this search.</p>
      <?php endif; ?>
      <?php if ($totalPages > 1): ?>
        <?php
        $pageWindow = 9;
        $firstPage = max(1, min($page - intdiv($pageWindow, 2), $totalPages - $pageWindow + 1));
        $lastPage = min($totalPages, $firstPage + $pageWindow - 1);
        $pageUrl = static function (int $pageNumber) use ($searchQuery, $searchType): string {
          return 'search.php?q=' . rawurlencode($searchQuery) . '&type=' . rawurlencode($searchType) . '&page=' . $pageNumber;
        };
        ?>
        <nav class="pagination search-pagination" aria-label="<?= ucfirst($searchType); ?> search pages">
          <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(1)); ?>">&laquo;</a>
          <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))); ?>">&lsaquo;</a>
          <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?><a class="btn<?= $pageNumber === $page ? ' btn-solid' : ''; ?>" href="<?= htmlspecialchars($pageUrl($pageNumber)); ?>" <?= $pageNumber === $page ? 'aria-current="page"' : ''; ?>><?= $pageNumber; ?></a><?php endfor; ?>
          <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(min($totalPages, $page + 1))); ?>">&rsaquo;</a>
          <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl($totalPages)); ?>">&raquo;</a>
        </nav>
        <p class="search-page-status">Page <?= $page; ?> of <?= $totalPages; ?></p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
