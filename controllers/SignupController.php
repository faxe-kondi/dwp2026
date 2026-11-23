<?php
class SignupController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();
        require_once dirname(__DIR__) . '/support/Csrf.php';
        require_once dirname(__DIR__) . '/models/User.php';

        $errors = [];
        $username = $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::verifyOrFail();

            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Za-z]/', $password)) {
                $errors[] = 'Password must contain at least one letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number.';
            } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character.';
            }

            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                if (User::usernameExists($username)) {
                    $errors[] = 'That username is already taken.';
                }

                if (User::emailExists($email)) {
                    $errors[] = 'That email is already registered.';
                }

                if (empty($errors)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    try {
                        Auth::login([
                            'id'       => User::createAccount($username, $email, $hash),
                            'username' => $username,
                            'role'     => 'user',
                        ]);
                        header('Location: index.php');
                        exit;
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            $errors[] = 'That username or email was just taken — please try again.';
                        } else {
                            error_log('Signup insert failed: ' . $e->getMessage());
                            $errors[] = 'Something went wrong creating your account. Please try again.';
                        }
                    }
                }
            }
        }

        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('auth/signup', compact('errors', 'username', 'email'));
    }
}
