<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
Auth::requireLogin();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/PostService.php';

$errors = [];
$caption = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    $caption = trim($_POST['caption'] ?? '');

    try {
        $postId = PostService::createPost(
            (int) Auth::user()['id'],
            $caption,
            $_FILES['photo'] ?? null
        );
        header('Location: post.php?id=' . $postId);
        exit;
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (RuntimeException $e) {
        error_log('Post upload failed: ' . $e->getMessage());
        $errors[] = 'Something went wrong saving your post. Please try again.';
    } catch (PDOException $e) {
        error_log('Post creation failed: ' . $e->getMessage());
        $errors[] = 'Something went wrong saving your post. Please try again.';
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
          <textarea id="caption" name="caption" rows="6" maxlength="750" placeholder="Tell the story behind the fit..."><?= htmlspecialchars($caption); ?></textarea>
          <p class="field-hint">Up to 750 characters. Optional.</p>
        </div>

        <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Drop it</button>
      </div>
    </form>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>