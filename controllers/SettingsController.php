<?php
class SettingsController
{
    public static function edit(): void
    {
        require_once dirname(__DIR__) . '/controllers/ProfilePageContext.php';
        $profileContext = ProfilePageContext::loadOwnProfile();
        $viewer = $profileContext['viewer'];
        $user_posts = $profileContext['user_posts'];
        $profile = $profileContext['profile'];
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/models/User.php';
        require_once dirname(__DIR__) . '/services/ProfileService.php';
        require_once dirname(__DIR__) . '/support/View.php';

        $errors = [];
        $success = false;
        $notice = '';

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
                if (User::usernameExists($username, (int) $viewer['id'])) {
                    $errors[] = 'That username is already taken.';
                }
            }

            $avatarUpload = $_FILES['avatar'] ?? null;
            if ($avatarUpload !== null && !is_array($avatarUpload)) {
                $errors[] = 'There was a problem uploading that file. Please try again.';
            }

            if (empty($errors)) {
                try {
                    $profileUpdate = ProfileService::updateProfile(
                        (int) $viewer['id'],
                        $username,
                        $bio !== '' ? $bio : null,
                        $avatarUpload,
                        $viewer['avatar'] ?? 'default.png'
                    );
                    if ($profileUpdate['avatarFilename'] !== null) {
                        $viewer['avatar'] = $profileUpdate['avatarFilename'];
                    }
                    $notice = $profileUpdate['notice'];

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
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                } catch (RuntimeException $e) {
                    error_log('Profile update failed: ' . $e->getMessage());
                    $errors[] = 'Something went wrong saving your profile picture. Please try again.';
                }
            }
        }

        View::renderPage('profile/settings', array_merge(
            $profileContext,
            compact('viewer', 'profile', 'success', 'notice', 'errors', 'username', 'bio')
        ));
    }
}
