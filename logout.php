<?php
require_once __DIR__ . '/includes/Auth.php';
Auth::boot();

Auth::logout();

header('Location: index.php');
exit;
