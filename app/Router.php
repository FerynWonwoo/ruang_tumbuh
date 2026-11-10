
<?php
class Router
{
    private $routes = [];

    public function get($url, $controller)
    {
        $this->routes['GET'][$url] = $controller;
    }

    public function post($url, $controller)
    {
        $this->routes['POST'][$url] = $controller;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($this->routes[$method][$url])) {
            $controller = $this->routes[$method][$url];

            if (is_callable($controller)) {
                call_user_func($controller);
            } else {
                echo "Controller tidak ditemukan.";
            }
        } else {
            http_response_code(404);
            echo "Halaman tidak ditemukan.";
        }
    }
}
