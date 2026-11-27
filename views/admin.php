<?php
$active = 'admin';
$page_title = 'Admin panel';
View::render('layout/header', get_defined_vars());
?>
<header class="page-head"><div class="wrap"><div class="crumb"><a href="index.php">Home</a> / Admin panel</div><h1>Site settings</h1></div></header>
<div class="stripe-bar" aria-hidden="true"></div>
<section class="page-body"><div class="wrap admin-settings">
  <p class="admin-intro">Manage the site's colors, About page, community rules, and contact details.</p>
  <?php if ($success): ?><div class="form-success" role="status">Site settings saved.</div><?php endif; ?>
  <?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach (array_unique($errors) as $error): ?><li><?= htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" action="index.php?route=admin">
    <?= Csrf::field(); ?>
    <section class="admin-section" aria-labelledby="admin-colors-heading">
      <h2 id="admin-colors-heading">Color scheme</h2><p class="field-hint">Choose the primary accent and secondary highlight colors used across the site.</p>
      <div class="admin-color-fields">
        <div class="field"><label for="color_primary">Primary color</label><input type="color" id="color_primary" name="color_primary" value="<?= htmlspecialchars($settings['color_primary']); ?>"></div>
        <div class="field"><label for="color_secondary">Secondary color</label><input type="color" id="color_secondary" name="color_secondary" value="<?= htmlspecialchars($settings['color_secondary']); ?>"></div>
      </div>
    </section>
    <section class="admin-section" aria-labelledby="admin-content-heading">
      <h2 id="admin-content-heading">About page</h2>
      <div class="field"><label for="description">About description</label><textarea id="description" name="description" rows="6" maxlength="600"><?= htmlspecialchars($settings['description']); ?></textarea><p class="field-hint">Up to 600 characters. Line breaks are shown on the About page.</p></div>
      <div class="field"><label for="rules">Community rules</label><textarea id="rules" name="rules" rows="8" maxlength="400"><?= htmlspecialchars($settings['rules']); ?></textarea><p class="field-hint">Enter one rule per line; numbering is added automatically.</p></div>
    </section>
    <section class="admin-section" aria-labelledby="admin-contact-heading">
      <h2 id="admin-contact-heading">Contact</h2><div class="field"><label for="contact">Contact details</label><textarea id="contact" name="contact" rows="4" maxlength="50"><?= htmlspecialchars($settings['contact']); ?></textarea><p class="field-hint">Displayed in the site footer. You can include an email address or other contact details.</p></div>
    </section>
    <button type="submit" class="btn btn-solid">Save site settings</button>
  </form>
</div></section>
<?php View::render('layout/footer', get_defined_vars()); ?>
