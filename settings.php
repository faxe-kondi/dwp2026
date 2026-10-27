<?php
require __DIR__ . '/includes/profile-bootstrap.php';
require_once __DIR__ . '/includes/Csrf.php';

$errors = [];
$success = false;

$username = $viewer['username'];
$bio = $viewer['bio'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    $username = trim($_POST['username'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
    }

    if (strlen($bio) > 500) {
        $errors[] = 'Bio must be 500 characters or fewer.';
    }

    if (empty($errors)) {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $viewer['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    $newAvatarFilename = null;
    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            if (in_array($_FILES['avatar']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $errors[] = 'That image is too large for the server to accept. Try a smaller file.';
            } else {
                $errors[] = 'There was a problem uploading that file. Please try again.';
            }
        } else {
            $maxBytes = 5 * 1024 * 1024;
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if ($_FILES['avatar']['size'] > $maxBytes) {
                $errors[] = 'Profile picture must be 5MB or smaller.';
            } else {
                $info = @getimagesize($_FILES['avatar']['tmp_name']);
                if ($info === false || !isset($allowedMimes[$info['mime']])) {
                    $errors[] = 'Profile picture must be a JPG, PNG, or WEBP image.';
                } else {
                    $newAvatarFilename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$info['mime']];
                }
            }
        }
    }

    if (empty($errors)) {
        $pdo = Database::connect();
        $uploadDir = __DIR__ . '/uploads/avatars/';

        try {
            if ($newAvatarFilename !== null) {
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    throw new RuntimeException('Could not create the uploads directory.');
                }
                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newAvatarFilename)) {
                    throw new RuntimeException('Could not save the uploaded file.');
                }

                $stmt = $pdo->prepare('UPDATE users SET username = ?, bio = ?, avatar = ? WHERE id = ?');
                $stmt->execute([$username, $bio !== '' ? $bio : null, $newAvatarFilename, $viewer['id']]);

                if (!empty($viewer['avatar']) && $viewer['avatar'] !== 'default.png') {
                    $oldPath = $uploadDir . basename($viewer['avatar']);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $viewer['avatar'] = $newAvatarFilename;
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, bio = ? WHERE id = ?');
                $stmt->execute([$username, $bio !== '' ? $bio : null, $viewer['id']]);
            }

            $_SESSION['username'] = $username;

            $viewer['username'] = $username;
            $viewer['bio'] = $bio !== '' ? $bio : null;
            $profile = ds_profile_view($viewer, count($user_posts));
            $success = true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'That username was just taken — please try again.';
            } else {
                error_log('Settings update failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving your changes. Please try again.';
            }
        } catch (RuntimeException $e) {
            error_log('Avatar upload failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your profile picture. Please try again.';
        }
    }
}

$active = 'profile';
$page_title = 'Settings';
include __DIR__ . '/includes/header.php';
?>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap">

    <?php $profile_tab = 'settings'; include __DIR__ . '/includes/profile-header.php'; ?>

    <div class="wrap" style="max-width: 560px; padding: 0;">

      <?php if ($success): ?>
        <div class="form-success">Saved — your profile's up to date.</div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="form-errors">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="settings.php" enctype="multipart/form-data" autocomplete="off">
        <?= Csrf::field(); ?>

        <div class="field">
          <label>Profile picture</label>
          <button type="button" class="avatar-picker-preview" id="avatarPickerBtn" aria-label="Change profile picture">
            <img src="<?= htmlspecialchars($profile['avatar_url']); ?>" alt="Current profile picture" id="avatarPreviewImg">
            <span class="avatar-picker-edit">Change</span>
          </button>
          <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp" style="display:none;">
          <p class="field-hint" id="avatarFileName">JPG, PNG, or WEBP &mdash; up to 5MB.</p>
        </div>

        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" autocomplete="username" required>
          <p class="field-hint">3&ndash;30 characters &mdash; letters, numbers, and underscores only. Must be unique, same as at signup.</p>
        </div>

        <div class="field">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" rows="4" maxlength="500" placeholder="Tell people what you're about..."><?= htmlspecialchars($bio); ?></textarea>
          <p class="field-hint">Up to 500 characters.</p>
        </div>

        <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Save changes</button>
      </form>

    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
