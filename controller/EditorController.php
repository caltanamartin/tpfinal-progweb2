<?php

class EditorController
{
    private $renderer;
    private $preguntaModel;
    private $usuarioModel;
    private $request;

    public function __construct($renderer, $preguntaModel, $usuarioModel, $request)
    {
        $this->renderer = $renderer;
        $this->preguntaModel = $preguntaModel;
        $this->usuarioModel = $usuarioModel;
        $this->request = $request;
    }

    private function buildPaginacion($actual, $totalPaginas, $param, $hash)
    {
        $numeros = [];
        $inicio = max(1, $actual - 2);
        $fin = min($totalPaginas, $actual + 2);
        for ($i = $inicio; $i <= $fin; $i++) {
            $numeros[] = ['num' => $i, 'activa' => $i === $actual];
        }
        return [
            'mostrar' => $totalPaginas > 1,
            'actual' => $actual,
            'anterior' => $actual > 1,
            'siguiente' => $actual < $totalPaginas,
            'prevPage' => $actual - 1,
            'nextPage' => $actual + 1,
            'numeros' => $numeros,
            'param' => $param,
            'hash' => $hash,
        ];
    }

    private function backUrl($usuario)
    {
        return ($usuario['rol'] === 'admin') ? '/admin#preguntas' : '/editor/preguntas';
    }

    public function index()
    {
        $usuario = Auth::requerirEditorOAdmin();

        $data = [
            'usuario' => $usuario,
            'esEditor' => true,
        ];

        $this->renderer->render('editor_index', $data);
    }

    public function preguntas()
    {
        $usuario = Auth::requerirEditorOAdmin();

        $porPagina = 15;
        $page = max(1, (int)$this->request->get('page', 1));
        $res = $this->preguntaModel->listarTodas($page, $porPagina);
        $preguntas = $res['filas'];
        foreach ($preguntas as &$p) {
            $p['esCorrectaA'] = ($p['respuesta_correcta'] === 'A');
            $p['esCorrectaB'] = ($p['respuesta_correcta'] === 'B');
            $p['esCorrectaC'] = ($p['respuesta_correcta'] === 'C');
            $p['esCorrectaD'] = ($p['respuesta_correcta'] === 'D');
        }

        $data = [
            'usuario' => $usuario,
            'esEditor' => ($usuario['rol'] === 'editor'),
            'esAdmin' => ($usuario['rol'] === 'admin'),
            'preguntas' => $preguntas,
            'backUrl' => $this->backUrl($usuario),
            'pag' => $this->buildPaginacion($page, $res['paginas'], 'page', ''),
        ];

        $this->renderer->render('editor_preguntas', $data);
    }

    public function editarPregunta($id = null)
    {
        $usuario = Auth::requerirEditorOAdmin();

        if (!Validator::positiveInt($id)) {
            Redirect::to($this->backUrl($usuario));
        }

        $pregunta = $this->preguntaModel->obtener($id);
        if (!$pregunta) {
            Redirect::to($this->backUrl($usuario));
        }

        $categorias = $this->preguntaModel->getCategorias();
        foreach ($categorias as &$cat) {
            $cat['selected'] = ((int)$cat['id'] === (int)$pregunta['categoria_id']);
        }
        unset($cat);

        $pregunta['esA'] = ($pregunta['respuesta_correcta'] === 'A');
        $pregunta['esB'] = ($pregunta['respuesta_correcta'] === 'B');
        $pregunta['esC'] = ($pregunta['respuesta_correcta'] === 'C');
        $pregunta['esD'] = ($pregunta['respuesta_correcta'] === 'D');

        $error = $_SESSION['error_editor'] ?? null;
        unset($_SESSION['error_editor']);

        $data = [
            'usuario' => $usuario,
            'esEditor' => ($usuario['rol'] === 'editor'),
            'esAdmin' => ($usuario['rol'] === 'admin'),
            'pregunta' => $pregunta,
            'categorias' => $categorias,
            'error' => $error,
            'backUrl' => $this->backUrl($usuario),
        ];

        $this->renderer->render('editor_editar_pregunta', $data);
    }

