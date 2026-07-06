<?php

class AdminController
{
    private $renderer;
    private $usuarioModel;
    private $partidaModel;
    private $preguntaModel;
    private $request;

    public function __construct($renderer, $usuarioModel, $partidaModel, $preguntaModel, $request)
    {
        $this->renderer = $renderer;
        $this->usuarioModel = $usuarioModel;
        $this->partidaModel = $partidaModel;
        $this->preguntaModel = $preguntaModel;
        $this->request = $request;
    }

    private function verificarAdmin()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario || ($usuario['rol'] ?? 'usuario') !== 'admin') {
            Redirect::to('/');
            return null;
        }
        return $usuario;
    }

    public function index()
    {
        $usuario = $this->verificarAdmin();
        if (!$usuario) return;

        $filtro = $this->request->get('filtro', 'mes');
        if (!in_array($filtro, ['dia', 'semana', 'mes', 'anio'])) {
            $filtro = 'mes';
        }

        $totalJugadores = $this->usuarioModel->getTotalJugadores($filtro);
        $totalPartidas = $this->partidaModel->getTotalPartidas($filtro);
        $preguntasActivas = $this->preguntaModel->getPreguntasActivas();
        $totalPreguntas = $this->preguntaModel->getTotalPreguntas($filtro);

        $usuariosNuevos = $this->usuarioModel->getUsuariosNuevosPorPeriodo($filtro);
        $partidasPeriodo = $this->partidaModel->getPartidasPorPeriodo($filtro);

        $seriesTiempo = $this->combinarSeries($usuariosNuevos, $partidasPeriodo);

        $usuariosPorPais = $this->usuarioModel->getUsuariosPorPais($filtro);
        $usuariosPorSexo = $this->usuarioModel->getUsuariosPorSexo($filtro);
        $usuariosPorRol = $this->usuarioModel->getUsuariosPorRol();
        $usuariosPorEdad = $this->usuarioModel->getUsuariosPorEdad($filtro);
        $porcentajeCorrectas = $this->usuarioModel->getPorcentajeCorrectasPorUsuario($filtro);

        $porPagina = 10;

        $pageU = max(1, (int)$this->request->get('page_usuarios', 1));
        $pageP = max(1, (int)$this->request->get('page_partidas', 1));
        $pageQ = max(1, (int)$this->request->get('page_preguntas', 1));

        $usuariosRes = $this->usuarioModel->getAll($pageU, $porPagina);
        $partidasRes = $this->partidaModel->getTodas($pageP, $porPagina);
        $preguntasRes = $this->preguntaModel->listarTodas($pageQ, $porPagina);

        $todosUsuarios = $usuariosRes['filas'];
        $todasPartidas = $partidasRes['filas'];
        $todasPreguntas = $preguntasRes['filas'];

        foreach ($todosUsuarios as &$u) {
            $u['verificadoSi'] = $u['verificado'] ? 'Sí' : 'No';
            $u['rol_admin'] = ($u['rol'] === 'admin');
            $u['rol_editor'] = ($u['rol'] === 'editor');
            $u['rol_usuario'] = ($u['rol'] === 'usuario');
        }
        unset($u);

        foreach ($todasPartidas as &$p) {
            $p['jugando'] = ($p['estado'] === 'jugando');
            $p['terminada'] = ($p['estado'] === 'terminada');
        }
        unset($p);

        foreach ($todasPreguntas as &$p) {
            $p['esCorrectaA'] = ($p['respuesta_correcta'] === 'A');
            $p['esCorrectaB'] = ($p['respuesta_correcta'] === 'B');
            $p['esCorrectaC'] = ($p['respuesta_correcta'] === 'C');
            $p['esCorrectaD'] = ($p['respuesta_correcta'] === 'D');
        }
        unset($p);

        $data = [
            'usuario' => $usuario,
            'esAdmin' => true,
            'totalJugadores' => $totalJugadores,
            'totalPartidas' => $totalPartidas,
            'preguntasActivas' => $preguntasActivas,
            'totalPreguntas' => $totalPreguntas,
            'filtroActual' => $filtro,
            'esDia' => $filtro === 'dia',
            'esSemana' => $filtro === 'semana',
            'esMes' => $filtro === 'mes',
            'esAnio' => $filtro === 'anio',
            'seriesTiempoJson' => json_encode($seriesTiempo),
            'usuariosPorPais' => $usuariosPorPais,
            'usuariosPorPaisJson' => json_encode($usuariosPorPais),
            'usuariosPorSexo' => $usuariosPorSexo,
            'usuariosPorSexoJson' => json_encode($usuariosPorSexo),
            'usuariosPorRol' => $usuariosPorRol,
            'usuariosPorRolJson' => json_encode($usuariosPorRol),
            'usuariosPorEdad' => $usuariosPorEdad,
            'usuariosPorEdadJson' => json_encode($usuariosPorEdad),
            'porcentajeCorrectas' => $porcentajeCorrectas,
            'porcentajeCorrectasJson' => json_encode($porcentajeCorrectas),
            'todosUsuarios' => $todosUsuarios,
            'todasPartidas' => $todasPartidas,
            'todasPreguntas' => $todasPreguntas,
            'pagUsuarios' => $this->buildPaginacion($pageU, $usuariosRes['paginas'], 'page_usuarios', 'usuarios'),
            'pagPartidas' => $this->buildPaginacion($pageP, $partidasRes['paginas'], 'page_partidas', 'partidas'),
            'pagPreguntas' => $this->buildPaginacion($pageQ, $preguntasRes['paginas'], 'page_preguntas', 'preguntas'),
        ];

        $this->renderer->render('admin_index', $data);
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

    private function combinarSeries($usuarios, $partidas)
    {
        $usuariosMap = [];
        foreach ($usuarios as $row) {
            $usuariosMap[$row['periodo']] = (int)$row['total'];
        }
        $partidasMap = [];
        foreach ($partidas as $row) {
            $partidasMap[$row['periodo']] = (int)$row['total'];
        }

        $periodos = array_unique(array_merge(array_keys($usuariosMap), array_keys($partidasMap)));
        sort($periodos);

        $series = [];
        foreach ($periodos as $p) {
            $series[] = [
                'periodo' => $p,
                'usuarios' => $usuariosMap[$p] ?? 0,
                'partidas' => $partidasMap[$p] ?? 0,
            ];
        }
        return $series;
    }
}
