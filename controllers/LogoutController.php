<?php
class LogoutController
{
    public static function logout(): void
    {
        require_once dirname(__DIR__) . '/support/Auth.php';
        Auth::boot();

        Auth::logout();

        header('Location: index.php');
        exit;
    }
}
