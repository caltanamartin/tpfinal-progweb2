<?php

class HomeController
{
    private $renderer;
    private $partidaModel;
    private $trampitaModel;
    private $usuarioModel;

    public function __construct($renderer, $partidaModel = null, $trampitaModel = null, $usuarioModel = null)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
        $this->trampitaModel = $trampitaModel;
        $this->usuarioModel = $usuarioModel;
    }

    public function index()
    {
        $usuario = Auth::usuario();
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

            if ($this->usuarioModel) {
                $nivel = $this->usuarioModel->calcularNivelUsuario($usuario['id']);
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

            if ($this->trampitaModel) {
                $stock = $this->trampitaModel->contarDisponibles($usuario['id']);
                $data['stock'] = [
                    'c_5050' => $stock['50/50'],
                    'c_skip' => $stock['skip'],
                    'c_freeze' => $stock['congelar_tiempo'],
                ];
                $data['tieneStock'] = ($stock['50/50'] + $stock['skip'] + $stock['congelar_tiempo']) > 0;
            }
        } elseif ($usuario) {
            $data["usuario"] = $usuario;
        }
        
        // flash message de sugerencia/creación de pregunta
        $exitoPregunta = $_SESSION['exito_pregunta'] ?? null;
        unset($_SESSION['exito_pregunta']);
        if ($exitoPregunta) {
            $data['exito_pregunta'] = $exitoPregunta;
        }

        $this->renderer->render("landing", $data);
    }
}
