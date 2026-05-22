<?php
require_once 'config/database.php';
require_once 'config/response.php';
require_once 'middleware/auth.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/GoogleAuthController.php';
require_once 'controllers/ProfileController.php';
require_once 'controllers/AppConfigController.php';
require_once 'controllers/ExploreController.php';
require_once 'controllers/UserController.php';
require_once 'config/app.php';

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
$method     = $_SERVER['REQUEST_METHOD'];

$basePath = '/apigoogle';
$path     = str_replace($basePath, '', $requestUri);

$segments = explode('/', trim($path, '/'));

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
        } elseif ($method === 'POST' && $subRoute === 'forgot-password') {
            $controller->forgotPassword();
        } elseif ($method === 'POST' && $subRoute === 'verify-otp') {
            $controller->verifyOtp();
        } elseif ($method === 'POST' && $subRoute === 'reset-password') {
            $controller->resetPassword();
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
        } elseif (in_array($method, ['POST', 'PUT']) && $subRoute === '') {
            $user = requireAuth($db);
            $controller->createOrUpdate($user);
        } elseif ($method === 'POST' && $subRoute === 'avatar') {
            $user = requireAuth($db);
            $controller->uploadAvatar($user);
        } elseif ($method === 'GET' && $subRoute === 'services') {
            $user = requireAuth($db);
            $controller->getServices($user);
        } elseif ($method === 'POST' && $subRoute === 'services') {
            $user = requireAuth($db);
            $controller->addService($user);
        } elseif ($subRoute === 'services' && isset($segments[2]) && is_numeric($segments[2])) {
            $user      = requireAuth($db);
            $serviceId = (int)$segments[2];

            if ($method === 'PUT') {
                $controller->updateService($user, $serviceId);
            } elseif ($method === 'DELETE') {
                $controller->deleteService($user, $serviceId);
            } else {
                Response::notFound();
            }
        } elseif ($method === 'GET' && $subRoute !== '') {
            requireAuth($db);
            $controller->getByUsername($subRoute);
        } else {
            Response::notFound();
        }
        break;

    // --- EXPLORE ---
    case 'explore':
        $user       = requireAuth($db);
        $controller = new ExploreController($db, $user['id']);

        if ($method === 'GET' && $subRoute === '') {
            $controller->search();
        } elseif ($method === 'GET' && $subRoute === 'suggest') {
            $controller->suggest();
        } else {
            Response::notFound();
        }
        break;

    // --- APP CONFIG ---
    case 'app-config':
        $controller = new AppConfigController($db);
        if ($method === 'GET') {
            $user = requireAuth($db);
            $controller->get();
        } elseif ($method === 'PUT') {
            $user = requireAuth($db);
            $controller->update($user);
        } else {
            Response::notFound();
        }
        break;

    // --- USER ---
    case 'user':
        $user       = requireAuth($db);
        $controller = new UserController($db);

        if ($method === 'GET' && $subRoute === 'me') {
            // GET /user/me
            $controller->me($user);
        } elseif ($method === 'PUT' && $subRoute === 'username') {
            // PUT /user/username
            $controller->updateUsername($user);
        } elseif ($method === 'PUT' && $subRoute === 'password') {
            // PUT /user/password
            $controller->updatePassword($user);
        } else {
            Response::notFound();
        }
        break;

    default:
        Response::notFound('Endpoint not found');
        break;
}
