<?php
$active = 'reports';
$page_title = 'Report details';
View::render('layout/header', get_defined_vars());
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="reports.php?category=<?= rawurlencode($type); ?>">Reports / <?= htmlspecialchars(ucfirst($type)); ?></a> / Details</div>
    <h1>Report details</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap reports-page">
    <a class="btn report-back-link" href="reports.php?category=<?= rawurlencode($type); ?>">Back to <?= htmlspecialchars($type); ?></a>

    <section class="reported-subject-preview">
      <?php if ($type === 'users'): ?>
        <a class="reported-subject-link reported-user-preview" href="<?= htmlspecialchars(ds_user_profile_url($subject['username'])); ?>">
          <img src="<?= htmlspecialchars(ds_user_avatar_url($subject, 96)); ?>" alt="">
          <div>
            <h2>@<?= htmlspecialchars($subject['username']); ?></h2>
            <p><?= htmlspecialchars(ucfirst($subject['role'])); ?><?= $subject['is_banned'] ? ' · Banned' : ''; ?></p>
            <?php if (!empty($subject['bio'])): ?><p><?= htmlspecialchars($subject['bio']); ?></p><?php endif; ?>
          </div>
        </a>
      <?php elseif ($type === 'posts'): ?>
        <a class="reported-subject-link reported-post-preview" href="post.php?id=<?= (int) $subject['id']; ?>">
          <img src="<?= htmlspecialchars(ds_post_image_url(ds_post_view($subject))); ?>" alt="<?= htmlspecialchars($subject['title']); ?>">
          <div>
            <h2><?= htmlspecialchars($subject['title']); ?></h2>
            <p>By @<?= htmlspecialchars($subject['username']); ?></p>
            <?php if (trim((string) $subject['caption']) !== ''): ?><p><?= nl2br(htmlspecialchars($subject['caption'])); ?></p><?php endif; ?>
          </div>
        </a>
      <?php else: ?>
        <a class="reported-subject-link" href="post.php?id=<?= (int) $subject['post_id']; ?>#comment-<?= (int) $subject['id']; ?>">
          <h2>Comment on <?= htmlspecialchars($subject['post_title']); ?></h2>
          <p class="reported-comment-preview"><?= nl2br(htmlspecialchars($subject['content'])); ?></p>
          <p>By @<?= htmlspecialchars($subject['username']); ?></p>
        </a>
      <?php endif; ?>
    </section>

    <?php if ($moderationStatus === 'dismissed'): ?>
      <div class="form-success" role="status">Pending reports were dismissed.</div>
    <?php elseif ($moderationStatus === 'banned'): ?>
      <div class="form-success" role="status">User banned and pending reports resolved.</div>
    <?php endif; ?>

    <?php if ($hasPendingReports): ?>
      <section class="report-review-actions" aria-label="Resolve pending reports">
        <h2>Resolve pending reports</h2>
        <div class="report-review-buttons">
          <form method="post" action="controllers/ModerateReportController.php">
            <?= Csrf::field(); ?>
            <input type="hidden" name="type" value="<?= htmlspecialchars($type); ?>">
            <input type="hidden" name="id" value="<?= (int) $subject['id']; ?>">
            <input type="hidden" name="action" value="dismiss">
            <button class="btn" type="submit">Dismiss reports</button>
          </form>

          <?php if ($type === 'users' && $canBanSubject): ?>
            <button
              class="btn btn-solid"
              type="button"
              data-ban-user
              data-user-id="<?= (int) $subject['id']; ?>"
              data-username="<?= htmlspecialchars($subject['username']); ?>"
              data-resolve-reports="true"
              data-user-banned="<?= $subject['is_banned'] ? 'true' : 'false'; ?>"
            ><?= $subject['is_banned'] ? 'Keep user banned and resolve reports' : 'Ban user and resolve reports'; ?></button>
          <?php elseif ($type !== 'users'): ?>
            <form method="post" action="controllers/ModerateReportController.php" onsubmit="return confirm('Remove this <?= $type === 'posts' ? 'post' : 'comment'; ?> and resolve the pending reports?');">
              <?= Csrf::field(); ?>
              <input type="hidden" name="type" value="<?= htmlspecialchars($type); ?>">
              <input type="hidden" name="id" value="<?= (int) $subject['id']; ?>">
              <input type="hidden" name="action" value="remove">
              <button class="btn btn-solid" type="submit">Remove <?= $type === 'posts' ? 'post' : 'comment'; ?> and resolve reports</button>
            </form>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <div class="section-head report-details-heading">
      <h2><?= htmlspecialchars($pageTitle); ?></h2>
      <span class="count-flag"><?= count($reports); ?> individual reports</span>
    </div>

    <?php if (!$reports): ?>
      <p class="search-empty">There are no pending reports for this subject.</p>
    <?php else: ?>
      <div class="individual-report-list">
        <?php foreach ($reports as $report): ?>
          <article class="individual-report">
            <h3><?= htmlspecialchars($report['reason']); ?></h3>
            <p class="individual-report-meta">
              Reported by <?= $report['reporter_username'] !== null ? '@' . htmlspecialchars($report['reporter_username']) : 'a deleted account'; ?>
              · <?= htmlspecialchars(date('M j, Y · g:i a', strtotime($report['created_at']))); ?>
            </p>
            <?php if (trim((string) $report['details']) !== ''): ?>
              <div class="individual-report-details">
                <span>Report details</span>
                <p><?= nl2br(htmlspecialchars($report['details'])); ?></p>
              </div>
            <?php else: ?>
              <p class="individual-report-no-details">No additional details were provided.</p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php View::render('layout/footer', get_defined_vars()); ?>
