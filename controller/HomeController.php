<?php

class HomeController
{
    private $renderer;
    private $partidaModel;

    public function __construct($renderer, $partidaModel = null)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
    }

    public function ver()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $data = ["esHome" => true];
        if ($usuario && $this->partidaModel) {
            $data["usuario"] = $usuario;
            $data["puntajeTotal"] = $this->partidaModel->puntajeTotal($usuario['id']);
            $partidas = $this->partidaModel->historial($usuario['id'], 5);
            $data["tienePartidas"] = !empty($partidas);
            foreach ($partidas as &$p) {
                $p["esGanada"] = $p["puntaje"] > 0;
            }
            $data["partidas"] = $partidas;
        } elseif ($usuario) {
            $data["usuario"] = $usuario;
        }
        $this->renderer->render("landing", $data);
    }
}
