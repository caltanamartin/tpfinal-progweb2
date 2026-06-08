<?php

class PerfilController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function ver()
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            Redirect::to('/login');
        }

        $this->renderer->render("perfil", [
            "usuario" => $usuario,
            "esPerfil" => true,
        ]);
    }
}
