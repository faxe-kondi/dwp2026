<?php
$active = 'signup';
$page_title = 'Sign Up';
View::render('layout/header', get_defined_vars());
?>
<header class="page-head"><div class="wrap">
  <div class="crumb"><a href="index.php">Home</a> / Sign Up</div><h1>Make an account</h1>
</div></header>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap" style="max-width: 560px;">
  <?php if (!empty($errors)): ?><div class="form-errors"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" action="index.php?route=signup" autocomplete="on">
    <?= Csrf::field(); ?>
    <div class="field"><label for="username">Username</label><input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" autocomplete="username" required><p class="field-hint">3&ndash;30 characters &mdash; letters, numbers, and underscores only.</p></div>
    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" autocomplete="email" required></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="new-password" required><p class="field-hint">8+ characters, with a letter, a number, and a special character.</p></div>
    <div class="field"><label for="confirm_password">Confirm password</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required></div>
    <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Create account</button>
  </form>
  <p class="proto-note">Already got an account? <a href="index.php?route=login">Log in</a>.</p>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
