<?php

class HomeController
{
    private $renderer;
    private $partidaModel;
    private $preguntaModel;

    public function __construct($renderer, $partidaModel = null, $preguntaModel = null)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
        $this->preguntaModel = $preguntaModel;
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

            if ($this->preguntaModel) {
                $nivel = $this->preguntaModel->calcularNivelUsuario($usuario['id']);
                if ($nivel === 0.5) {
                    $data['nivelLabel'] = 'En evaluación';
                    $data['nivelClass'] = 'bg-gray-200 text-gray-600';
                } elseif ($nivel > 0.7) {
                    $data['nivelLabel'] = 'Avanzado';
                    $data['nivelClass'] = 'bg-green-100 text-green-700';
                } elseif ($nivel < 0.3) {
                    $data['nivelLabel'] = 'Principiante';
                    $data['nivelClass'] = 'bg-red-100 text-red-700';
                } else {
                    $data['nivelLabel'] = 'Intermedio';
                    $data['nivelClass'] = 'bg-yellow-100 text-yellow-700';
                }
            }
        } elseif ($usuario) {
            $data["usuario"] = $usuario;
        }

        $exitoPregunta = $_SESSION['exito_pregunta'] ?? null;
        unset($_SESSION['exito_pregunta']);
        if ($exitoPregunta) {
            $data['exito_pregunta'] = $exitoPregunta;
        }

        $this->renderer->render("landing", $data);
    }
}
