<?php
require __DIR__ . '/includes/data.php';
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
  <div class="wrap upload-layout">

    <div>
      <div class="dropzone" id="dropzone">
        <div class="icon">&#8593;</div>
        <h3>Drag a photo here, or click to browse</h3>
        <p>JPG or PNG, up to 10MB &middot; <span id="fileName">no file selected</span></p>
        <input type="file" id="fileInput" accept="image/*" style="display:none;">
      </div>
      <p class="proto-note">This uploader is a front-end mockup only. No file is actually sent or stored &mdash; that comes once the database is connected.</p>
    </div>

    <form data-proto>
      <div class="field">
        <label for="caption">Caption</label>
        <textarea id="caption" rows="4" placeholder="Tell the story behind the fit..."></textarea>
      </div>

      <div class="field">
        <label for="tags">Tags</label>
        <input type="text" id="tags" value="thrifted, archive" placeholder="e.g. thrifted, restock, diy">
        <div class="tag-input-preview">
          <span class="chip">#thrifted</span>
          <span class="chip">#archive</span>
        </div>
      </div>

      <div class="field">
        <label for="location">Location (optional)</label>
        <input type="text" id="location" placeholder="Where'd you find it?">
      </div>

      <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Drop it</button>
      <p class="field-hint proto-submit-note" style="margin-top:12px; text-align:center;"></p>
    </form>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
