<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Comment.php';
require __DIR__ . '/includes/helpers.php';

$requestedQuery = $_GET['q'] ?? '';
$searchQuery = is_string($requestedQuery) ? trim($requestedQuery) : '';
$searchTypes = ['users', 'posts', 'comments'];
$requestedType = $_GET['type'] ?? '';
$searchType = is_string($requestedType) && in_array($requestedType, $searchTypes, true)
    ? $requestedType
    : '';
$hasValidQuery = $searchQuery !== '' && mb_strlen($searchQuery, 'UTF-8') <= 100;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
    ? $requestedPage
    : 1;
$resultsPerPage = 12;
$totalResults = 0;
$totalPages = 1;
$currentUser = Auth::user();
$viewerId = $currentUser ? (int) $currentUser['id'] : null;
$users = [];
$posts = [];
$comments = [];

if ($hasValidQuery && $searchType !== '') {
    switch ($searchType) {
        case 'users':
            $totalResults = User::countSearchResults($searchQuery, $viewerId);
            $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
            $page = min($page, $totalPages);
            $users = User::search(
                $searchQuery,
                $viewerId,
                $resultsPerPage,
                ($page - 1) * $resultsPerPage
            );
            break;
        case 'posts':
            $totalResults = Post::countSearchResults($searchQuery, $viewerId);
            $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
            $page = min($page, $totalPages);
            $posts = array_map(
                'ds_post_view',
                Post::search($searchQuery, $viewerId, $resultsPerPage, ($page - 1) * $resultsPerPage)
            );
            break;
        case 'comments':
            $totalResults = Comment::countSearchResults($searchQuery, $viewerId);
            $totalPages = max(1, (int) ceil($totalResults / $resultsPerPage));
            $page = min($page, $totalPages);
            $comments = Comment::search(
                $searchQuery,
                $viewerId,
                $resultsPerPage,
                ($page - 1) * $resultsPerPage
            );
            break;
    }
}

$active = 'search';
$page_title = 'Search';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <span class="ghost-num" aria-hidden="true">FIND</span>
    <div class="crumb"><a href="index.php">Home</a> / Search</div>
    <h1>Find your next fit</h1>
  </div>
</header>

