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
        Redirect::to('/juego/categoria?id=' . $partidaId);
    }

    public function categoria()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaId = $_REQUEST['id'] ?? null;
        if (!Validator::positiveInt($partidaId)) {
            Redirect::to('/');
        }
        $partida = $this->partidaModel->obtener($partidaId);

        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            Redirect::to('/');
        }

        if ($partida['estado'] === 'terminada') {
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $respuestaCorrecta = $_SESSION['respuesta_correcta'] ?? false;
        unset($_SESSION['respuesta_correcta']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoriaId = $this->request->post('categoria_id');
            $_SESSION['categoria_elegida'] = $categoriaId;
            Redirect::to('/juego?id=' . $partidaId);
        }

        $categorias = $this->preguntaModel->getCategorias();

        $data = [
            'usuario' => $usuario,
            'partida' => $partida,
            'categorias' => $categorias,
            'respuestaCorrecta' => $respuestaCorrecta,
        ];

        if (isset($_SESSION['error_categoria'])) {
            $data['error'] = $_SESSION['error_categoria'];
            unset($_SESSION['error_categoria']);
        }

        $this->renderer->render('categoria', $data);
    }

    public function jugar()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaId = $this->request->get('id');
        if (!Validator::positiveInt($partidaId)) {
            Redirect::to('/');
        }
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
            if (!isset($sessionPartida['mostrada_en'])) {
                $_SESSION['partida_actual']['mostrada_en'] = time();
            }
        } else {
            $categoriaId = $_SESSION['categoria_elegida'] ?? null;
            unset($_SESSION['categoria_elegida']);

            if (!$categoriaId) {
                Redirect::to('/juego/categoria?id=' . $partidaId);
            }

            $pregunta = $this->preguntaModel->getPreguntaAleatoria($partidaId, $usuario['id'], $categoriaId);

            if (!$pregunta) {
                $_SESSION['error_categoria'] = 'No hay más preguntas de esta categoría. Elegí otra.';
                Redirect::to('/juego/categoria?id=' . $partidaId);
            }

            $ordenSiguiente = $this->partidaPreguntaModel->siguienteOrden($partidaId);

            $_SESSION['partida_actual'] = [
                'partida_id' => $partidaId,
                'pregunta_id' => $pregunta['id'],
                'orden' => $ordenSiguiente,
                'mostrada_en' => time(),
            ];
        }

        $mostradaEn = $_SESSION['partida_actual']['mostrada_en'];
        $tiempoRestante = max(0, 30 - (time() - $mostradaEn));

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

        $exito = $_SESSION['reportado_exito'] ?? null;
        unset($_SESSION['reportado_exito']);

        $data = [
            'usuario' => $usuario,
            'partida' => $partida,
            'pregunta' => $pregunta,
            'tiempo_restante' => $tiempoRestante,
            'esJuego' => true,
            'exito' => $exito,
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
        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            unset($_SESSION['partida_actual']);
            Redirect::to('/');
        }

        if ($partida['estado'] === 'terminada') {
            unset($_SESSION['partida_actual']);
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $mostradaEn = $sessionPartida['mostrada_en'] ?? 0;
        if (time() - $mostradaEn > 30) {
            $pregunta = $this->preguntaModel->getPreguntaConCategoria($preguntaId);
            $this->partidaPreguntaModel->registrar($partidaId, $preguntaId, null, 0, $orden);
            $this->partidaModel->terminar($partidaId);
            unset($_SESSION['partida_actual']);
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
            $_SESSION['hubo_timeout'] = true;
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $pregunta = $this->preguntaModel->getPreguntaConCategoria($preguntaId);
        $esCorrecta = ($respuesta === $pregunta['respuesta_correcta']) ? 1 : 0;

        $this->partidaPreguntaModel->registrar($partidaId, $preguntaId, $respuesta, $esCorrecta, $orden);
        unset($_SESSION['partida_actual']);

        if ($esCorrecta) {
            $this->partidaModel->sumarPunto($partidaId);
            $_SESSION['respuesta_correcta'] = true;
            Redirect::to('/juego/categoria?id=' . $partidaId);
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

    public function tiempoAgotado()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $sessionPartida = $_SESSION['partida_actual'] ?? null;
        if (!$sessionPartida) {
            Redirect::to('/');
        }

        $partidaId = $sessionPartida['partida_id'];
        $preguntaId = $sessionPartida['pregunta_id'];
        $orden = $sessionPartida['orden'];

        $partida = $this->partidaModel->obtener($partidaId);
        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            unset($_SESSION['partida_actual']);
            Redirect::to('/');
        }

        if ($partida['estado'] === 'terminada') {
            unset($_SESSION['partida_actual']);
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $mostradaEn = $sessionPartida['mostrada_en'] ?? 0;
        if (time() - $mostradaEn < 30) {
            Redirect::to('/juego?id=' . $partidaId);
        }

        $pregunta = $this->preguntaModel->getPreguntaConCategoria($preguntaId);
        $this->partidaPreguntaModel->registrar($partidaId, $preguntaId, null, 0, $orden);
        $this->partidaModel->terminar($partidaId);
        unset($_SESSION['partida_actual']);

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
        $_SESSION['hubo_timeout'] = true;
        Redirect::to('/juego/resultado?id=' . $partidaId);
    }

    public function reportar()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/');
        }

        $preguntaId = $this->request->post('pregunta_id');

        if (!Validator::positiveInt($preguntaId)) {
            Redirect::to('/');
        }

        $motivo = $this->request->post('motivo');

        if (!$preguntaId) {
            Redirect::to('/');
        }

        $this->preguntaModel->reportar($preguntaId, $usuario['id'], $motivo);
        $_SESSION['reportado_exito'] = 'Pregunta reportada. Gracias por tu ayuda.';

        $sessionPartida = $_SESSION['partida_actual'] ?? null;
        $partidaId = $sessionPartida ? $sessionPartida['partida_id'] : null;
        if ($partidaId) {
            Redirect::to('/juego?id=' . $partidaId);
        }
        Redirect::to('/');
    }

    public function resultado()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
        }

        $partidaId = $this->request->get('id');
        if (!Validator::positiveInt($partidaId)) {
            Redirect::to('/');
        }
        $partida = $this->partidaModel->obtener($partidaId);

        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            Redirect::to('/');
        }

        $huboTimeout = $_SESSION['hubo_timeout'] ?? false;
        unset($_SESSION['hubo_timeout']);

        $ultimaRespuesta = $this->partidaPreguntaModel->obtenerUltimaRespuesta($partidaId);

        $ultimaData = $_SESSION['ultima_respuesta'] ?? null;
        unset($_SESSION['ultima_respuesta']);

        $data = [
            'usuario' => $usuario,
            'partida' => $partida,
            'ultimaPregunta' => $ultimaData,
            'huboError' => !$ultimaRespuesta || !$ultimaRespuesta['es_correcta'],
            'huboTimeout' => $huboTimeout,
            'esResultado' => true,
        ];

        $this->renderer->render('resultado', $data);
    }
}
