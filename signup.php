<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/db.php';
$pdo = Database::connect();

$errors = [];
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = 'Password must contain at least one letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email is already registered.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
                $stmt->execute([$username, $email, $hash]);

                Auth::login([
                    'id'       => $pdo->lastInsertId(),
                    'username' => $username,
                    'role'     => 'user',
                ]);
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'That username or email was just taken — please try again.';
                } else {
                    error_log('Signup insert failed: ' . $e->getMessage());
                    $errors[] = 'Something went wrong creating your account. Please try again.';
                }
            }
        }
    }
}

$active = 'signup';
$page_title = 'Sign Up';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / Sign Up</div>
    <h1>Make an account</h1>
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

    <form method="post" action="signup.php" autocomplete="on">
      <?= Csrf::field(); ?>

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" autocomplete="username" required>
        <p class="field-hint">3&ndash;30 characters &mdash; letters, numbers, and underscores only.</p>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>" autocomplete="email" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <p class="field-hint">8+ characters, with a letter, a number, and a special character.</p>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Create account</button>
    </form>

    <p class="proto-note">Already got an account? <a href="login.php">Log in</a>.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
