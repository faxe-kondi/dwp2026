<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
Auth::requireLogin();
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/Post.php';
require_once __DIR__ . '/includes/PostService.php';
require_once __DIR__ . '/includes/helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = (int) Auth::user()['id'];
$row = $id > 0 ? Post::findById($id) : null;

if (!$row || (int) $row['user_id'] !== $userId) {
    http_response_code(404);
    exit('Post not found.');
}

$errors = [];
$title = $row['title'];
$caption = $row['caption'] ?? '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();
    $submittedTitle = $_POST['title'] ?? '';
    $title = is_string($submittedTitle) ? trim($submittedTitle) : '';
    $submittedCaption = $_POST['caption'] ?? '';
    $caption = is_string($submittedCaption) ? trim($submittedCaption) : '';

    try {
        $result = PostService::updatePost(
            $id,
            $userId,
            $row['filename'],
            $title,
            $caption,
            $_FILES['photo'] ?? null
        );
        if (!$result['updated']) {
            http_response_code(404);
            exit('Post not found.');
        }

        $notice = $result['notice'];
        if ($notice === '') {
            header('Location: post.php?id=' . $id);
            exit;
        }
        $row = Post::findById($id);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (PDOException $e) {
        error_log('Post edit failed: ' . $e->getMessage());
        $errors[] = 'Something went wrong saving your changes. Please try again.';
    } catch (RateLimitException $e) {
        $errors[] = $e->getMessage();
    } catch (RuntimeException $e) {
        error_log('Post edit failed: ' . $e->getMessage());
        $errors[] = 'Something went wrong saving your changes. Please try again.';
    }
}

$active = '';
$page_title = 'Edit post';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="<?= htmlspecialchars(ds_user_profile_url(Auth::user()['username'])); ?>">Profile</a> / <a href="post.php?id=<?= $id; ?>">Post #<?= $id; ?></a> / Edit</div>
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
          <label for="title">Title</label>
          <input type="text" id="title" name="title" maxlength="150" required value="<?= htmlspecialchars($title); ?>">
          <p class="field-hint">Up to 150 characters. Required.</p>
        </div>
        <div class="field">
          <label for="caption">Caption</label>
          <textarea id="caption" name="caption" rows="6" maxlength="750" placeholder="Tell the story behind the fit..."><?= htmlspecialchars($caption); ?></textarea>
          <p class="field-hint">Up to 750 characters. Optional.</p>
        </div>
        <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Save changes</button>
        <a href="post.php?id=<?= $id; ?>" class="btn" style="width:100%; justify-content:center; margin-top:14px;">Cancel</a>
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
