<?php
require_once __DIR__ . '/Session.php';
class Auth
{
    private static ?array $currentUser = null;
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        Session::start();

        if (!empty($_SESSION['user_id'])) {
            self::$currentUser = [
                'id'       => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? null,
                'role'     => $_SESSION['role'] ?? 'user',
            ];
        }
    }

    public static function check(): bool
    {
        return self::$currentUser !== null;
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }

    public static function username(): ?string
    {
        return self::$currentUser['username'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$currentUser['role'] ?? null;
    }

    public static function login(array $userRow): void
    {
        Session::regenerate();
        $_SESSION['user_id']  = (int) $userRow['id'];
        $_SESSION['username'] = $userRow['username'];
        $_SESSION['role']     = $userRow['role'] ?? 'user';

        self::$currentUser = [
            'id'       => (int) $userRow['id'],
            'username' => $userRow['username'],
            'role'     => $userRow['role'] ?? 'user',
        ];
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$currentUser = null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }
}
