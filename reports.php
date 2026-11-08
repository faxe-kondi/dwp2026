<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();

if (!Auth::check() || !Auth::canModerate()) {
    http_response_code(403);
    exit('Only moderators and the site owner can view reported content.');
}

require_once __DIR__ . '/includes/Report.php';
require_once __DIR__ . '/includes/helpers.php';

$threshold = 3;
$itemsPerPage = 12;
$categories = ['users' => 'Users', 'posts' => 'Posts', 'comments' => 'Comments'];
$requestedCategory = $_GET['category'] ?? '';
$category = is_string($requestedCategory) && isset($categories[$requestedCategory])
    ? $requestedCategory
    : '';
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $requestedPage !== false && $requestedPage !== null && $requestedPage > 0
    ? $requestedPage
    : 1;

if ($category !== '') {
    Report::markCategoryChecked((int) Auth::user()['id'], $category);
}
$newReports = Report::getUnreadSubjectCounts((int) Auth::user()['id'], $threshold);
$results = $category !== ''
    ? Report::getFlaggedSubjects(
        $category,
        $threshold,
        $itemsPerPage,
        ($page - 1) * $itemsPerPage
    )
    : ['items' => [], 'total' => 0];
$totalPages = max(1, (int) ceil($results['total'] / $itemsPerPage));
if ($category !== '' && $page > $totalPages) {
    $page = $totalPages;
    $results = Report::getFlaggedSubjects(
        $category,
        $threshold,
        $itemsPerPage,
        ($page - 1) * $itemsPerPage
    );
}

$pageUrl = static function (int $pageNumber) use ($category): string {
    return 'reports.php?' . http_build_query([
        'category' => $category,
        'page' => $pageNumber,
    ]);
};
$renderPagination = static function (int $page, int $totalPages) use ($pageUrl, $category): void {
    if ($totalPages <= 1) {
        return;
    }

    $pageWindow = 9;
    $firstPage = max(1, min($page - intdiv($pageWindow, 2), $totalPages - $pageWindow + 1));
    $lastPage = min($totalPages, $firstPage + $pageWindow - 1);
    ?>
    <nav class="pagination search-pagination" aria-label="<?= htmlspecialchars(ucfirst($category)); ?> report pages">
      <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(1)); ?>" aria-label="First page" title="First page"<?= $page === 1 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&laquo;</a>
      <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))); ?>" aria-label="Previous page" title="Previous page"<?= $page === 1 ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&lsaquo;</a>
      <?php for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++): ?>
        <a class="btn<?= $pageNumber === $page ? ' btn-solid' : ''; ?>" href="<?= htmlspecialchars($pageUrl($pageNumber)); ?>" <?= $pageNumber === $page ? 'aria-current="page"' : ''; ?>><?= $pageNumber; ?></a>
      <?php endfor; ?>
      <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl(min($totalPages, $page + 1))); ?>" aria-label="Next page" title="Next page"<?= $page === $totalPages ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&rsaquo;</a>
      <a class="btn pagination-arrow" href="<?= htmlspecialchars($pageUrl($totalPages)); ?>" aria-label="Last page" title="Last page"<?= $page === $totalPages ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>&raquo;</a>
    </nav>
    <p class="search-page-status">Page <?= $page; ?> of <?= $totalPages; ?></p>
    <?php
};

