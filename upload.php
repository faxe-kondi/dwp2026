<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
Auth::requireLogin();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/Image.php';

$errors = [];
$caption = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    $caption = trim($_POST['caption'] ?? '');

    if (strlen($caption) > 1000) {
        $errors[] = 'Caption must be 1000 characters or fewer.';
    }

    $newFilename = null;
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Choose a photo to post.';
    } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        if (in_array($_FILES['photo']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $errors[] = 'That image is too large for the server to accept. Try a smaller file.';
        } else {
            $errors[] = 'There was a problem uploading that file. Please try again.';
        }
    } else {
        $newFilename = bin2hex(random_bytes(16)) . '.webp';
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/uploads/posts/';

        try {
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('Could not create the uploads directory.');
            }
            PostImage::saveSquare($_FILES['photo']['tmp_name'], $uploadDir . $newFilename);

            $postId = Post::create((int) Auth::user()['id'], $newFilename, $caption !== '' ? $caption : null);

            header('Location: post.php?id=' . $postId);
            exit;
        } catch (InvalidArgumentException $e) {
            if (isset($uploadDir, $newFilename) && is_file($uploadDir . $newFilename) && !unlink($uploadDir . $newFilename)) {
                error_log('Could not remove an invalid post image upload.');
            }
            $errors[] = $e->getMessage();
        } catch (RuntimeException $e) {
            if (isset($uploadDir, $newFilename) && is_file($uploadDir . $newFilename) && !unlink($uploadDir . $newFilename)) {
                error_log('Could not remove a failed post image upload.');
            }
            error_log('Post upload failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your post. Please try again.';
        } catch (PDOException $e) {
            if (isset($uploadDir, $newFilename) && is_file($uploadDir . $newFilename) && !unlink($uploadDir . $newFilename)) {
                error_log('Could not remove a failed post image upload.');
            }
            error_log('Post creation failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving your post. Please try again.';
        }
    }
}

$active = 'upload';
$page_title = 'Upload';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / Upload</div>
    <h1>Post a fit</h1>
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

    <form method="post" action="upload.php" enctype="multipart/form-data" class="upload-layout">
      <?= Csrf::field(); ?>

      <div class="upload-photo">
        <div class="field">
          <div class="dropzone" id="dropzone">
            <img id="dropzonePreview" alt="Selected photo preview" hidden>
            <div class="icon">&#8593;</div>
            <h3>Drag a photo here, or click to browse</h3>
            <p>JPG, PNG, or WEBP &middot; optimized on upload &middot; <span id="fileName">no file selected</span></p>
            <input type="file" name="photo" id="fileInput" accept="image/png,image/jpeg,image/webp" required style="display:none;">
          </div>
        </div>
      </div>

      <div class="upload-fields">
        <div class="field">
          <label for="caption">Caption</label>
          <textarea id="caption" name="caption" rows="6" maxlength="1000" placeholder="Tell the story behind the fit..."><?= htmlspecialchars($caption); ?></textarea>
          <p class="field-hint">Up to 1000 characters. Optional.</p>
        </div>

        <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Drop it</button>
      </div>
    </form>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>