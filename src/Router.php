<?php
namespace Workstation\PhpApi;

class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function __construct()
    {
    }

    public function get(string $route, callable $handler): void
    {
        $this->routes['GET'][$route] = $handler;
    }

    public function post(string $route, callable $handler): void
    {
        $this->routes['POST'][$route] = $handler;
    }

    public function put(string $route, callable $handler): void
    {
        $this->routes['PUT'][$route] = $handler;
    }

    public function delete(string $route, callable $handler): void
    {
        $this->routes['DELETE'][$route] = $handler;
    }

    // Method to add middleware to the router
    public function applyMiddleware(string $methods, string $route, callable $middleware): void
    {
        foreach (explode('|', $methods) as $method) {
            $this->middleware[$method][$route][] = $middleware;
        }
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $path);
    
        $routePath = $path;
        $params = [];
    
        // Check if URL structure matches '/{resource}/{id}' pattern
        if(count($parts) > 2) {
            $routePath = '/' . $parts[1] . '/{id}';
            $params['id'] = $parts[2];
        }
    
        // Execute middleware before executing the main handler
        if (!empty($this->middleware[$method][$routePath])) {
            foreach ($this->middleware[$method][$routePath] as $middleware) {
                call_user_func_array($middleware, []);
            }
        }

        $handler = $this->routes[$method][$routePath] ?? false;
        if ($handler && is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            http_response_code(404);
            throw new \Exception('Route not found');
        }
    }
}


/*

// Usage example:

$router = new Router();

$router->get('/users', function () {
    echo 'Get all users';
});

$router->get('/users/{id}', function ($id) {
    echo 'Get user with ID ' . $id;
});

$router->post('/users', function () {
    echo 'Create a new user';
});

$router->put('/users/{id}', function ($id) {
    echo 'Update user with ID ' . $id;
});

$router->delete('/users/{id}', function ($id) {
    echo 'Delete user with ID ' . $id;
});

$router->run();
*/