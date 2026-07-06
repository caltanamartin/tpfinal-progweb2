<?php

class TrampitaController
{
    private $renderer;
    private $trampitaModel;
    private $partidaModel;
    private $partidaPreguntaModel;
    private $preguntaModel;
    private $request;

    public function __construct($renderer, $trampitaModel, $partidaModel, $partidaPreguntaModel, $preguntaModel, $request)
    {
        $this->renderer = $renderer;
        $this->trampitaModel = $trampitaModel;
        $this->partidaModel = $partidaModel;
        $this->partidaPreguntaModel = $partidaPreguntaModel;
        $this->preguntaModel = $preguntaModel;
        $this->request = $request;
    }

    private function verificarUsuario()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario) {
            Redirect::to('/login');
            return null;
        }
        return $usuario;
    }

    public function index()
    {
        $usuario = $this->verificarUsuario();
        if (!$usuario) return;

        $tipos = $this->trampitaModel->getTipos();
        $stockRaw = $this->trampitaModel->contarDisponibles($usuario['id']);
        $stock = [
            'c_5050' => $stockRaw['50/50'],
            'c_skip' => $stockRaw['skip'],
            'c_freeze' => $stockRaw['congelar_tiempo'],
        ];

        $data = [
            'usuario' => $usuario,
            'tipos' => $tipos,
            'stock' => $stock,
            'esTrampitas' => true,
        ];

        $exito = $_SESSION['exito_compra'] ?? null;
        unset($_SESSION['exito_compra']);
        if ($exito) {
            $data['exito'] = $exito;
        }

        $error = $_SESSION['error_compra'] ?? null;
        unset($_SESSION['error_compra']);
        if ($error) {
            $data['error'] = $error;
        }

        $this->renderer->render('comprar_trampita', $data);
    }

    public function comprar()
    {
        $usuario = $this->verificarUsuario();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = $this->request->post('tipo');
            $cardNumber = $this->request->post('card_number');
            $cardExpiry = $this->request->post('card_expiry');
            $cardCvv = $this->request->post('card_cvv');
            $cardName = $this->request->post('card_name');

            $errores = [];

            $tiposValidos = ['50/50', 'skip', 'congelar_tiempo'];
            if (!in_array($tipo, $tiposValidos)) {
                $errores[] = 'Tipo de trampita inválido.';
            }

            $cardNumber = preg_replace('/\s+/', '', $cardNumber);
            if (!preg_match('/^\d{16}$/', $cardNumber)) {
                $errores[] = 'Número de tarjeta inválido (16 dígitos).';
            }

            if (!preg_match('/^\d{2}\/\d{2}$/', $cardExpiry)) {
                $errores[] = 'Fecha de expiración inválida (MM/AA).';
            } else {
                $parts = explode('/', $cardExpiry);
                $mes = (int)$parts[0];
                $anio = (int)$parts[1];
                if ($mes < 1 || $mes > 12) {
                    $errores[] = 'Mes de expiración inválido.';
                } else {
                    $anioCompleto = 2000 + $anio;
                    $ahora = new DateTime();
                    $vencimiento = new DateTime("{$anioCompleto}-{$mes}-01");
                    $vencimiento->modify('last day of this month');
                    if ($vencimiento < $ahora) {
                        $errores[] = 'La tarjeta está vencida.';
                    }
                }
            }

            $cardCvv = preg_replace('/\s+/', '', $cardCvv);
            if (!preg_match('/^\d{3}$/', $cardCvv)) {
                $errores[] = 'CVV inválido (3 dígitos).';
            }

            if (empty(trim($cardName))) {
                $errores[] = 'Nombre del titular requerido.';
            }

            if (!empty($errores)) {
                $_SESSION['error_compra'] = implode(' ', $errores);
                Redirect::to('/trampitas/comprar?tipo=' . urlencode($tipo));
            }

            $this->trampitaModel->comprar($usuario['id'], $tipo);
            $_SESSION['exito_compra'] = 'Compra exitosa. ¡Trampita agregada a tu cuenta!';
            Redirect::to('/trampitas');
        }

        $tipo = $this->request->get('tipo', '50/50');
        $tiposValidos = ['50/50', 'skip', 'congelar_tiempo'];
        if (!in_array($tipo, $tiposValidos)) {
            $tipo = '50/50';
        }

        $tipos = $this->trampitaModel->getTipos();
        $tipoInfo = null;
        foreach ($tipos as $t) {
            if ($t['tipo'] === $tipo) {
                $tipoInfo = $t;
                $tipoInfo['es5050'] = ($tipo === '50/50');
                $tipoInfo['esSkip'] = ($tipo === 'skip');
                $tipoInfo['esFreeze'] = ($tipo === 'congelar_tiempo');
                break;
            }
        }

        $error = $_SESSION['error_compra'] ?? null;
        unset($_SESSION['error_compra']);

        $data = [
            'usuario' => $usuario,
            'tipoInfo' => $tipoInfo,
            'error' => $error,
            'esComprar' => true,
        ];

        $this->renderer->render('comprar_trampita', $data);
    }

    public function usar()
    {
        $usuario = $this->verificarUsuario();
        if (!$usuario) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to('/');
        }

        $tipo = $this->request->post('tipo');
        $tiposValidos = ['50/50', 'skip', 'congelar_tiempo'];
        if (!in_array($tipo, $tiposValidos)) {
            Redirect::to('/');
        }

        $sessionPartida = $_SESSION['partida_actual'] ?? null;
        if (!$sessionPartida) {
            Redirect::to('/');
        }

        $partidaId = $sessionPartida['partida_id'];
        $partida = $this->partidaModel->obtener($partidaId);
        if (!$partida || $partida['usuario_id'] != $usuario['id']) {
            unset($_SESSION['partida_actual']);
            Redirect::to('/');
        }

        if ($partida['estado'] === 'terminada') {
            unset($_SESSION['partida_actual']);
            Redirect::to('/juego/resultado?id=' . $partidaId);
        }

        $disponibles = $this->trampitaModel->obtenerDisponibles($usuario['id']);
        $trampitaId = null;
        foreach ($disponibles as $t) {
            if ($t['tipo'] === $tipo) {
                $trampitaId = $t['id'];
                break;
            }
        }

        if (!$trampitaId) {
            $_SESSION['error_categoria'] = 'No tenés esa trampita disponible.';
            Redirect::to('/juego?id=' . $partidaId);
        }

        $this->trampitaModel->usar($trampitaId, $partidaId);

        $preguntaId = $sessionPartida['pregunta_id'];
        $orden = $sessionPartida['orden'];

        if ($tipo === 'skip') {
            $this->partidaPreguntaModel->registrar($partidaId, $preguntaId, null, 0, $orden);
            unset($_SESSION['partida_actual']);
            $_SESSION['respuesta_correcta'] = false;
            Redirect::to('/juego/categoria?id=' . $partidaId);
        } elseif ($tipo === '50/50') {
            $pregunta = $this->preguntaModel->getPreguntaConCategoria($preguntaId);
            if ($pregunta) {
                $correcta = $pregunta['respuesta_correcta'];
                $opciones = ['A', 'B', 'C', 'D'];
                $incorrectas = array_values(array_diff($opciones, [$correcta]));
                shuffle($incorrectas);
                $_SESSION['ocultar_opciones'] = array_slice($incorrectas, 0, 2);
            }
            Redirect::to('/juego?id=' . $partidaId);
        } elseif ($tipo === 'congelar_tiempo') {
            $_SESSION['partida_actual']['mostrada_en'] += 15;
            Redirect::to('/juego?id=' . $partidaId);
        }
    }

    public function adminListar()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario || ($usuario['rol'] ?? 'usuario') !== 'admin') {
            Redirect::to('/');
        }

        $page = max(1, (int)$this->request->get('page', 1));
        $porPagina = 10;
        $offset = ($page - 1) * $porPagina;

        $tipoFiltro = $this->request->get('tipo');
        if ($tipoFiltro && !in_array($tipoFiltro, ['50/50', 'skip', 'congelar_tiempo'])) {
            $tipoFiltro = null;
        }

        $trampitas = $this->trampitaModel->listarTodas($offset, $porPagina, $tipoFiltro);
        $total = $this->trampitaModel->contarTodas($tipoFiltro);
        $estadisticas = $this->trampitaModel->getEstadisticas();

        foreach ($trampitas as &$t) {
            $t['es5050'] = ($t['tipo'] === '50/50');
            $t['esSkip'] = ($t['tipo'] === 'skip');
            $t['esFreeze'] = ($t['tipo'] === 'congelar_tiempo');
            $t['esDisponible'] = ($t['estado'] === 'disponible');
            $t['esUsado'] = ($t['estado'] === 'usado');
        }
        unset($t);

        $totalPaginas = max(1, ceil($total / $porPagina));

        $data = [
            'usuario' => $usuario,
            'trampitas' => $trampitas,
            'page' => $page,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'estadisticas' => $estadisticas,
            'esAdminTrampitas' => true,
        ];

        if ($page > 1) $data['page_prev'] = $page - 1;
        if ($page < $totalPaginas) $data['page_next'] = $page + 1;

        if ($tipoFiltro) {
            $data['tipoFiltro'] = $tipoFiltro;
            $data['esFiltro5050'] = ($tipoFiltro === '50/50');
            $data['esFiltroSkip'] = ($tipoFiltro === 'skip');
            $data['esFiltroFreeze'] = ($tipoFiltro === 'congelar_tiempo');
            $data['esFiltroTodas'] = false;
        } else {
            $data['esFiltroTodas'] = true;
            $data['esFiltro5050'] = false;
            $data['esFiltroSkip'] = false;
            $data['esFiltroFreeze'] = false;
        }

        $this->renderer->render('admin_trampitas', $data);
    }

}
