<?php

class Router
{
    private $config;
    private $defaultController;

    public function __construct($config, $defaultController)
    {
        $this->config = $config;
        $this->defaultController = $defaultController;
    }

    public function dispatch($url)
    {
        try {
            $path = trim(parse_url($url, PHP_URL_PATH), '/');
            $segments = $path ? explode('/', $path) : [];

            $controllerName = $segments[0] ?? $this->defaultController;
            $methodName = $segments[1] ?? null;

            $controller = $this->config->getOrDefault($controllerName, $this->defaultController);

            if ($methodName && method_exists($controller, $methodName)) {
                $controller->{$methodName}();
            } else {
                $controller->index();
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo "Error interno del servidor.";
        }
    }
}
