<?php

declare(strict_types=1);

use App\Config\Config;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CategoryController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Controllers\ShippingController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ShippingRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\PayPalOrderService;

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
$emailService = new EmailService(
    'noreply@aubunworld.com',
    'AUBUN WORLD',
    $config['app']['base_url']
);
$userRepository = new UserRepository($pdo);
$productRepository = new ProductRepository($pdo);
$productImageDirectory = dirname(__DIR__) . '/store/products/image';
$cartRepository = new CartRepository($pdo, $productRepository);
$shippingRepository = new ShippingRepository($pdo);
$orderRepository = new OrderRepository($pdo, $shippingRepository);
$paypalService = new PayPalOrderService(
    $config['paypal']['client_id'],
    $config['paypal']['client_secret'],
    $config['paypal']['base_url'],
    $config['paypal']['currency']
);

$authController = new AuthController($userRepository, $authService, $emailService);
$productController = new ProductController($productRepository, $productImageDirectory);
$categoryController = new CategoryController($productRepository);
$cartController = new CartController($cartRepository);
$orderController = new OrderController($orderRepository, $cartRepository, $paypalService);
$shippingController = new ShippingController($shippingRepository);
$authMiddleware = new AuthMiddleware($authService, $userRepository);
$managerRoleMiddleware = new RoleMiddleware(['manager', 'admin']);

$router = new Router();

$router->post('/api/auth/register', [$authController, 'register']);
$router->post('/api/auth/login', [$authController, 'login']);
$router->get('/api/auth/me', [$authController, 'me'], [$authMiddleware]);
$router->get('/api/auth/verify-email', [$authController, 'verifyEmail']);
$router->post('/api/auth/resend-verification', [$authController, 'resendVerification'], [$authMiddleware]);
$router->post('/api/auth/newsletter/subscribe', [$authController, 'subscribeNewsletter']);
$router->patch('/api/auth/profile', [$authController, 'updateProfile'], [$authMiddleware]);
$router->post('/api/auth/change-password', [$authController, 'changePassword'], [$authMiddleware]);
$router->patch('/api/auth/shipping-address', [$authController, 'updateShippingAddress'], [$authMiddleware]);

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
$router->get('/api/orders/paypal-config', [$orderController, 'paypalConfig'], [$authMiddleware]);
$router->post('/api/orders', [$orderController, 'create'], [$authMiddleware]);
$router->post('/api/orders/{orderID}/capture', [$orderController, 'capture'], [$authMiddleware]);
$router->post('/api/orders/checkout', [$orderController, 'checkout'], [$authMiddleware]);
$router->get('/api/shipping-options', [$shippingController, 'options'], [$authMiddleware]);
$router->get('/api/shipping-settings', [$shippingController, 'index'], [$authMiddleware, $managerRoleMiddleware]);
$router->post('/api/shop-countries', [$shippingController, 'storeShopCountry'], [$authMiddleware, $managerRoleMiddleware]);
$router->delete('/api/shop-countries/{id}', [$shippingController, 'destroyShopCountry'], [$authMiddleware, $managerRoleMiddleware]);
$router->post('/api/shipping-settings/sync', [$shippingController, 'syncMappings'], [$authMiddleware, $managerRoleMiddleware]);

try {
    $result = $router->dispatch($request);
    $status = $result['status'] ?? 200;
    $status = is_int($status) ? $status : (is_numeric($status) ? (int) $status : 200);
    $status = $status >= 100 && $status < 600 ? $status : 200;
    Response::json($result['body'], $status);
} catch (Throwable $exception) {
    $status = $exception->getCode();
    $status = is_int($status) ? $status : (is_numeric($status) ? (int) $status : 500);
    $status = $status >= 400 && $status < 600 ? $status : 500;

    Response::json([
        'message' => $exception->getMessage() ?: 'Unexpected server error.',
    ], $status);
}
