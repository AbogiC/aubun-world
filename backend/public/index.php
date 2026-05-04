<?php

declare(strict_types=1);

use App\Config\Config;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CategoryController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Customer-Country');
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
$productImageDirectory = dirname(__DIR__) . '/store/products/image';
$cartRepository = new CartRepository($pdo, $productRepository);
$orderRepository = new OrderRepository($pdo);

$authController = new AuthController($userRepository, $authService);
$productController = new ProductController($productRepository, $productImageDirectory);
$categoryController = new CategoryController($productRepository);
$cartController = new CartController($cartRepository);
$orderController = new OrderController($orderRepository, $cartRepository);
$authMiddleware = new AuthMiddleware($authService, $userRepository);
$managerRoleMiddleware = new RoleMiddleware(['manager', 'admin']);

$router = new Router();

$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login', [$authController, 'login']);
$router->get('/api/auth/me', [$authController, 'me'], [$authMiddleware]);

$router->get('/api/products', [$productController, 'index']);
$router->get('/api/products/{id}', [$productController, 'show']);
$router->get('/api/product-images/{filename}', [$productController, 'image']);
$router->post('/api/products/upload-image', [$productController, 'uploadImage'], [$authMiddleware, $managerRoleMiddleware]);
$router->post('/api/products', [$productController, 'store'], [$authMiddleware, $managerRoleMiddleware]);
$router->patch('/api/products/{id}', [$productController, 'update'], [$authMiddleware, $managerRoleMiddleware]);
$router->delete('/api/products/{id}', [$productController, 'destroy'], [$authMiddleware, $managerRoleMiddleware]);
$router->get('/api/categories', [$categoryController, 'index']);

$router->get('/api/cart', [$cartController, 'show'], [$authMiddleware]);
$router->post('/api/cart/items', [$cartController, 'storeItem'], [$authMiddleware]);
$router->patch('/api/cart/items/{id}', [$cartController, 'updateItem'], [$authMiddleware]);
$router->delete('/api/cart/items/{id}', [$cartController, 'deleteItem'], [$authMiddleware]);
$router->post('/api/cart/apply-discount', [$cartController, 'applyDiscount'], [$authMiddleware]);
$router->delete('/api/cart', [$cartController, 'clear'], [$authMiddleware]);
$router->get('/api/orders', [$orderController, 'index'], [$authMiddleware]);
$router->post('/api/orders/checkout', [$orderController, 'checkout'], [$authMiddleware]);

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
