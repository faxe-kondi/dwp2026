<?php
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/../db.php';
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
            $stmt = Database::connect()->prepare(
                'SELECT users.id, users.username, users.is_banned, roles.name AS role
                 FROM users
                 JOIN roles ON roles.id = users.role_id
                 WHERE users.id = ?
                 LIMIT 1'
            );
            $stmt->execute([(int) $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || $user['is_banned']) {
                self::logout();
                return;
            }

            self::$currentUser = [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
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

    public static function canModerate(): bool
    {
        return in_array(self::role(), ['owner', 'moderator'], true);
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
