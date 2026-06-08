<?php

class HomeController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function ver()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $data = ["esHome" => true];
        if ($usuario) {
            $data["usuario"] = $usuario;
        }
        $this->renderer->render("landing", $data);
    }
}
