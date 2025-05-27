<?php

namespace App\Core;

class Router {
    private array $routes = [];
    private array $params = [];

    public function add(string $route, string $controller, string $action, string $method = 'GET'): void {
        $route = trim($route, '/');
        $this->routes[$method][$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function match(string $url, string $method = 'GET'): bool {
        $url = trim($url, '/');
        
        // Check if exact route exists
        if (isset($this->routes[$method][$url])) {
            $this->params = $this->routes[$method][$url];
            return true;
        }

        // Check for dynamic routes
        foreach ($this->routes[$method] as $route => $params) {
            $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<\1>[^/]+)', $route);
            $pattern = "#^{$pattern}$#";
            
            if (preg_match($pattern, $url, $matches)) {
                // Remove numeric keys
                foreach ($matches as $key => $match) {
                    if (is_int($key)) {
                        unset($matches[$key]);
                    }
                }

                $this->params = array_merge($params, $matches);
                return true;
            }
        }

        return false;
    }

    public function dispatch(): void {
        $url = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        // Remove query string
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        if ($this->match($url, $method)) {
            $controller = "App\\Controllers\\" . $this->params['controller'];
            $action = $this->params['action'];

            if (class_exists($controller)) {
                $controllerInstance = new $controller();

                if (method_exists($controllerInstance, $action)) {
                    // Remove controller and action from params
                    unset($this->params['controller'], $this->params['action']);

                    // Call the action method with parameters
                    call_user_func_array([$controllerInstance, $action], $this->params);
                    return;
                }
            }
        }

        // No route matched, show 404 page
        header("HTTP/1.0 404 Not Found");
        $controller = new \App\Controllers\HomeController();
        $controller->error404();
    }
} 