<?php
$active = '';
$page_title = $title;
View::render('layout/header', get_defined_vars());
?>
<section class="page-body blocked-post-page"><div class="wrap">
  <div class="blocked-post-notice" role="status">
    <h1><?= htmlspecialchars($title); ?></h1>
    <p><?= htmlspecialchars($message); ?></p>
    <a class="btn btn-solid" href="<?= htmlspecialchars($actionUrl); ?>"><?= htmlspecialchars($actionLabel); ?></a>
  </div>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
