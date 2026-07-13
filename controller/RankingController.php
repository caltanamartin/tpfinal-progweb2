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

    public function index()
    {
        $usuario = Auth::usuario();
        $rolFiltro = ($usuario && $usuario['rol'] === 'usuario') ? 'usuario' : null;
        $usuarios = $this->usuarioModel->getRanking(50, $rolFiltro);

        $posicion = 1;
        foreach ($usuarios as &$u) {
            $u['posicion'] = $posicion;
            $u['esPrimero'] = $posicion === 1;
            $posicion++;
        }

        $data = [
            'usuarios' => $usuarios,
            'usuario' => $usuario,
        ];

        $this->renderer->render("ranking", $data);
    }
}
