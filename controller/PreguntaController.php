<?php

class PreguntaController
{
    private $renderer;
    private $preguntaModel;
    private $request;

    public function __construct($renderer, $preguntaModel, $request)
    {
        $this->renderer = $renderer;
        $this->preguntaModel = $preguntaModel;
        $this->request = $request;
    }

    public function crear()
    {
        $usuario = Auth::requerirLogin();

        $categorias = $this->preguntaModel->getCategorias();

        $error = $_SESSION['error_pregunta'] ?? null;
        unset($_SESSION['error_pregunta']);

        $data = [
            'usuario' => $usuario,
            'categorias' => $categorias,
            'error' => $error,
            'esCrearPregunta' => true,
        ];

        $this->renderer->render('crear_pregunta', $data);
    }

    public function guardar()
    {
        $usuario = Auth::requerirLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoriaId = $this->request->post('categoria_id');
            $pregunta = $this->request->post('pregunta');
            $opcionA = $this->request->post('opcion_a');
            $opcionB = $this->request->post('opcion_b');
            $opcionC = $this->request->post('opcion_c');
            $opcionD = $this->request->post('opcion_d');
            $respuestaCorrecta = $this->request->post('respuesta_correcta');

            if (!$categoriaId || !$pregunta || !$opcionA || !$opcionB || !$opcionC || !$opcionD || !$respuestaCorrecta) {
                $_SESSION['error_pregunta'] = 'Completá todos los campos.';
                Redirect::to('/pregunta/crear');
            }

            $rol = $usuario['rol'] ?? 'usuario';
            $this->preguntaModel->crear($categoriaId, $pregunta, $opcionA, $opcionB, $opcionC, $opcionD, $respuestaCorrecta, $usuario['id'], $rol);

            if ($rol === 'editor' || $rol === 'admin') {
                $_SESSION['exito_pregunta'] = 'Pregunta creada correctamente. Ya está disponible en el juego.';
            } else {
                $_SESSION['exito_pregunta'] = 'Pregunta enviada. Un editor la revisará para aprobarla.';
            }
            Redirect::toIndex();
        }
    }
}
