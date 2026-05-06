<?php
require_once 'config/database.php';
require_once 'config/response.php';
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/GoogleAuthController.php';
require_once 'controllers/ProfileController.php';
require_once 'controllers/AppConfigController.php';

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse URL
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path if needed (e.g., /api)
$basePath = '/apigoogle';
$path = str_replace($basePath, '', $requestUri);

// Route segments
$segments = explode('/', trim($path, '/'));
// segments[0] = 'auth' | 'profile' | 'app-config'
// segments[1] = sub-route

$resource = $segments[0] ?? '';
$subRoute = $segments[1] ?? '';

$db = Database::getInstance()->getConnection();

// =====================
//  ROUTING
// =====================
switch ($resource) {

    // --- AUTH ---
    case 'auth':
        $controller = new AuthController($db);
        if ($method === 'POST' && $subRoute === 'register') {
            $controller->register();
        } elseif ($method === 'POST' && $subRoute === 'login') {
            $controller->login();
        } elseif ($method === 'POST' && $subRoute === 'logout') {
            $user = requireAuth($db);
            $controller->logout($user);
        } elseif ($method === 'POST' && $subRoute === 'google') {
            $google = new GoogleAuthController($db);
            $google->handle();
        } else {
            Response::notFound();
        }
        break;

    // --- PROFILE ---
    case 'profile':
        $controller = new ProfileController($db);
        if ($method === 'GET' && $subRoute === '') {
            $user = requireAuth($db);
            $controller->getMyProfile($user);
        } elseif ($method === 'POST' && $subRoute === '') {
            $user = requireAuth($db);
            $controller->createOrUpdate($user);
        } elseif ($method === 'PUT' && $subRoute === '') {
            $user = requireAuth($db);
            $controller->createOrUpdate($user);
        } elseif ($method === 'GET' && $subRoute !== '') {
            // Private: GET /profile/{username} — harus login
            requireAuth($db);
            $controller->getByUsername($subRoute);
        } else {
            Response::notFound();
        }
        break;

    // --- APP CONFIG ---
    case 'app-config':
        $controller = new AppConfigController($db);
        if ($method === 'GET') {
            $controller->get();
        } elseif ($method === 'PUT') {
            $user = requireAuth($db);
            $controller->update($user);
        } else {
            Response::notFound();
        }
        break;

    default:
        Response::notFound('Endpoint not found');
        break;
}
