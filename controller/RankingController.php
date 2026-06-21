<?php

class RankingController
{
    private $renderer;
    private $usuarioModel;

    public function __construct($renderer, $usuarioModel)
    {
        $this->renderer = $renderer;
        $this->usuarioModel = $usuarioModel;
    }

    public function ver()
    {
        $usuarios = $this->usuarioModel->getRanking(50);

        $posicion = 1;
        foreach ($usuarios as &$u) {
            $u['posicion'] = $posicion++;
        }

        $data = [
            'usuarios' => $usuarios,
            'usuario' => $_SESSION['usuario'] ?? null,
        ];

        $this->renderer->render("ranking", $data);
    }
}
