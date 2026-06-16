<?php

class JuegoController
{
    private $renderer;
    private $partidaModel;
    private $preguntaModel;
    private $partidaPreguntaModel;
    private $request;

    public function __construct($renderer, $partidaModel, $preguntaModel, $partidaPreguntaModel, $request)
    {
        $this->renderer = $renderer;
        $this->partidaModel = $partidaModel;
        $this->preguntaModel = $preguntaModel;
        $this->partidaPreguntaModel = $partidaPreguntaModel;
        $this->request = $request;
    }

    public function nuevaPartida()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaEnCurso = $this->partidaModel->tienePartidaEnCurso($usuario['id']);
        if ($partidaEnCurso) {
            $this->partidaModel->terminar($partidaEnCurso);
        }

        $partidaId = $this->partidaModel->crear($usuario['id']);
        Redirect::to('/juego?id=' . $partidaId);
    }

    public function jugar()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaId = $this->request->get('id');
        $partida = $this->partidaModel->obtener($partidaId);

        if (!$partida || $partida['usuario_id'] != $usuario['id']) { 
            Redirect::to('/');
        }

        if ($partida['estado'] === 'terminada') { 
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $sessionPartida = $_SESSION['partida_actual'] ?? null;
        if ($sessionPartida && (int)$sessionPartida['partida_id'] === (int)$partidaId) {
            $pregunta = $this->preguntaModel->getPreguntaConCategoria($sessionPartida['pregunta_id']);
            $ordenSiguiente = $sessionPartida['orden'];
        } else {
            $pregunta = $this->preguntaModel->getPreguntaAleatoria($partidaId, $usuario['id']);

            if (!$pregunta) {
                $this->partidaModel->terminar($partidaId);
                Redirect::to('/juego/resultado?id=' . $partidaId);
            }

            $ordenSiguiente = $this->partidaPreguntaModel->siguienteOrden($partidaId);

            $_SESSION['partida_actual'] = [
                'partida_id' => $partidaId,
                'pregunta_id' => $pregunta['id'],
                'orden' => $ordenSiguiente,
            ];
        }

        $pregunta['opciones'] = [
            ['letra' => 'A', 'texto' => $pregunta['opcion_a']],
            ['letra' => 'B', 'texto' => $pregunta['opcion_b']],
            ['letra' => 'C', 'texto' => $pregunta['opcion_c']],
            ['letra' => 'D', 'texto' => $pregunta['opcion_d']],
        ];

        $totalResp = (int)$pregunta['total_respuestas_global'];
        $totalCorr = (int)$pregunta['total_correctas_global'];
        $pregunta['esNeutra'] = ($totalResp < 10);
        $pregunta['respuestas_global'] = $totalResp;

        if ($totalResp >= 10) {
            $ratio = $totalCorr / $totalResp;
            if ($ratio > 0.7) {
                $pregunta['dificultad_label'] = 'Fácil';
            } elseif ($ratio < 0.3) {
                $pregunta['dificultad_label'] = 'Difícil';
            } else {
                $pregunta['dificultad_label'] = 'Media';
            }
        } else {
            $pregunta['dificultad_label'] = 'Periodo de prueba';
        }

        $data = [
            'usuario' => $usuario,
            'partida' => $partida,
            'pregunta' => $pregunta,
            'esJuego' => true,
        ];

        $this->renderer->render('juego', $data);
    }

    public function responder()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $respuesta = strtoupper($this->request->post('respuesta'));
        $sessionPartida = $_SESSION['partida_actual'] ?? null;

        if (!$sessionPartida) {
            Redirect::to('/');
        }

        $partidaId = $sessionPartida['partida_id'];
        $preguntaId = $sessionPartida['pregunta_id'];
        $orden = $sessionPartida['orden'];

        $partida = $this->partidaModel->obtener($partidaId);
        if (!$partida || $partida['usuario_id'] != $usuario['id'] || $partida['estado'] === 'terminada') {
            unset($_SESSION['partida_actual']);
            Redirect::to('/');
        }

        $pregunta = $this->preguntaModel->getPreguntaConCategoria($preguntaId);
        $esCorrecta = ($respuesta === $pregunta['respuesta_correcta']) ? 1 : 0;

        $this->partidaPreguntaModel->registrar($partidaId, $preguntaId, $respuesta, $esCorrecta, $orden);
        unset($_SESSION['partida_actual']);

        if ($esCorrecta) {
            $this->partidaModel->sumarPunto($partidaId);
            Redirect::to('/juego?id=' . $partidaId);
        } else {
            $this->partidaModel->terminar($partidaId);
            $_SESSION['ultima_respuesta'] = [
                'pregunta' => $pregunta['pregunta'],
                'respuesta_correcta' => $pregunta['respuesta_correcta'],
                'opciones' => [
                    'A' => $pregunta['opcion_a'],
                    'B' => $pregunta['opcion_b'],
                    'C' => $pregunta['opcion_c'],
                    'D' => $pregunta['opcion_d'],
                ],
            ];
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }
    }

    public function resultado()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaId = $this->request->get('id');
        $partida = $this->partidaModel->obtener($partidaId);

        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            Redirect::to('/');
        }

        $ultimaRespuesta = $this->partidaPreguntaModel->obtenerUltimaRespuesta($partidaId);
        $ultimaData = $_SESSION['ultima_respuesta'] ?? null;
        unset($_SESSION['ultima_respuesta']);

        $data = [
            'usuario' => $usuario,
            'partida' => $partida,
            'ultimaPregunta' => $ultimaData,
            'huboError' => !$ultimaRespuesta || !$ultimaRespuesta['es_correcta'],
            'esResultado' => true,
        ];

        $this->renderer->render('resultado', $data);
    }
}
