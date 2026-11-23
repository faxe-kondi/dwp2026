<?php
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function check(): bool
    {
        $submitted = $_POST['csrf_token'] ?? '';
        $expected  = $_SESSION['csrf_token'] ?? '';
        return $expected !== '' && hash_equals($expected, $submitted);
    }

    public static function verifyOrFail(): void
    {
        if (!self::check()) {
            http_response_code(403);
            die('Your session expired or this form was submitted incorrectly. Please go back and try again.');
        }
    }
}
