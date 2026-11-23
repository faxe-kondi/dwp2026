<?php
class LoginController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/support/RateLimiter.php';
        require_once dirname(__DIR__) . '/models/User.php';

        $errors = [];
        $identifier = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verifyOrFail();

            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';

            if ($identifier === '' || $password === '') {
                $errors[] = 'Enter your username or email, and your password.';
            } else {
                $user = User::findForLogin($identifier);
                $loginSubject = ($user
                    ? 'user:' . (int) $user['id']
                    : 'identifier:' . mb_strtolower($identifier, 'UTF-8'))
                    . '|ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                $retryAfter = RateLimiter::check('login_failure', $loginSubject, 5, 900);

                if ($retryAfter > 0) {
                    $errors[] = 'Too many failed sign-in attempts. Try again in ' . RateLimiter::formatWait($retryAfter) . '.';
                } elseif (!$user || !password_verify($password, $user['password_hash'])) {
                    $retryAfter = RateLimiter::recordFailure('login_failure', $loginSubject, 5, 900);
                    if ($retryAfter > 0) {
                        $errors[] = 'Too many failed sign-in attempts. Try again in ' . RateLimiter::formatWait($retryAfter) . '.';
                    } else {
                        $errors[] = 'Incorrect username/email or password.';
                    }
                } else {
                    RateLimiter::clear('login_failure', $loginSubject);
                    if ($user['is_banned']) {
                        $errors[] = 'This account has been banned' . ($user['ban_reason'] ? ': ' . $user['ban_reason'] . '.' : '.');
                    } else {
                        Auth::login($user);
                        header('Location: index.php');
                        exit;
                    }
                }
            }
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('auth/login', compact('errors', 'identifier'));
    }
}
