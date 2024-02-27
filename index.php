<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Workstation\PhpApi\AuthManager;
use Workstation\PhpApi\ErrorHandler;
use Workstation\PhpApi\Router;
use Workstation\PhpApi\Database;

require 'src/routes.php';

// Set custom error and exception handlers
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);

// Set response content type and allow all origins
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Instantiate the database
$username = 'root';
$password = 'password';
$database_name = 'apidb';
$database_host = 'localhost';
$dsn = "mysql:host=$database_host;dbname=$database_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_STRINGIFY_FETCHES => false
];
$database = new Database($dsn, $username, $password, $options);


// Instantiate the Auth Client
$firebaseSecretKey = 'your_firebase_secret_key_here';
$authManager = new AuthManager($firebaseSecretKey, $database);

// Instantiate the router
$router = new Router();

// Define auth routes
$router->get('/login', function () use ($authManager) {
    $requestData = json_decode(file_get_contents('php://input'), true);
    if (!empty($requestData)) {
        $token = $authManager->login($requestData['email'], $requestData['password']);
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
    if (!empty($requestData)) {
        $token = $authManager->signup($requestData['email'], $requestData['password'], $requestData['name']);
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

$router->run();
