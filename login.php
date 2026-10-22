<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/db.php';
$pdo = Database::connect();

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Enter your username or email, and your password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT users.id, users.username, users.password_hash, roles.name AS role, users.is_banned, users.ban_reason
             FROM users
             JOIN roles ON roles.id = users.role_id
             WHERE users.username = ? OR users.email = ?
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect username/email or password.';
        } elseif ($user['is_banned']) {
            $errors[] = 'This account has been banned' . ($user['ban_reason'] ? ': ' . $user['ban_reason'] . '.' : '.');
        } else {
            Auth::login($user);
            header('Location: index.php');
            exit;
        }
    }
}

$active = 'login';
$page_title = 'Log In';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / Log In</div>
    <h1>Welcome back</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap" style="max-width: 560px;">

    <?php if (!empty($errors)): ?>
      <div class="form-errors">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="on">
      <?= Csrf::field(); ?>

      <div class="field">
        <label for="identifier">Username or email</label>
        <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($identifier); ?>" autocomplete="username" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Log in</button>
    </form>

    <p class="proto-note">New here? <a href="signup.php">Create an account</a>.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
