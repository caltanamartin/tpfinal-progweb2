<?php

class Router
{
    private $config;
    private $defaultController;
    private $defaultMethod;

    public function __construct($config, $defaultController, $defaultMethod)
    {
        $this->config            = $config;
        $this->defaultController = $defaultController;
        $this->defaultMethod     = $defaultMethod;
    }

    public function dispatch($url)
    {
        try {
            $path = trim(parse_url($url, PHP_URL_PATH), '/');
            $segments = $path ? explode('/', $path) : [];

            $controllerName = $segments[0] ?? $this->defaultController;
            $methodName     = $segments[1] ?? null;
            $params         = $methodName ? array_slice($segments, 2) : [];

            $controller = $this->config->getOrDefault($controllerName, $this->defaultController);

            if ($methodName && method_exists($controller, $methodName)) {
                $controller->{$methodName}(...$params);
            } elseif ($methodName) {
                $method = $this->resolveDefaultMethod($controller);
                $controller->{$method}(...array_slice($segments, 1));
            } else {
                $method = $this->resolveDefaultMethod($controller);
                $controller->{$method}();
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo "Error interno del servidor.";
        }
    }

    private function resolveDefaultMethod($controller)
    {
        foreach ([$this->defaultMethod, 'index', 'ver'] as $method) {
            if (method_exists($controller, $method)) {
                return $method;
            }
        }
        $methods = get_class_methods($controller);
        foreach ($methods as $m) {
            if (!str_starts_with($m, '__')) {
                return $m;
            }
        }
        return $this->defaultMethod;
    }
}
