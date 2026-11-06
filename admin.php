<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();
Auth::requireLogin();

if (Auth::role() !== 'owner') {
    http_response_code(403);
    exit('Only the site owner can access the admin panel.');
}

require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/includes/SiteSettings.php';

$settings = [
    'description' => SiteSettings::get('description'),
    'rules' => SiteSettings::get('rules'),
    'contact' => SiteSettings::get('contact'),
    'color_primary' => SiteSettings::getColor('color_primary', '#FF5A1F'),
    'color_secondary' => SiteSettings::getColor('color_secondary', '#D6FF3F'),
];
$errors = [];
$success = isset($_GET['saved']) && $_GET['saved'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrFail();

    foreach (['description', 'rules', 'contact', 'color_primary', 'color_secondary'] as $key) {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            $errors[] = 'Please complete every settings field.';
            continue;
        }
        $settings[$key] = trim($_POST[$key]);
    }

    $maxLengths = [
        'description' => 600,
        'rules' => 400,
        'contact' => 50,
    ];
    foreach ($maxLengths as $key => $maxLength) {
        if (strlen($settings[$key]) > $maxLength) {
            $errors[] = ucfirst($key) . ' must be ' . number_format($maxLength) . ' characters or fewer.';
        }
    }

    foreach (['color_primary' => 'Primary', 'color_secondary' => 'Secondary'] as $key => $label) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $settings[$key])) {
            $errors[] = $label . ' color must be a valid six-digit hex color.';
        }
    }

    if (!$errors) {
        try {
            $pdo = Database::connect();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $pdo->commit();
            header('Location: admin.php?saved=1');
            exit;
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Site settings update failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong saving the site settings. Please try again.';
        }
    }
}

$active = 'admin';
$page_title = 'Admin panel';
include __DIR__ . '/includes/header.php';
?>

<header class="page-head">
  <div class="wrap">
    <div class="crumb"><a href="index.php">Home</a> / Admin panel</div>
    <h1>Site settings</h1>
  </div>
</header>

<div class="stripe-bar" aria-hidden="true"></div>

<section class="page-body">
  <div class="wrap admin-settings">
    <p class="admin-intro">Manage the site's colors, About page, community rules, and contact details.</p>

    <?php if ($success): ?>
      <div class="form-success" role="status">Site settings saved.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="form-errors" role="alert">
        <ul>
          <?php foreach (array_unique($errors) as $error): ?>
            <li><?= htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="admin.php">
      <?= Csrf::field(); ?>

      <section class="admin-section" aria-labelledby="admin-colors-heading">
        <h2 id="admin-colors-heading">Color scheme</h2>
        <p class="field-hint">Choose the primary accent and secondary highlight colors used across the site.</p>
        <div class="admin-color-fields">
          <div class="field">
            <label for="color_primary">Primary color</label>
            <input type="color" id="color_primary" name="color_primary" value="<?= htmlspecialchars($settings['color_primary']); ?>">
          </div>
          <div class="field">
            <label for="color_secondary">Secondary color</label>
            <input type="color" id="color_secondary" name="color_secondary" value="<?= htmlspecialchars($settings['color_secondary']); ?>">
          </div>
        </div>
      </section>

      <section class="admin-section" aria-labelledby="admin-content-heading">
        <h2 id="admin-content-heading">About page</h2>
        <div class="field">
          <label for="description">About description</label>
          <textarea id="description" name="description" rows="6" maxlength="600"><?= htmlspecialchars($settings['description']); ?></textarea>
          <p class="field-hint">Up to 600 characters. Line breaks are shown on the About page.</p>
        </div>

        <div class="field">
          <label for="rules">Community rules</label>
          <textarea id="rules" name="rules" rows="8" maxlength="400"><?= htmlspecialchars($settings['rules']); ?></textarea>
          <p class="field-hint">Enter one rule per line; numbering is added automatically.</p>
        </div>
      </section>

      <section class="admin-section" aria-labelledby="admin-contact-heading">
        <h2 id="admin-contact-heading">Contact</h2>
        <div class="field">
          <label for="contact">Contact details</label>
          <textarea id="contact" name="contact" rows="4" maxlength="50"><?= htmlspecialchars($settings['contact']); ?></textarea>
          <p class="field-hint">Displayed in the site footer. You can include an email address or other contact details.</p>
        </div>
      </section>

      <button type="submit" class="btn btn-solid">Save site settings</button>
    </form>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
