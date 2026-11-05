<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Post.php';
require __DIR__ . '/includes/helpers.php';

$postsPerPage = 24;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
    ? $requestedPage
    : 1;
$sortOptions = ['newest', 'likes', 'comments', 'random'];
$requestedSort = $_GET['sort'] ?? 'newest';
$sort = is_string($requestedSort) && in_array($requestedSort, $sortOptions, true)
    ? $requestedSort
    : 'newest';
$requestedSeed = filter_input(INPUT_GET, 'seed', FILTER_VALIDATE_INT);
$randomSeed = $requestedSeed !== false
    && $requestedSeed !== null
    && $requestedSeed > 0
    && $requestedSeed <= 2147483647
        ? $requestedSeed
        : random_int(1, 2147483647);
if ($sort !== 'random') {
    $randomSeed = 1;
}
$currentUser = Auth::user();
$viewerId = $currentUser ? (int) $currentUser['id'] : null;
$totalPosts = Post::countAll($viewerId);
$totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
$page = min($page, $totalPages);
$posts = array_map(
    'ds_post_view',
    Post::findBrowsePage($postsPerPage, ($page - 1) * $postsPerPage, $viewerId, $sort, $randomSeed)
);
$sortQuery = 'sort=' . rawurlencode($sort);
if ($sort === 'random') {
    $sortQuery .= '&seed=' . $randomSeed;
}

$active = 'browse';
$page_title = 'Browse';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <span class="ghost-num" aria-hidden="true">ALL</span>
    <div class="crumb"><a href="index.php">Home</a> / Browse</div>
    <h1>Browse all pictures</h1>
  </div>
</header>

<section class="page-body">
  <div class="wrap">
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
      <div class="photo-grid browse-grid">
        <?php foreach ($posts as $post) ds_photo_card($post); ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Browse pages">
          <?php if ($page > 1): ?>
            <a class="btn" href="browse.php?page=<?= $page - 1; ?>&amp;<?= $sortQuery; ?>">Previous</a>
          <?php endif; ?>

          <?php
          $firstPage = max(1, $page - 2);
          $lastPage = min($totalPages, $page + 2);
          for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++):
          ?>
            <a
              class="btn<?= $pageNumber === $page ? ' btn-solid' : ''; ?>"
              href="browse.php?page=<?= $pageNumber; ?>&amp;<?= $sortQuery; ?>"
              <?= $pageNumber === $page ? 'aria-current="page"' : ''; ?>
            ><?= $pageNumber; ?></a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a class="btn" href="browse.php?page=<?= $page + 1; ?>&amp;<?= $sortQuery; ?>">Next</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="empty-state">No pictures yet. Be the first to post one.</div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
