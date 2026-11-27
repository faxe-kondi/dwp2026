<?php
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isPrettyProfileRoute = is_string($requestPath)
    && preg_match('~(?:^|/)@([A-Za-z0-9_]{3,30})$~D', $requestPath, $routeMatch) === 1;

if ($isPrettyProfileRoute) {
    define('DS_PRETTY_PROFILE_ROUTE', true);
    $_GET['username'] = $routeMatch[1];
    $route = 'profile';
} else {
    $route = $_GET['_route'] ?? $_GET['route'] ?? 'home';
}

$routes = [
    'home' => ['HomeController', 'index'],
    'about' => ['AboutController', 'show'],
    'admin' => ['AdminController', 'index'],
    'blocked' => ['BlockedController', 'index'],
    'browse' => ['BrowseController', 'index'],
    'edit-post' => ['EditPostController', 'edit'],
    'following' => ['FollowingController', 'index'],
    'liked' => ['LikedController', 'index'],
    'login' => ['LoginController', 'show'],
    'logout' => ['LogoutController', 'logout'],
    'post' => ['PostController', 'show'],
    'profile' => ['ProfileController', 'show'],
    'report-details' => ['ReportDetailsController', 'show'],
    'reports' => ['ReportsController', 'index'],
    'search' => ['SearchController', 'index'],
    'settings' => ['SettingsController', 'edit'],
    'signup' => ['SignupController', 'show'],
    'upload' => ['UploadController', 'show'],
    'block' => ['BlockController', 'handle'],
    'follow' => ['FollowController', 'handle'],
    'like' => ['LikeController', 'handle'],
    'submit-report' => ['SubmitReportController', 'handle'],
    'moderate-report' => ['ModerateReportController', 'handle'],
    'moderate-user' => ['ModerateUserController', 'handle'],
];

if (!is_string($route) || !isset($routes[$route])) {
    http_response_code(404);
    exit('Page not found.');
}

[$controller, $action] = $routes[$route];
require_once __DIR__ . '/controllers/' . $controller . '.php';
$controller::$action();
