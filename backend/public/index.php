<?php

declare(strict_types=1);

use App\Config\Config;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CategoryController;
use App\Controllers\ProductController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$request = Request::capture();

if ($request->path === '/api/health') {
    Response::json(['status' => 'ok']);
    exit;
}

$config = Config::load(dirname(__DIR__) . '/.env');
$database = new Database($config['db']);
$pdo = $database->connection();

$authService = new AuthService($config['app']['key']);
$userRepository = new UserRepository($pdo);
$productRepository = new ProductRepository($pdo);
$cartRepository = new CartRepository($pdo, $productRepository);

$authController = new AuthController($userRepository, $authService);
$productController = new ProductController($productRepository);
$categoryController = new CategoryController($productRepository);
$cartController = new CartController($cartRepository);
$authMiddleware = new AuthMiddleware($authService, $userRepository);

$router = new Router();

$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login', [$authController, 'login']);
$router->get('/api/auth/me', [$authController, 'me'], [$authMiddleware]);

$router->get('/api/products', [$productController, 'index']);
$router->get('/api/products/{id}', [$productController, 'show']);
$router->get('/api/categories', [$categoryController, 'index']);

$router->get('/api/cart', [$cartController, 'show'], [$authMiddleware]);
$router->post('/api/cart/items', [$cartController, 'storeItem'], [$authMiddleware]);
$router->patch('/api/cart/items/{id}', [$cartController, 'updateItem'], [$authMiddleware]);
$router->delete('/api/cart/items/{id}', [$cartController, 'deleteItem'], [$authMiddleware]);
$router->post('/api/cart/apply-discount', [$cartController, 'applyDiscount'], [$authMiddleware]);
$router->delete('/api/cart', [$cartController, 'clear'], [$authMiddleware]);

try {
    $result = $router->dispatch($request);
    Response::json($result['body'], $result['status']);
} catch (Throwable $exception) {
    $status = $exception->getCode();
    $status = is_int($status) && $status >= 400 && $status < 600 ? $status : 500;

    Response::json([
        'message' => $exception->getMessage() ?: 'Unexpected server error.',
    ], $status);
}