    public function guardarPregunta()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to($this->backUrl($usuario));
        }

        $id = $this->request->post('id');
        $categoriaId = $this->request->post('categoria_id');
        $pregunta = $this->request->post('pregunta');
        $opcionA = $this->request->post('opcion_a');
        $opcionB = $this->request->post('opcion_b');
        $opcionC = $this->request->post('opcion_c');
        $opcionD = $this->request->post('opcion_d');
        $respuestaCorrecta = $this->request->post('respuesta_correcta');

        $backUrl = $this->backUrl($usuario);

        if (!Validator::positiveInt($id) || !$categoriaId || !$pregunta || !$opcionA || !$opcionB || !$opcionC || !$opcionD || !$respuestaCorrecta) {
            $_SESSION['error_editor'] = 'Completá todos los campos.';
            Redirect::to('/editor/editarPregunta/' . $id);
        }

        $this->preguntaModel->actualizar($id, [
            'categoria_id' => $categoriaId,
            'pregunta' => $pregunta,
            'opcion_a' => $opcionA,
            'opcion_b' => $opcionB,
            'opcion_c' => $opcionC,
            'opcion_d' => $opcionD,
            'respuesta_correcta' => $respuestaCorrecta,
        ]);

        $_SESSION['exito_editor'] = 'Pregunta actualizada correctamente.';
        Redirect::to($backUrl);
    }

    public function eliminarPregunta()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to($this->backUrl($usuario));
        }

        $id = $this->request->post('id');
        if (!Validator::positiveInt($id)) {
            Redirect::to($this->backUrl($usuario));
        }

        $this->preguntaModel->desactivar($id, $usuario['id']);
        $_SESSION['exito_editor'] = 'Pregunta desactivada correctamente.';
        Redirect::to($this->backUrl($usuario));
    }

    public function activarPregunta()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to($this->backUrl($usuario));
        }

        $id = $this->request->post('id');
        if (!Validator::positiveInt($id)) {
            Redirect::to($this->backUrl($usuario));
        }

        $this->preguntaModel->aprobar($id, $usuario['id']);
        $_SESSION['exito_editor'] = 'Pregunta activada correctamente.';
        Redirect::to($this->backUrl($usuario));
    }

    public function reportes()
    {
        $usuario = Auth::requerirEditorOAdmin();

        $porPagina = 15;
        $page = max(1, (int)$this->request->get('page', 1));
        $res = $this->preguntaModel->listarReportes($page, $porPagina);

        $exito = $_SESSION['exito_editor'] ?? null;
        unset($_SESSION['exito_editor']);

        $data = [
            'usuario' => $usuario,
            'esEditor' => ($usuario['rol'] === 'editor'),
            'reportes' => $res['filas'],
            'exito' => $exito,
            'pag' => $this->buildPaginacion($page, $res['paginas'], 'page', ''),
        ];

        $this->renderer->render('editor_reportes', $data);
    }

    public function aprobarReporte()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/editor/reportes');
        }

        $reporteId = $this->request->post('reporte_id');
        $preguntaId = $this->request->post('pregunta_id');

        if (!Validator::positiveInt($reporteId) || !Validator::positiveInt($preguntaId)) {
            Redirect::to('/editor/reportes');
        }

        $this->preguntaModel->resolverReporte($reporteId, $usuario['id'], 'aprobada');
        $this->preguntaModel->desactivar($preguntaId, $usuario['id']);

        $_SESSION['exito_editor'] = 'Reporte aprobado. La pregunta fue desactivada.';
        Redirect::to('/editor/reportes');
    }

    public function rechazarReporte()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/editor/reportes');
        }

        $reporteId = $this->request->post('reporte_id');

        if (!Validator::positiveInt($reporteId)) {
            Redirect::to('/editor/reportes');
        }

        $this->preguntaModel->resolverReporte($reporteId, $usuario['id'], 'rechazada');

        $_SESSION['exito_editor'] = 'Reporte rechazado. La pregunta se mantiene activa.';
        Redirect::to('/editor/reportes');
    }

    public function pendientes()
    {
        $usuario = Auth::requerirEditorOAdmin();

        $porPagina = 15;
        $page = max(1, (int)$this->request->get('page', 1));
        $res = $this->preguntaModel->listarPendientes($page, $porPagina);
        $pendientes = $res['filas'];
        foreach ($pendientes as &$p) {
            $p['esCorrectaA'] = ($p['respuesta_correcta'] === 'A');
            $p['esCorrectaB'] = ($p['respuesta_correcta'] === 'B');
            $p['esCorrectaC'] = ($p['respuesta_correcta'] === 'C');
            $p['esCorrectaD'] = ($p['respuesta_correcta'] === 'D');
        }

        $exito = $_SESSION['exito_editor'] ?? null;
        unset($_SESSION['exito_editor']);

        $data = [
            'usuario' => $usuario,
            'esEditor' => ($usuario['rol'] === 'editor'),
            'pendientes' => $pendientes,
            'exito' => $exito,
            'pag' => $this->buildPaginacion($page, $res['paginas'], 'page', ''),
        ];

        $this->renderer->render('editor_pendientes', $data);
    }

    public function aprobarPendiente()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/editor/pendientes');
        }

        $id = $this->request->post('id');
        if (!Validator::positiveInt($id)) {
            Redirect::to('/editor/pendientes');
        }

        $this->preguntaModel->aprobar($id, $usuario['id']);
        $_SESSION['exito_editor'] = 'Pregunta aprobada y activada.';
        Redirect::to('/editor/pendientes');
    }

    public function rechazarPendiente()
    {
        $usuario = Auth::requerirEditorOAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/editor/pendientes');
        }

        $id = $this->request->post('id');
        if (!Validator::positiveInt($id)) {
            Redirect::to('/editor/pendientes');
        }

        $this->preguntaModel->eliminar($id);
        $_SESSION['exito_editor'] = 'Pregunta rechazada y eliminada.';
        Redirect::to('/editor/pendientes');
    }
}
