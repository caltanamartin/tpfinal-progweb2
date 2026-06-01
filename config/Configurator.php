<?php
class Configurator {

    private $config;

    public function __construct()
    {
        $this->config = parse_ini_file("config/config.ini");
    }

    public function getVikingoController()
    {
        return new VikingoController($this->getVikingoModel(), $this->getRenderer(), new Request());
    }

    public function getHomeController() {
        return new HomeController($this->getRenderer());
    }

    public function getAuthController() {
        return new AuthController($this->getRenderer());
    }

    private function getDatabase()
    {
        return new MyDatabase(
            $this->config['hostname'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );
    }

    private function getRenderer()
    {
        return new MustacheRenderer(__DIR__ . '/../view');
    }

    private function getVikingoModel()
    {
        return new VikingoModel($this->getDatabase());
    }

    public function getRouter()
    {
        return new Router($this, 'home', 'ver');
    }

    public function getOrDefault($controllerName, $defaultControllerName)
    {
        $getter = 'get' . ucfirst($controllerName) . 'Controller';
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        $defaultGetter = 'get' . ucfirst($defaultControllerName) . 'Controller';
        return $this->{$defaultGetter}();
    }
}
