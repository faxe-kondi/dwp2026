<?php
$active = 'login';
$page_title = 'Log In';
View::render('layout/header', get_defined_vars());
?>
<header class="page-head"><div class="wrap">
  <div class="crumb"><a href="index.php">Home</a> / Log In</div><h1>Welcome back</h1>
</div></header>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap" style="max-width: 560px;">
  <?php if (!empty($errors)): ?><div class="form-errors"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" action="login.php" autocomplete="on">
    <?= Csrf::field(); ?>
    <div class="field"><label for="identifier">Username or email</label><input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($identifier); ?>" autocomplete="username" required></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required></div>
    <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Log in</button>
  </form>
  <p class="proto-note">New here? <a href="signup.php">Create an account</a>.</p>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
