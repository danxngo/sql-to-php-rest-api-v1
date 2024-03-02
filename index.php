<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Workstation\PhpApi\AuthManager;
use Workstation\PhpApi\ErrorHandler;
use Workstation\PhpApi\Router;
use Workstation\PhpApi\Database;
use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

require 'src/routes.php';

// Set custom error and exception handlers
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);

// Set response content type and allow all origins
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 

// Check if it's a preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Instantiate the database using environment variables
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database_name = $_ENV['DB_NAME'];
$database_host = $_ENV['DB_HOST'];
$dsn = "mysql:host=$database_host;dbname=$database_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_STRINGIFY_FETCHES => false
];
$database = new Database($dsn, $username, $password, $options);

// Instantiate the Auth Client using environment variables
$firebaseSecretKey = $_ENV['FIREBASE_SECRET_KEY'];
$authManager = new AuthManager($firebaseSecretKey, $database);

// Instantiate the router
$router = new Router();

$router->post('/login', function () use ($authManager) {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!empty($requestData['email']) && !empty($requestData['password'])) {
        $email = filter_var($requestData['email'], FILTER_SANITIZE_EMAIL);
        $password = $requestData['password'];

        $token = $authManager->login($email, $password);
        if ($token) {
            echo json_encode(['token' => $token]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
    }
});

$router->post('/signup', function () use ($authManager) {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!empty($requestData['email']) && !empty($requestData['password']) && !empty($requestData['name'])) {
        $email = filter_var($requestData['email'], FILTER_SANITIZE_EMAIL);
        $password = $requestData['password'];
        $name = $requestData['name'];

        // Perform additional validation checks
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters long']);
            return;
        }

        $token = $authManager->signup($email, $password, $name);
        if ($token) {
            echo json_encode(['token' => $token]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
    }
});

$router->post('/logout', function () use ($authManager) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header is missing']);
        exit();
    }
    $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    if ($authManager->logout($token)) {
        echo json_encode(['message' => 'Logged out successfully']);
    }
    else{
        http_response_code(401);
        echo json_encode(['error'=> 'Invalid token']);
    }
});

// Validate token
$router->get('/validateToken', function () use ($authManager) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header is missing']);
        exit();
    }
    $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    if ($authManager->verifyToken($token)) {
        echo json_encode(['message' => 'Token is valid']);
    }
    else{
        http_response_code(401);
        echo json_encode(['error'=> 'Invalid token']);
    }
});



defineRoutesForTables($router, $database, 'meta.sql');

// Middleware function for token verification
$verifyTokenMiddleware = function () use ($authManager) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header is missing']);
        exit();
    }

    $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    if (!$authManager->verifyToken($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit();
    }
};

// Apply middleware to routes that require authentication
$router->applyMiddleware('GET|POST|PUT|DELETE', '/user', $verifyTokenMiddleware);
//$router->applyMiddleware('GET|POST|PUT|DELETE', '/note', $verifyTokenMiddleware);

$router->run();