<section class="page-body">
  <div class="wrap">
    <form class="search-page-form" method="get" action="search.php" role="search">
      <label class="sr-only" for="searchQuery">Search users, post titles, or comments</label>
      <input id="searchQuery" type="search" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="What are you looking for?" maxlength="100" required>
      <?php if ($searchType !== ''): ?>
        <input type="hidden" name="type" value="<?= htmlspecialchars($searchType); ?>">
      <?php endif; ?>
    </form>

    <div class="search-category-picker" aria-label="Choose what to search">
      <p class="search-category-prompt">Search for <strong><?= htmlspecialchars($searchQuery !== '' ? $searchQuery : '...'); ?></strong> in</p>
      <nav class="search-category-options" aria-label="Search category">
        <?php foreach ($searchTypes as $type): ?>
          <a
            class="search-category-option<?= $searchType === $type ? ' active' : ''; ?>"
            data-search-category="<?= htmlspecialchars($type); ?>"
            href="search.php?q=<?= rawurlencode($searchQuery); ?>&amp;type=<?= rawurlencode($type); ?>"
            <?= $searchType === $type ? 'aria-current="page"' : ''; ?>
          ><?= ucfirst($type); ?></a>
        <?php endforeach; ?>
      </nav>
    </div>

    <p class="search-status" role="status" aria-live="polite">
      <?php if ($searchQuery === ''): ?>
        Enter a search term, then choose a category.
      <?php elseif (!$hasValidQuery): ?>
        Searches are limited to 100 characters.
      <?php elseif ($searchType === ''): ?>
        Choose a category to see matching results.
      <?php else: ?>
        Results for "<?= htmlspecialchars($searchQuery); ?>" in <?= htmlspecialchars($searchType); ?>.
      <?php endif; ?>
    </p>

    <?php if ($hasValidQuery && $searchType !== ''): ?>
      <section class="search-results-section" aria-labelledby="searchResultsHeading">
        <div class="section-head">
          <h2 id="searchResultsHeading"><?= ucfirst($searchType); ?></h2>
          <?php
          $resultCount = $totalResults;
          ?>
          <span class="count-flag"><?= number_format($resultCount); ?> found</span>
        </div>

        <?php if ($searchType === 'users' && $users): ?>
          <div class="user-grid">
            <?php foreach ($users as $user): ?>
              <article class="user-card">
                <div class="user-card-inner">
                  <a href="<?= htmlspecialchars(ds_user_profile_url($user['username'])); ?>" class="user-card-avatar">
                    <img src="<?= htmlspecialchars(ds_user_avatar_url($user, 96)); ?>" alt="<?= htmlspecialchars($user['username']); ?>">
                  </a>
                  <a href="<?= htmlspecialchars(ds_user_profile_url($user['username'])); ?>" class="user-card-name">@<?= htmlspecialchars($user['username']); ?></a>
                  <p class="user-card-bio"><?= htmlspecialchars($user['bio'] ?? ''); ?></p>
                  <div class="user-card-stats">
                    <span><b><?= (int) $user['posts']; ?></b> posts</span>
                    <span><b><?= (int) $user['followers']; ?></b> followers</span>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php elseif ($searchType === 'posts' && $posts): ?>
          <div class="photo-grid">
            <?php foreach ($posts as $post) ds_photo_card($post); ?>
          </div>
        <?php elseif ($searchType === 'comments' && $comments): ?>
          <div class="search-comment-list">
            <?php foreach ($comments as $comment): ?>
              <article class="search-comment-result">
                <a class="search-comment-post-link" href="post.php?id=<?= (int) $comment['post_id']; ?>#comments" aria-label="View comment on <?= htmlspecialchars($comment['title']); ?>"></a>
                <a class="search-comment-author" href="<?= htmlspecialchars(ds_user_profile_url($comment['username'])); ?>">
                  <img src="<?= htmlspecialchars(ds_user_avatar_url($comment, 48)); ?>" alt="">
                  <span><?= htmlspecialchars($comment['username']); ?></span>
                </a>
                <span class="search-comment-copy">
                  <span>Commented on <b><?= htmlspecialchars($comment['title']); ?></b></span>
                  <span class="search-comment-text"><?= htmlspecialchars($comment['content']); ?></span>
                  <span class="search-comment-post-author">Post by <?= htmlspecialchars($comment['post_username']); ?></span>
                </span>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="search-empty">No <?= htmlspecialchars($searchType); ?> found for this search.</p>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
          <?php
          $pageWindow = 9;
          $firstPage = max(1, min($page - intdiv($pageWindow, 2), $totalPages - $pageWindow + 1));
          $lastPage = min($totalPages, $firstPage + $pageWindow - 1);
          $pageUrl = static function (int $pageNumber) use ($searchQuery, $searchType): string {
              return 'search.php?q=' . rawurlencode($searchQuery)
                  . '&type=' . rawurlencode($searchType)
                  . '&page=' . $pageNumber;
          };
          ?>
          <nav class="pagination search-pagination" aria-label="<?= ucfirst($searchType); ?> search pages">
            <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(1)); ?>" aria-label="First page" title="First page"<?= $page === 1 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&laquo;</a>
            <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))); ?>" aria-label="Previous page" title="Previous page"<?= $page === 1 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&lsaquo;</a>
            <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
              <a
                class="btn<?= $pageNumber === $page ? ' btn-solid' : ''; ?>"
                href="<?= htmlspecialchars($pageUrl($pageNumber)); ?>"
                <?= $pageNumber === $page ? 'aria-current="page"' : ''; ?>
              ><?= $pageNumber; ?></a>
            <?php endfor; ?>
            <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(min($totalPages, $page + 1))); ?>" aria-label="Next page" title="Next page"<?= $page === $totalPages ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&rsaquo;</a>
            <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl($totalPages)); ?>" aria-label="Last page" title="Last page"<?= $page === $totalPages ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&raquo;</a>
          </nav>
          <p class="search-page-status">Page <?= $page; ?> of <?= $totalPages; ?></p>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
