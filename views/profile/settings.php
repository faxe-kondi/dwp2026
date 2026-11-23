<?php
$active = 'profile';
$page_title = 'Settings';
View::render('layout/header', get_defined_vars());
?>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap">
  <?php $profile_tab = 'settings'; View::render('components/profile-header', get_defined_vars()); ?>
  <div class="wrap" style="max-width: 560px; padding: 0;">
    <?php if ($success): ?><div class="form-success">Saved — your profile's up to date.</div><?php endif; ?>
    <?php if ($notice !== ''): ?><div class="form-errors" role="status"><?= htmlspecialchars($notice); ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="form-errors"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="settings.php" enctype="multipart/form-data" autocomplete="off">
      <?= Csrf::field(); ?>
      <div class="field"><label>Profile picture</label><button type="button" class="avatar-picker-preview" id="avatarPickerBtn" aria-label="Change profile picture"><img src="<?= htmlspecialchars($profile['avatar_url']); ?>" alt="Current profile picture" id="avatarPreviewImg"><span class="avatar-picker-edit">Change</span></button><input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp" style="display:none;"><p class="field-hint" id="avatarFileName">JPG, PNG, or WEBP &mdash; up to 5MB.</p></div>
      <div class="field"><label for="username">Username</label><input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" autocomplete="username" required><p class="field-hint">3&ndash;30 characters &mdash; letters, numbers, and underscores only. Must be unique, same as at signup.</p></div>
      <div class="field"><label for="bio">Bio</label><textarea id="bio" name="bio" rows="4" maxlength="500" placeholder="Tell people what you're about..."><?= htmlspecialchars($bio); ?></textarea><p class="field-hint">Up to 500 characters.</p></div>
      <button type="submit" class="btn btn-solid" style="width:100%; justify-content:center; padding:16px;">Save changes</button>
    </form>
  </div>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