$active = 'reports';
$page_title = 'Reported content';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / Reports</div>
    <h1>Reported content</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap reports-page">
    <?php if (($_GET['moderation'] ?? '') === 'removed'): ?>
      <div class="form-success" role="status">
        The <?= $category === 'posts' ? 'post' : 'comment'; ?> was removed and its pending reports were resolved.
      </div>
      <?php if (($_GET['cleanup_failed'] ?? '') === '1'): ?>
        <div class="form-errors" role="alert">The post was removed, but its uploaded image could not be deleted.</div>
      <?php endif; ?>
    <?php endif; ?>
    <p class="reports-intro">Items below have at least <?= $threshold; ?> unresolved reports. Counts include pending reports only.</p>

    <div class="search-category-picker" aria-label="Reported content categories">
      <span class="search-category-prompt">Review reports:</span>
      <div class="search-category-options">
        <?php foreach ($categories as $key => $label): ?>
          <a
            class="search-category-option<?= $category === $key ? ' active' : ''; ?>"
            href="reports.php?category=<?= rawurlencode($key); ?>"
            <?= $category === $key ? 'aria-current="page"' : ''; ?>
          ><?= htmlspecialchars($label); ?><?php if ($newReports[$key] > 0): ?> <span class="report-tab-count"><?= number_format($newReports[$key]); ?></span><?php endif; ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($category === ''): ?>
      <p class="search-empty">Choose Posts, Users, or Comments to review reported items.</p>
    <?php else: ?>
      <section class="search-results-section reports-section" id="<?= htmlspecialchars($category); ?>" aria-labelledby="reportsHeading">
        <div class="section-head">
          <h2 id="reportsHeading"><?= htmlspecialchars($categories[$category]); ?></h2>
          <span class="count-flag"><?= number_format($results['total']); ?> flagged</span>
        </div>

        <?php if (!$results['items']): ?>
          <p class="search-empty">No <?= htmlspecialchars($category); ?> currently meet the report threshold.</p>
        <?php elseif ($category === 'users'): ?>
          <div class="user-grid">
            <?php foreach ($results['items'] as $user): ?>
              <a class="user-card reported-user-card" href="report-details.php?type=users&amp;id=<?= (int) $user['id']; ?>" aria-label="View reports for @<?= htmlspecialchars($user['username']); ?>">
                <div class="user-card-inner">
                  <span class="user-card-avatar"><img src="<?= htmlspecialchars(ds_user_avatar_url($user, 96)); ?>" alt=""></span>
                  <span class="user-card-name">@<?= htmlspecialchars($user['username']); ?></span>
                  <p class="user-card-bio"><?= htmlspecialchars($user['bio'] ?? ''); ?></p>
                  <div class="user-card-stats">
                    <span><b><?= (int) $user['posts']; ?></b> posts</span>
                    <span><b><?= (int) $user['followers']; ?></b> followers</span>
                  </div>
                  <div class="reported-subject-meta">
                    <span class="reported-count"><?= (int) $user['report_count']; ?> reports</span>
                    <span class="reported-reasons"><?= htmlspecialchars($user['reasons']); ?></span>
                    <span><?= htmlspecialchars(ucfirst($user['role'])); ?><?= $user['is_banned'] ? ' · Banned' : ''; ?></span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php elseif ($category === 'posts'): ?>
          <div class="photo-grid">
            <?php foreach ($results['items'] as $post): ?>
              <a class="photo-card reported-photo-card" href="report-details.php?type=posts&amp;id=<?= (int) $post['id']; ?>" aria-label="View reports for <?= htmlspecialchars($post['title']); ?>">
                <div class="photo-card-inner">
                  <span class="frame">
                    <img src="<?= htmlspecialchars(ds_post_image_url(ds_post_view($post))); ?>" alt="<?= htmlspecialchars($post['title']); ?>" loading="lazy">
                  </span>
                  <div class="photo-meta">
                    <span class="who">
                      <img src="<?= htmlspecialchars(ds_user_avatar_url(['username' => $post['username'], 'avatar' => $post['avatar']], 48)); ?>" alt="">
                      <span>@<?= htmlspecialchars($post['username']); ?></span>
                    </span>
                    <span class="reported-post-title"><?= htmlspecialchars($post['title']); ?></span>
                    <?php if (trim((string) $post['caption']) !== ''): ?>
                      <p class="caption"><?= htmlspecialchars($post['caption']); ?></p>
                    <?php endif; ?>
                    <div class="reported-subject-meta">
                      <span class="reported-count"><?= (int) $post['report_count']; ?> reports</span>
                      <span class="reported-reasons"><?= htmlspecialchars($post['reasons']); ?></span>
                    </div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="search-comment-list">
            <?php foreach ($results['items'] as $comment): ?>
              <article class="search-comment-result reported-comment-result">
                <a class="search-comment-post-link" href="report-details.php?type=comments&amp;id=<?= (int) $comment['id']; ?>" aria-label="View reports for comment on <?= htmlspecialchars($comment['post_title']); ?>"></a>
                <a class="search-comment-author" href="<?= htmlspecialchars(ds_user_profile_url($comment['username'])); ?>">
                  <img src="<?= htmlspecialchars(ds_user_avatar_url(['username' => $comment['username'], 'avatar' => $comment['avatar'] ?? 'default.png'], 48)); ?>" alt="">
                  <span><?= htmlspecialchars($comment['username']); ?></span>
                </a>
                <span class="search-comment-copy">
                  <span>Comment on <b><?= htmlspecialchars($comment['post_title']); ?></b></span>
                  <span class="search-comment-text"><?= htmlspecialchars($comment['content']); ?></span>
                  <span class="search-comment-post-author"><?= (int) $comment['report_count']; ?> reports · <?= htmlspecialchars($comment['reasons']); ?></span>
                </span>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php $renderPagination($page, $totalPages); ?>
      </section>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
