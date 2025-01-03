<?php

//Rounting system

class Router {
    protected $routes = []; // stores routes

    public function addRoute(string $method, string $url, closure $target) {
        $this->routes[$method][$url] = $target;
    }

    public function matchRoute() {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $_SERVER['REQUEST_URI'];
        $parts = explode('?', $url);
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routeUrl => $target) {
                // Simple string comparison to see if the route URL matches the requested URL
                if ($routeUrl === $parts[0]) {
                    call_user_func($target);
                }
            }
        }
        header('Location: /404');
    }
}