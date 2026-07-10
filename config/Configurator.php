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
        return new HomeController($this->getRenderer(), $this->getPartidaModel(), $this->getPreguntaModel(), $this->getTrampitaModel(), $this->getUsuarioModel());
    }

    public function getRankingController() {
        return new RankingController($this->getRenderer(), $this->getUsuarioModel());
    }

    public function getAuthController() {
        return new AuthController($this->getRenderer(), $this->getUsuarioModel(), new Request());
    }

    public function getPerfilController() {
        return new PerfilController($this->getRenderer(), $this->getUsuarioModel(), new Request());
    }

    public function getPreguntaController() {
        return new PreguntaController($this->getRenderer(), $this->getPreguntaModel(), new Request());
    }

    public function getAdminController() {
        return new AdminController(
            $this->getRenderer(),
            $this->getUsuarioModel(),
            $this->getPartidaModel(),
            $this->getPreguntaModel(),
            new Request(),
            $this->getTrampitasController()
        );
    }

    public function getVerificarController() {
        return new VerificarController($this->getUsuarioModel(), $this->getRenderer());
    }

    public function getTrampitasController() {
        return new TrampitasController(
            $this->getRenderer(),
            $this->getTrampitaModel(),
            $this->getPartidaModel(),
            $this->getPartidaPreguntaModel(),
            $this->getPreguntaModel(),
            new Request()
        );
    }

    public function getEditorController() {
        return new EditorController(
            $this->getRenderer(),
            $this->getPreguntaModel(),
            $this->getUsuarioModel(),
            new Request()
        );
    }

    public function getJuegoController() {
        return new JuegoController(
            $this->getRenderer(),
            $this->getPartidaModel(),
            $this->getPreguntaModel(),
            $this->getPartidaPreguntaModel(),
            new Request(),
            $this->getTrampitaModel(),
            $this->getUsuarioModel()
        );
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

    private function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    private function getPartidaModel()
    {
        return new PartidaModel($this->getDatabase());
    }

    private function getTrampitaModel()
    {
        return new TrampitaModel($this->getDatabase());
    }

    private function getPreguntaModel()
    {
        return new PreguntaModel($this->getDatabase());
    }

    private function getPartidaPreguntaModel()
    {
        return new PartidaPreguntaModel($this->getDatabase());
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
