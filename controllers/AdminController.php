<?php
class AdminController
{
    public static function index(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        Auth::requireLogin();

        if (Auth::role() !== 'owner') {
            http_response_code(403);
            exit('Only the site owner can access the admin panel.');
        }

        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/models/SiteSettings.php';
        require_once dirname(__DIR__) . '/support/View.php';

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
                    SiteSettings::updateMany($settings);
                    header('Location: admin.php?saved=1');
                    exit;
                } catch (PDOException $e) {
                    error_log('Site settings update failed: ' . $e->getMessage());
                    $errors[] = 'Something went wrong saving the site settings. Please try again.';
                }
            }
        }

        View::renderPage('admin', compact('settings', 'success', 'errors'));
    }
}
