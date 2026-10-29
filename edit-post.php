<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
Auth::requireLogin();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/Image.php';
require_once __DIR__ . '/includes/helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = (int) Auth::user()['id'];
$row = $id > 0 ? Post::findById($id) : null;

if (!$row || (int) $row['user_id'] !== $userId) {
    http_response_code(404);
    exit('Post not found.');
}

$errors = [];
$caption = $row['caption'] ?? '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();
    $caption = trim($_POST['caption'] ?? '');

    if (strlen($caption) > 1000) {
        $errors[] = 'Caption must be 1000 characters or fewer.';
    }

    $newFilename = null;
    $upload = $_FILES['photo'] ?? null;
    if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $errors[] = 'That image is too large for the server to accept. Try a smaller file.';
            } else {
                $errors[] = 'There was a problem uploading that file. Please try again.';
            }
        } else {
            $newFilename = bin2hex(random_bytes(16)) . '.webp';
        }
    }

    $savedPath = null;
    $discardSavedUpload = static function () use (&$savedPath, $id): void {
        if ($savedPath !== null && is_file($savedPath) && !unlink($savedPath)) {
            error_log('Could not remove a staged image after editing post #' . $id . ' failed.');
        }
    };

    if (empty($errors)) {
        try {
            if ($newFilename !== null) {
                $uploadDir = __DIR__ . '/uploads/posts/';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    throw new RuntimeException('Could not create the uploads directory.');
                }
                $savedPath = $uploadDir . $newFilename;
                PostImage::saveSquare($upload['tmp_name'], $savedPath);
            }

            if (!Post::updateOwned($id, $userId, $newFilename, $caption !== '' ? $caption : null)) {
                $discardSavedUpload();
                http_response_code(404);
                exit('Post not found.');
            }

            if ($newFilename !== null) {
                $oldPath = __DIR__ . '/uploads/posts/' . basename($row['filename']);
                if (basename($row['filename']) !== $row['filename'] || (is_file($oldPath) && !unlink($oldPath))) {
                    error_log('Post image cleanup failed after editing post #' . $id . '.');
                    $notice = 'Post updated, but the previous image could not be removed.';
                }
            }

            if ($notice === '') {
                header('Location: post.php?id=' . $id);
                exit;
            }
            $row = Post::findById($id);
        } catch (InvalidArgumentException $e) {
            $discardSavedUpload();
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $discardSavedUpload();
            error_log('Post edit failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        } catch (RuntimeException $e) {
            $discardSavedUpload();
            error_log('Post edit failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your changes. Please try again.';
        }
    }
}

$active = '';
$page_title = 'Edit post';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="profile.php">Profile</a> / <a href="post.php?id=<?= $id; ?>">Post #<?= $id; ?></a> / Edit</div>
    <h1>Edit post</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap upload-wrap">
    <?php if (!empty($errors)): ?>
      <div class="form-errors">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($notice !== ''): ?>
      <div class="form-success"><?= htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <form method="post" action="edit-post.php?id=<?= $id; ?>" enctype="multipart/form-data" class="upload-layout">
      <?= Csrf::field(); ?>

      <div class="upload-photo">
        <div class="field">
          <div class="dropzone has-preview" id="dropzone">
            <img id="dropzonePreview" src="<?= htmlspecialchars(ds_post_image_url(ds_post_view($row))); ?>" alt="Current post image preview">
            <div class="icon">&#8593;</div>
            <h3>Drag a replacement here, or click to browse</h3>
            <p>JPG, PNG, or WEBP &middot; optimized on upload &middot; <span id="fileName">current image</span></p>
            <input type="file" name="photo" id="fileInput" accept="image/png,image/jpeg,image/webp" style="display:none;">
          </div>
        </div>
      </div>

      <div class="upload-fields">
        <div class="field">
          <label for="caption">Caption</label>
          <textarea id="caption" name="caption" rows="6" maxlength="1000" placeholder="Tell the story behind the fit..."><?= htmlspecialchars($caption); ?></textarea>
          <p class="field-hint">Up to 1000 characters. Optional.</p>
        </div>
        <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Save changes</button>
        <a href="post.php?id=<?= $id; ?>" class="btn" style="width:100%; justify-content:center; margin-top:14px;">Cancel</a>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
